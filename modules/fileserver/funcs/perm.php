<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get,post', 0);
$message = '';

$message = '1'.$nv_Request->get_string('submit', 'post', '');
if ($nv_Request->get_string('submit', 'post', '')) {

    $owner_read = $nv_Request->get_int('owner_read', 'post', 0);
    $owner_write = $nv_Request->get_int('owner_write', 'post', 0);
    $owner_execute = $nv_Request->get_int('owner_execute', 'post', 0);

    $group_read = $nv_Request->get_int('group_read', 'post', 0);
    $group_write = $nv_Request->get_int('group_write', 'post', 0);
    $group_execute = $nv_Request->get_int('group_execute', 'post', 0);

    $other_read = $nv_Request->get_int('other_read', 'post', 0);
    $other_write = $nv_Request->get_int('other_write', 'post', 0);
    $other_execute = $nv_Request->get_int('other_execute', 'post', 0);


    $permissions = ($owner_read * 4 + $owner_write * 2 + $owner_execute) .
                   ($group_read * 4 + $group_write * 2 + $group_execute) .
                   ($other_read * 4 + $other_write * 2 + $other_execute);

    $sql = 'UPDATE ' .NV_PREFIXLANG . '_fileserver_files
            SET permissions = :permissions, updated_at = :updated_at 
            WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':permissions', $permissions, PDO::PARAM_STR);
    $stmt->bindParam(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();

    $file_path = $file_data['file_path']; 

    $chmod_value = octdec($permissions);

    if (chmod($file_path, $chmod_value)) {
        $message = 'File permissions updated successfully.';
    } else {
        $message = 'Failed to change file permissions on the server.';
    }
}

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$file_data = $stmt->fetch();

if (!$file_data) {
    $message = 'File does not exist.';
}

$xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', $file_data['file_name']);
$xtpl->assign('FILE_PATH', $file_data['file_path']);
$xtpl->assign('CURRENT_PERMISSIONS', $file_data['permissions']);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FORM_ACTION', NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm&amp;file_id=' . $file_id);

$current_permissions = str_split($file_data['permissions']);
$xtpl->assign('OWNER_READ', $current_permissions[0] & 4 ? 'checked' : '');
$xtpl->assign('OWNER_WRITE', $current_permissions[0] & 2 ? 'checked' : '');
$xtpl->assign('OWNER_EXECUTE', $current_permissions[0] & 1 ? 'checked' : '');

$xtpl->assign('GROUP_READ', $current_permissions[1] & 4 ? 'checked' : '');
$xtpl->assign('GROUP_WRITE', $current_permissions[1] & 2 ? 'checked' : '');
$xtpl->assign('GROUP_EXECUTE', $current_permissions[1] & 1 ? 'checked' : '');

$xtpl->assign('OTHER_READ', $current_permissions[2] & 4 ? 'checked' : '');
$xtpl->assign('OTHER_WRITE', $current_permissions[2] & 2 ? 'checked' : '');
$xtpl->assign('OTHER_EXECUTE', $current_permissions[2] & 1 ? 'checked' : '');

if ($message != '') {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
