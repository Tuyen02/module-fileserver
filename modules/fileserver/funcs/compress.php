<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$page_title = $lang_module['compress'];

$action = $nv_Request->get_title('action', 'post', '');
$page = $nv_Request->get_int('page', 'get', 1);

$sql = "SELECT file_name, file_size, file_path, compressed, alias FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $lev;
$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress/' . $row['alias'] . '&page=' . $page;

$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => $base_url
];

$status = '';
$message = '';
$list = [];
$file_size_zip = 0;

if (!$row) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
} else {
    $zipFilePath = NV_ROOTDIR . $row['file_path'];
    $extractTo = NV_ROOTDIR . '/uploads/fileserver/' . pathinfo($row['file_name'], PATHINFO_FILENAME);

    if ($action === 'unzip' && $row['compressed'] != 0) {
        if (!is_dir($extractTo)) {
            mkdir($extractTo);
        }

        $zip = new PclZip($zipFilePath);
        $list = $zip->extract(PCLZIP_OPT_PATH, $extractTo, PCLZIP_OPT_REMOVE_PATH, NV_ROOTDIR);

        $file_size_zip = file_exists($zipFilePath) ? filesize($zipFilePath) : 0;

        if ($list) {
            addToDatabase($list, $file_id);

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
                nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name);
            }
        } else {
            $status = $lang_module['error'];
            $message = $lang_module['unzip_false'];
        }
    } else {
        $compressed = $row['compressed'];
        $fileIds = explode(',', $compressed);

        if (!empty($fileIds)) {
            $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
            $sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id IN ($placeholders)";
            $stmt = $db->prepare($sql);
            $stmt->execute($fileIds);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$tree = buildTree($list);
$tree_html = displayTree($tree);

$contents = nv_fileserver_compress($list, $file_id, $message, $tree_html);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';