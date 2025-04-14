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

$breadcrumbs = [];
$current_lev = $lev;

while ($current_lev > 0) {
    $sql1 = 'SELECT file_name, file_path, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $result1 = $db->query($sql1);
    $row1 = $result1->fetch();
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $row1['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=main/' . $row1['alias'] . '&page=' . $page
    ];
    $current_lev = $row1['lev'];
}

$breadcrumbs = array_reverse($breadcrumbs);

foreach ($breadcrumbs as $breadcrumb) {
    $array_mod_title[] = $breadcrumb;
}

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

        updatePermissionsRecursively( $file_id, $group_permission, $other_permission);

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
