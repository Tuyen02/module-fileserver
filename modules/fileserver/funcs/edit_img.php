<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$file_id = $nv_Request->get_int('file_id', 'get', 0);
$page = $nv_Request->get_int("page", "get", 1);

$sql = "SELECT file_name, file_path, lev FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();


if (!empty($row)) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
}

$contents = nv_page_edit_img($row,$file_id);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
