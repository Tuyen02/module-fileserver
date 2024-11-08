<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$file_id = $nv_Request->get_int('file_id', 'get,post', '0');
$sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = ".$file_id;
$result = $db->query($sql);
$row = $result->fetch();

$file_content = file_get_contents(NV_ROOTDIR . '/uploads/fileserver/module-fileserver.docx' );

$xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_CONTENT', $file_content);

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';