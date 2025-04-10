<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$page_title = $lang_module['perm'];

$status = '';
$message = '';

$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT f.file_name, f.file_path, f.alias,
        (SELECT p.p_group FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p WHERE p.file_id = f.file_id) AS p_group,
        (SELECT p.p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p WHERE p.file_id = f.file_id) AS p_other
        FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
        WHERE f.file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => $base_url
];

$group_level = $row['p_group'];
$other_level = $row['p_other'];

if (defined('NV_IS_SPADMIN')) {
    if ($nv_Request->isset_request('submit', 'post')) {
        $group_permission = $nv_Request->get_int('group_permission', 'post', 0);
        $other_permission = $nv_Request->get_int('other_permission', 'post', 0);

        $sql_check = 'SELECT permission_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
        $check_stmt = $db->query($sql_check);

        if ($check_stmt->rowCount() > 0) {
            $sql_update = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                           SET  p_group = :p_group, p_other = :p_other, updated_at = :updated_at 
                           WHERE file_id = :file_id';
            $update_stmt = $db->prepare($sql_update);
            $update_stmt->bindParam(':p_group', $group_permission);
            $update_stmt->bindParam(':p_other', $other_permission);
            $update_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                           (file_id, p_group, p_other, updated_at) 
                           VALUES (:file_id, :p_group, :p_other, :updated_at)';
            $insert_stmt = $db->prepare($sql_insert);
            $insert_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':p_group', $group_permission);
            $insert_stmt->bindParam(':p_other', $other_permission);
            $insert_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $insert_stmt->execute();
        }

        $sql_children = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = ' . $file_id;
        $children_stmt = $db->query($sql_children);

        while ($child = $children_stmt->fetch()) {
            $sql_update_child = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                                 SET p_group = :p_group, p_other = :p_other, updated_at = :updated_at 
                                 WHERE file_id = :file_id';
            $update_child_stmt = $db->prepare($sql_update_child);
            $update_child_stmt->bindParam(':p_group', $group_permission);
            $update_child_stmt->bindParam(':p_other', $other_permission);
            $update_child_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $update_child_stmt->bindParam(':file_id', $child['file_id'], PDO::PARAM_INT);
            $update_child_stmt->execute();
        }

        $status = 'success';
        $message = $lang_module['update_ok'];

        $stmt->execute();
        $row = $stmt->fetch();

        $group_level = $row['p_group'];
        $other_level = $row['p_other'];
        nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['perm'], 'File id: ' . $file_id .'. Nhóm người dùng mức: ' . $group_level .'. Nhóm khác mức: ' . $other_level , $user_info['userid']);
    }
} else {
    $status = $lang_module['error'];
    $message = $lang_module['not_thing_to_do'];
}

$xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_NAME', $row['file_name']);
$xtpl->assign('FILE_PATH', $row['file_path']);
$xtpl->assign('FILE_ID', $file_id);

$xtpl->assign('GROUP_LEVEL_1', $group_level == 1 ? 'selected' : '');
$xtpl->assign('GROUP_LEVEL_2', $group_level == 2 ? 'selected' : '');
$xtpl->assign('GROUP_LEVEL_3', $group_level == 3 ? 'selected' : '');

$xtpl->assign('OTHER_LEVEL_1', $other_level == 1 ? 'selected' : '');
$xtpl->assign('OTHER_LEVEL_2', $other_level == 2 ? 'selected' : '');

if ($status) {
    $xtpl->assign('MESSAGE_CLASS', $status == 'success' ? 'alert-success' : 'alert-danger');
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
