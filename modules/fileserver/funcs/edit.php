<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$file_id = $nv_Request->get_int('file_id', 'get', 0);
$page = $nv_Request->get_int("page", "get", 1);

$sql = "SELECT file_name, file_path, lev FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['lev'];

if (!empty($row)) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
}
$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$file_content = file_exists($full_path) ? file_get_contents($full_path) : '';

$status = '';
$message = '';
if (defined('NV_IS_SPADMIN')) {
    if ($nv_Request->get_int('file_id', 'post') > 0) {
        $file_content = $nv_Request->get_string('file_content', 'post');

        file_put_contents($full_path, $file_content);

        $file_size = filesize($full_path);

        $sql = "UPDATE " . NV_PREFIXLANG . '_' . $module_data . "_files SET updated_at = :updated_at, file_size = :file_size WHERE file_id = :file_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->execute();

        updateLog($row['lev']);
        $status = $lang_module['error'];
        $message = $lang_module['update_ok'];
    }
} else {
    $status = $lang_module['error'];
    $message = $lang_module['not_thing_to_do'];
}

$xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', $file_name);
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
