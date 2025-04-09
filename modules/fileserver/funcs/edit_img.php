<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['edit_img'];

$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT file_name, file_path, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();
$page_url = nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias']);

$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => $page_url
];

if (!empty($row)) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

$contents = nv_fileserver_edit_img($row, $file_id, $file_extension);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
