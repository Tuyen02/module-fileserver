<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

$error = [];
$success = '';
$admin_info['allow_files_type'][] = 'xlsx';

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
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
                    $array_tender[$_stt]['alias'] = $sheet->getCell('C' . $i)->getValue();
                    $array_tender[$_stt]['file_path'] = $sheet->getCell('D' . $i)->getValue();
                    $array_tender[$_stt]['file_size'] = $sheet->getCell('E' . $i)->getValue();
                    $array_tender[$_stt]['uploaded_by'] = $sheet->getCell('F' . $i)->getValue();
                    $array_tender[$_stt]['created_at'] = $sheet->getCell('G' . $i)->getValue();
                    $array_tender[$_stt]['updated_at'] = $sheet->getCell('H' . $i)->getValue();
                    $array_tender[$_stt]['is_folder'] = $sheet->getCell('I' . $i)->getValue();
                    $array_tender[$_stt]['status'] = $sheet->getCell('J' . $i)->getValue();
                    $array_tender[$_stt]['lev'] = $sheet->getCell('K' . $i)->getValue();
                    $array_tender[$_stt]['view'] = $sheet->getCell('L' . $i)->getValue();
                    $array_tender[$_stt]['share'] = $sheet->getCell('M' . $i)->getValue();
                    $array_tender[$_stt]['compressed'] = $sheet->getCell('N' . $i)->getValue();
                } else {
                    if (!empty($sheet->getCell('D' . $i)->getValue())) {
                        $error[] = sprintf($nv_Lang->getModule('col_import'), $i);
                    }
                }
            }

            if (!empty($array_tender)) {
                foreach ($array_tender as $stt => $data) {
                    // Chuẩn bị dữ liệu
                    $file_name = $db->quote($data['file_name']);
                    $alias = $db->quote($data['alias']);
                    $file_path = $db->quote($data['file_path']);
                    $file_size = intval($data['file_size']);
                    $uploaded_by = intval($admin_info['userid']); // Lưu ID admin upload
                    $created_at = NV_CURRENTTIME;
                    $updated_at = NV_CURRENTTIME;
                    $is_folder = intval($data['is_folder']); // File (không phải folder)
                    $status = intval($data['status']);
                    $lev = intval($data['lev']);
                    $view = intval($data['view']);
                    $share = intval($data['share']);
                    $compressed = intval($data['compressed']);

                    // Chèn vào bảng
                    $sql = 'INSERT INTO ' . $db_config['prefix'] . '_' . NV_LANG_DATA . '_' . $module_data . '_files 
                            (file_name, alias, file_path, file_size, uploaded_by, created_at, updated_at, is_folder, status, lev, view, share, compressed) 
                            VALUES 
                            (' . $file_name . ', ' . $alias . ', ' . $file_path . ', ' . $file_size . ', ' . $uploaded_by . ', ' . $created_at . ', ' . $updated_at . ', ' . $is_folder . ', ' . $status . ', ' . $lev . ', ' . $view . ', ' . $share . ', ' . $compressed . ')';

                    $db->query($sql);
                }
                $success = 'import_success'; // Thông báo thành công
            }
        } catch (Exception $e) {
            $error[] = $e->getMessage();
        }
    } else {
        $error[] = $upload_info['error'];
    }
}

$xtpl = new XTemplate('import.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op);

if (!empty($error)) {
    $xtpl->assign('ERROR', implode('<br>', $error));
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

