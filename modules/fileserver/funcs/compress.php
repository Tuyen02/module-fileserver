<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);
$action = $nv_Request->get_title('action', 'post', '');

$sql = 'SELECT file_name, file_size, file_path, compressed FROM ' . NV_PREFIXLANG . '_fileserver_files WHERE file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$status = '';
$message = '';
if (!$row) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
} else {
    $zipFilePath = NV_ROOTDIR . $row['file_path'];
    $extractTo = NV_ROOTDIR . '/uploads/fileserver/' . pathinfo($row['file_name'], PATHINFO_FILENAME);

    if (!is_dir($extractTo)) {
        mkdir($extractTo, 0777, true);
    }

    $zip = new PclZip($zipFilePath);
    $list = $zip->extract(PCLZIP_OPT_PATH, $extractTo);

    $file_size_zip = file_exists($zipFilePath) ? filesize($zipFilePath) : 0;

    if ($action === 'unzip' && $row['compressed'] == 1) {
        if ($list) {
            addToDatabase($list, $file_id, $db);

            if (nv_deletefile($zipFilePath)) {
                $status = $lang_module['success'];
                $message = $lang_module['unzip_ok'];
            } else {
                $status = $lang_module['error'];
                $message = $lang_module['unzip_ok_cant_delete'];
            }
            $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_fileserver_files 
                           SET is_folder = 1, compressed = 0, file_name = :new_name, file_path = :new_path, file_size = :file_size ,created_at= :created_at
                           WHERE file_id = :file_id';
            $update_stmt = $db->prepare($update_sql);
            $new_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
            $new_path = '/uploads/fileserver/' . $new_name;
            $update_stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
            $update_stmt->bindParam(':new_path', $new_path, PDO::PARAM_STR);
            $update_stmt->bindParam(':file_size', $file_size_zip, PDO::PARAM_INT);
            $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $update_stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $update_stmt->execute();
            updateLog($file_id);
        } else {
            $status = $lang_module['error'];
            $message = $lang_module['unzip_false'];
        }
    }
}

$xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);

if ($file_size_zip > 0) {
    $xtpl->assign('ZIP_FILE_SIZE', nv_convertfromBytes($file_size_zip));
    $xtpl->parse('main.zip_file_size');
}

if (!empty($list)) {
    foreach ($list as $file) {
        $file['file_name'] = basename($file['filename']);
        $file['file_size'] = $file['folder'] ? '-' : nv_convertfromBytes($file['size']);
        $file['file_type'] = $file['folder'] ? 'fa-folder-o' : 'fa-file-o';
        $xtpl->assign('FILE', $file);
        $xtpl->parse('main.file');
    }
}

if (!empty($message)) {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
