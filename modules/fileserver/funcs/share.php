<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);


$sql = "SELECT file_name, file_path, view, share FROM ". NV_PREFIXLANG . '_' . $module_data ."_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (!$row) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
}
$share = $row['share'];
$status = '';
$message = '';
if ($share == 0) {
    $message = $lang_module['no_share'];

} elseif ($share == 1) {
    $message = $lang_module['share_w_user'];

} elseif ($share == 2) {
    $message = $lang_module['share_w_everyone'];

}


if (!$nv_Request->isset_request($module_name . '-' . $file_id, 'session')) {
    $nv_Request->set_Session($module_name . '-' . $file_id, NV_CURRENTTIME);
    $sql = "UPDATE ". NV_PREFIXLANG . '_' . $module_data ."_files SET view = view + 1 WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $row['file_path'];

$view = $row['view'];
$file_content = file_exists($full_path) ? file_get_contents($full_path) : '';

if ($nv_Request->get_int('file_id', 'post') > 0) {
    $file_content = $nv_Request->get_string('file_content', 'post');

    file_put_contents($full_path, $file_content);

    $file_size = filesize($full_path);

    $sql = "UPDATE ". NV_PREFIXLANG . '_' . $module_data ."_files SET updated_at = :updated_at, file_size = :file_size WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();

    $status = $lang_module['success'];
    $message = $lang_module['update_ok'];
}
$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['lev'];

$contents = nv_page_share($row, $file_content,  $file_id, $file_name, $view, $view_url, $message);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
