<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['edit_img'];

// $file_id = $nv_Request->get_int('file_id', 'get', 0);
$page = $nv_Request->get_int('page', 'get', 1);

$sql = "SELECT file_name, file_path, lev FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit_img/')
];

if (!empty($row)) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
}

$contents = nv_page_edit_img($row, $file_id);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
