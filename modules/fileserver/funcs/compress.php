<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$page_title = $lang_module['compress'];

// $file_id = $nv_Request->get_int('file_id', 'get', 0);
$action = $nv_Request->get_title('action', 'post', '');

$sql = 'SELECT file_name, file_size, file_path, compressed,alias FROM ' . NV_PREFIXLANG . '_fileserver_files WHERE file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress/' . $row['alias'] . '&page=' . $page;

//$canonicalUrl = getCanonicalUrl($page_url, true, true);
$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => $base_url
];
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
            $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $new_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
            $new_path = '/uploads/fileserver/' . $new_name;
            $update_stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
            $update_stmt->bindParam(':new_path', $new_path, PDO::PARAM_STR);
            $update_stmt->bindParam(':file_size', $file_size_zip, PDO::PARAM_INT);
            $update_stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);

            if ($update_stmt->execute()) {
                updateLog($file_id);
                $file_id = $db->lastInsertId();
                updateAlias($file_id, $row['file_name']);
                $sql1 = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_permissions (file_id, p_group, p_other, updated_at) 
                    VALUES (:file_id, :p_group, :p_other, :updated_at)";
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmta->execute();
            }
        } else {
            $status = $lang_module['error'];
            $message = $lang_module['unzip_false'];
        }
    }
}

$contents = nv_page_compress($row, $file_id, $file_size_zip, $list, $message);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
