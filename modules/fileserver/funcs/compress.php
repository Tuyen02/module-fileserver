<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);
$action = $nv_Request->get_title('action', 'post', '');

$sql = 'SELECT file_name, file_path, compressed FROM ' . NV_PREFIXLANG . '_fileserver_files WHERE file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$message = '';
if (!$row) {
    $message = 'File không tồn tại.';
} else {
    $zipFilePath = NV_ROOTDIR . $row['file_path'];
    $extractTo = NV_ROOTDIR . '/uploads/fileserver/' . pathinfo($row['file_name'], PATHINFO_FILENAME);
    
    $zip = new PclZip($zipFilePath);
    $list = $zip->extract(PCLZIP_OPT_PATH, $extractTo);

    if ($action === 'unzip' && $row['compressed'] == 1) {
        if ($list) {
            $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_fileserver_files 
                           SET is_folder = 1, compressed = 0, file_name = :new_name, file_path = :new_path 
                           WHERE file_id = :file_id';
            $update_stmt = $db->prepare($update_sql);
            $new_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
            $new_path = '/uploads/fileserver/' . $new_name;
            $update_stmt->bindParam(':new_name', $new_name, PDO::PARAM_INT);
            $update_stmt->bindParam(':new_path', $new_path, PDO::PARAM_INT);
            $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $update_stmt->execute();

             function addToDatabase($files, $parent_id, $basePath, $level, $db, $file_id)
            {
                foreach ($files as $file) {
                    $isFolder = ($file['folder'] == 1) ? 1 : 0;
                    $filePath = str_replace(NV_ROOTDIR, '', $file['filename']);

                    $insert_sql = 'INSERT INTO ' . NV_PREFIXLANG . '_fileserver_files 
                                   (file_name, file_path, is_folder, lev, compressed) 
                                   VALUES (:file_name, :file_path, :is_folder, :lev, 0)';
                    $insert_stmt = $db->prepare($insert_sql);
                    $insert_stmt->execute([
                        ':file_name' => basename($file['filename']),
                        ':file_path' => $filePath,
                        ':is_folder' => $isFolder,
                        ':lev' => $file_id,
                    ]);
                }
            }

            addToDatabase($list, $file_id, $extractTo, 1, $db, $file_id);
            @nv_deletefile($zipFilePath);

            $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_fileserver_files 
                           SET is_folder = 1, compressed = 0, file_name = :new_name, file_path = :new_path 
                           WHERE file_id = :file_id';
            $update_stmt = $db->prepare($update_sql);
            $new_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
            $new_path = '/uploads/fileserver/' . $new_name;
            $update_stmt->bindParam(':new_name', $new_name, PDO::PARAM_INT);
            $update_stmt->bindParam(':new_path', $new_path, PDO::PARAM_INT);
            $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $update_stmt->execute();

            $message = 'Giải nén thành công.';
        }
    }
}

$xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);

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
