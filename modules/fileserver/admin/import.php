<?php
if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'xlsx';
$admin_info['allow_files_type'][] = 'xls';

function importSheetData($sheet, $parent_id, &$importedSheets, $parent_path = '/uploads/fileserver')
{
    global $db, $objPHPExcel;
    $Totalrow = $sheet->getHighestRow();

    for ($i = 5; $i <= $Totalrow; $i++) {
        $real_path = $sheet->getCell('C' . $i)->getValue();
        $file_path = $real_path;

        if (!empty($file_path)) {
            $file_name = basename($file_path);
            $file_path = $parent_path . '/' . $file_name;
            $full_path = NV_ROOTDIR . $file_path;
            $is_folder = pathinfo($file_name, PATHINFO_EXTENSION) == '' ? 1 : 0;

            $file_content = '';
            if (!$is_folder && file_exists($real_path)) {
                $file_content = file_get_contents($real_path);
            }

            $folder_path = NV_ROOTDIR . $file_path;
            if ($is_folder && !file_exists($folder_path)) {
                mkdir($folder_path, 0777, true);
            } else {
                $dir_path = dirname($full_path);
                if (!file_exists($dir_path)) {
                    mkdir($dir_path, 0777, true);
                }
                if (!file_exists($full_path)) {
                    file_put_contents($full_path, $file_content);
                }
            }

            $file_size = file_exists($full_path) ? filesize($full_path) : 0;

            $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . 'fileserver_files (file_name, file_path, file_size, uploaded_by, created_at, is_folder, lev) 
                    VALUES (:file_name, :file_path, :file_size, :uploaded_by, :created_at, :is_folder, :lev)';
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':file_size', $file_size, PDO::PARAM_INT);
            $uploaded_by = 1;
            $stmt->bindParam(':uploaded_by', $uploaded_by, PDO::PARAM_INT);
            $created_at = NV_CURRENTTIME;
            $stmt->bindParam(':created_at', $created_at, PDO::PARAM_INT);
            $stmt->bindParam(':is_folder', $is_folder, PDO::PARAM_INT);
            $stmt->bindParam(':lev', $parent_id, PDO::PARAM_INT);
            $stmt->execute();

            $file_id = $db->lastInsertId();
            updateAlias($file_id, $file_name);
            updatePerm($file_id);
            updateLog($parent_id);

            if ($is_folder && !in_array($file_name, $importedSheets)) {
                $sub_sheet = $objPHPExcel->getSheetByName($file_name);
                if ($sub_sheet) {
                    $importedSheets[] = $file_name;
                    importSheetData($sub_sheet, $file_id, $importedSheets, $file_path);
                }
            }
        }
    }
}

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

        $upload_dir = NV_ROOTDIR . '/data/tmp/import-file';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $upload_info = $upload->save_file($_FILES['uploadfile'], $upload_dir, false, $global_config['nv_auto_resize']);

        if ($upload_info['error'] == '') {
            $link_file = $upload_info['name'];

            try {
                $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($link_file);
                $sheetNames = $objPHPExcel->getSheetNames();
                $importedSheets = [];

                $sheet = $objPHPExcel->getSheet(0);
                importSheetData($sheet, 0,  $importedSheets);

                foreach ($sheetNames as $sheetIndex => $sheetName) {
                    if ($sheetIndex == 0)
                        continue;

                    if (!in_array($sheetName, $importedSheets)) {
                        $sheet = $objPHPExcel->getSheet($sheetIndex);

                        $sql = ' SELECT file_id, file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_name = :file_name AND is_folder = 1 AND lev = 0';
                        $stmt = $db->prepare($sql);
                        $stmt->bindParam(':file_name', $sheetName, PDO::PARAM_STR);
                        $stmt->execute();
                        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($parent) {
                            importSheetData($sheet, $parent['file_id'],  $importedSheets, $parent['file_path']);
                        }
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
    $file_path = NV_ROOTDIR . '/themes/default/images/fileserver/import_file.xlsx';
    if (file_exists($file_path)) {
        $download = new NukeViet\Files\Download($file_path, NV_ROOTDIR . '/themes/default/images/fileserver/', 'import_file.xlsx', true, 0);
        $download->download_file();
    } else {
        $error = $lang_module['error_file_not_found'];
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