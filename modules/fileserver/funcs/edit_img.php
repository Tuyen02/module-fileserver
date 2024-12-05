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


$xtpl = new XTemplate('edit_img.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);

$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', $row['file_name']);
$xtpl->assign('FILE_PATH', $row['file_path']);


$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
