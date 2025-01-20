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
                $sheet = $objPHPExcel->getActiveSheet();
                $Totalrow = $sheet->getHighestRow();
                $array_tender = [];

                for ($i = 5; $i <= $Totalrow; $i++) {
                    $_stt = $sheet->getCell('A' . $i)->getValue();
                    if (!empty($_stt)) {
                        $array_tender[$_stt]['file_name'] = $sheet->getCell('B' . $i)->getValue();
                        $array_tender[$_stt]['file_path'] = $sheet->getCell('C' . $i)->getValue();
                        $array_tender[$_stt]['file_size'] = $sheet->getCell('D' . $i)->getValue();
                        $array_tender[$_stt]['uploaded_by'] = $sheet->getCell('E' . $i)->getValue();
                        $array_tender[$_stt]['created_at'] = $sheet->getCell('F' . $i)->getValue();
                        $array_tender[$_stt]['is_folder'] = $sheet->getCell('G' . $i)->getValue();
                        $array_tender[$_stt]['status'] = $sheet->getCell('H' . $i)->getValue();
                    } else {
                        if (!empty($sheet->getCell('C' . $i)->getValue())) {
                            $error = sprintf($nv_Lang->getModule('col_import'), $i);
                        }
                    }
                }

                if (!empty($array_tender)) {
                    foreach ($array_tender as $stt => $data) {
                        $file_name = $db->quote($data['file_name']);
                        $file_path = $db->quote($data['file_path']);
                        $file_size = intval($data['file_size']);

                        $sql1 = "SELECT userid FROM nv4_users WHERE username = :username";
                        $stmt = $db->prepare($sql1);
                        $stmt->bindParam(':username', $data['uploaded_by'], PDO::PARAM_STR);
                        $stmt->execute();
                        $userid = $stmt->fetchColumn();

                        $uploaded_by = $userid;
                        $dateString = $data['created_at'];
                        $dateFormatted = str_replace("/", "-", $dateString); 
                        $timestamp = strtotime($dateFormatted);
                        $created_at = $timestamp;
                        $is_folder = ($data['is_folder'] == 'Thư mục') ? 1 : 0;
                        $status = ($data['status'] == 'Hoạt động') ? 1: 0;

                        $sql = 'INSERT INTO ' . $db_config['prefix'] . '_' . NV_LANG_DATA . '_' . $module_data . '_files 
                            (file_name, file_path, file_size, uploaded_by, created_at, is_folder, status) 
                            VALUES 
                            (' . $file_name . ',' . $file_path . ', ' . $file_size . ', ' . $uploaded_by . ', ' . $created_at . ', ' . $is_folder . ', ' . $status . ')';

                        $db->query($sql);

                        $file_id = $db->lastInsertId();
                        updateAlias($file_id, $file_name);
                        updatePerm($file_id);
                        updateLog(0);
                    }
                    $success = 'import_success';
                }
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

