<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);


$sql = "SELECT file_name, file_path, view, share FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (!$row) {
    exit('File not found');
}
$share = $row['share'];
$message = '';
if ($share == 0) {
    $message = 'File khong chia se';
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $file_id);
    exit();
} elseif ($share == 1) {
    $message = 'File chia se voi nguoi co tai khoan';
    // nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share&amp;file_id=' . $file_id);
    // exit();
} elseif ($share == 2) {
    $message = 'File chia se voi moi nguoi';
    // nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share&amp;file_id=' . $file_id);
    // exit();
}


if(!$nv_Request->isset_request($module_name . '-'.$file_id,'session')){
    $nv_Request->set_Session($module_name . '-'.$file_id, NV_CURRENTTIME);
    $sql = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET view = view + 1 WHERE file_id = :file_id";
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

    $sql = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET updated_at = :updated_at, file_size = :file_size WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT); 
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();

    $message = 'File content has been updated successfully.';
}
$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['lev'];

$xtpl = new XTemplate('share.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', $file_name);
$xtpl->assign('VIEW', $view);
$xtpl->assign('url_view', $view_url);

if ($message != '') {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';