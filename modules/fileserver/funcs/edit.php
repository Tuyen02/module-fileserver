<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);

$sql = "SELECT file_name, file_path FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (!$row) {
    exit('File not found');
}

$file_path = $row['file_path'];
$file_content = file_exists($file_path) ? file_get_contents($file_path) : '';

if ($nv_Request->get_int('file_id', 'post') > 0) {
    $file_content = $nv_Request->get_string('file_content', 'post'); 

    file_put_contents($file_path, $file_content);

    $sql = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET updated_at = :updated_at WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();

    $message = 'File content has been updated successfully.';
}

$xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('MESSAGE', isset($message) ? $message : '');

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
