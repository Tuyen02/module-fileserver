<?php
if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'xlsx';
$admin_info['allow_files_type'][] = 'xls';

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    $file_extension = pathinfo($_FILES['uploadfile']['name'], PATHINFO_EXTENSION);
    if (!in_array($file_extension, ['xlsx', 'xls'])) {
        $error = $lang_module['error_file_type'];
    } else {
        $upload = new NukeViet\Files\Upload(
            $admin_info['allow_files_type'],
            $global_config['forbid_extensions'],
            $global_config['forbid_mimes'],
            NV_UPLOAD_MAX_FILESIZE,
            NV_MAX_WIDTH,
            NV_MAX_HEIGHT
        );
        $upload->setLanguage($lang_global);

        $upload_info = $upload->save_file($_FILES['uploadfile'], NV_ROOTDIR . '/data/tmp/import-file', false, $global_config['nv_auto_resize']);

        if ($upload_info['error'] == '') {
            $link_file = $upload_info['name'];

            try {
                $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($link_file);
                $sheetNames = $objPHPExcel->getSheetNames();
                $importedSheets = [];

                function importSheetData($sheet, $parent_id, $db, $objPHPExcel, &$importedSheets) {
                    $Totalrow = $sheet->getHighestRow();

                    for ($i = 5; $i <= $Totalrow; $i++) {
                        $_stt = $sheet->getCell('A' . $i)->getValue();
                        if (!empty($_stt)) {
                            $file_name = $sheet->getCell('B' . $i)->getValue();
                            $file_path = $sheet->getCell('C' . $i)->getValue();
                            $file_size = $sheet->getCell('D' . $i)->getValue();
                            $uploaded_by = $sheet->getCell('E' . $i)->getValue();
                            $created_at = $sheet->getCell('F' . $i)->getValue();
                            $is_folder = ($sheet->getCell('G' . $i)->getValue() == 'Thư mục') ? 1 : 0;
                            $status = ($sheet->getCell('H' . $i)->getValue() == 'Hoạt động') ? 1 : 0;

                            if (is_numeric($created_at)) {
                                $created_at = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($created_at);
                            } else {
                                $created_at = strtotime(str_replace("/", "-", $created_at));
                            }

                            $sql = "SELECT userid FROM nv4_users WHERE username = :username";
                            $stmt = $db->prepare($sql);
                            $stmt->bindParam(':username', $uploaded_by, PDO::PARAM_STR);
                            $stmt->execute();
                            $uploaded_by_id = $stmt->fetchColumn();

                            $sql = "INSERT INTO nv4_vi_fileserver_files (file_name, file_path, file_size, uploaded_by, created_at, is_folder, status, lev) 
                                    VALUES (:file_name, :file_path, :file_size, :uploaded_by, :created_at, :is_folder, :status, :lev)";
                            $stmt = $db->prepare($sql);
                            $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
                            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
                            $stmt->bindParam(':file_size', $file_size, PDO::PARAM_STR);
                            $stmt->bindParam(':uploaded_by', $uploaded_by_id, PDO::PARAM_INT);
                            $stmt->bindParam(':created_at', $created_at, PDO::PARAM_INT);
                            $stmt->bindParam(':is_folder', $is_folder, PDO::PARAM_INT);
                            $stmt->bindParam(':status', $status, PDO::PARAM_INT);
                            $stmt->bindParam(':lev', $parent_id, PDO::PARAM_INT);
                            $stmt->execute();

                            $file_id = $db->lastInsertId();
                            updateAlias($file_id, $file_name);
                            updatePerm($file_id);
                            updateLog($parent_id);

                            $full_path = NV_ROOTDIR . $file_path;
                            if ($is_folder) {
                                if (!file_exists($full_path)) {
                                    mkdir($full_path, 0777, true);
                                }
                            } else {
                                if (!file_exists($full_path)) {
                                    file_put_contents($full_path, '');
                                }
                            }

                            if ($is_folder && !in_array($file_name, $importedSheets)) {
                                $sub_sheet = $objPHPExcel->getSheetByName($file_name);
                                if ($sub_sheet) {
                                    $importedSheets[] = $file_name; // Đánh dấu sheet đã được import
                                    importSheetData($sub_sheet, $file_id, $db, $objPHPExcel, $importedSheets);
                                }
                            }
                        }
                    }
                }

                $sheet = $objPHPExcel->getSheet(0);
                importSheetData($sheet, 0, $db, $objPHPExcel, $importedSheets);

                foreach ($sheetNames as $sheetIndex => $sheetName) {
                    if ($sheetIndex == 0) continue; 

                    if (!in_array($sheetName, $importedSheets)) {
                        $sheet = $objPHPExcel->getSheet($sheetIndex);

                        $sql = "SELECT file_id FROM nv4_vi_fileserver_files WHERE file_name = :file_name AND is_folder = 1 AND status = 1";
                        $stmt = $db->prepare($sql);
                        $stmt->bindParam(':file_name', $sheetName, PDO::PARAM_STR);
                        $stmt->execute();
                        $parent_id = $stmt->fetchColumn();

                        importSheetData($sheet, $parent_id, $db, $objPHPExcel, $importedSheets);
                    }
                }
                $success = $lang_module['import_success'];
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = $upload_info['error'];
        }
    }
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_path = NV_ROOTDIR . '/data/tmp/import-file/test.xlsx';
    if (file_exists($file_path)) {
        $download = new NukeViet\Files\Download($file_path, NV_ROOTDIR . '/data/tmp/import-file/', 'test.xlsx', true, 0);
        $download->download_file();
    }
}

$xtpl = new XTemplate('import.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op);
$xtpl->assign('URL_DOWNLOAD', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=import&download=1');

if (!empty($error)) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}

if (!empty($success)) {
    $xtpl->assign('SUCCESS', $success);
    $xtpl->parse('main.success');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';