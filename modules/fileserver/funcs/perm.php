<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

if (!defined('NV_IS_SPADMIN')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$page_title = $lang_module['perm'];

$status = '';
$message = '';
$back_url = '';

$page = $nv_Request->get_int('page', 'get', 1);

$sql_file = 'SELECT file_name, file_path, alias, lev FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id AND status = 1';
$stmt_file = $db->prepare($sql_file);
$stmt_file->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt_file->execute();
$row = $stmt_file->fetch();

$sql_perm = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = :file_id';
$stmt_perm = $db->prepare($sql_perm);
$stmt_perm->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt_perm->execute();
$row_perm = $stmt_perm->fetch();

if ($row_perm) {
    $row['p_group'] = $row_perm['p_group'];
    $row['p_other'] = $row_perm['p_other'];
} else {
    $row['p_group'] = 0;
    $row['p_other'] = 0;
}

if ($row['lev'] > 0) {
    $sql = 'SELECT lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $row['lev'];
    $parent = $db->query($sql)->fetch();
    if ($parent) {
        $back_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
        if ($parent['lev'] > 0) {
            $sql = 'SELECT alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $parent['lev'];
            $parent_alias = $db->query($sql)->fetchColumn();
            if ($parent_alias) {
                $op = $module_info['alias']['main'];
                $back_url .= '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $parent_alias;
            }
        }
    }
}

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

$breadcrumbs[] = [
        'catid' => $row['lev'],
        'title' => $row['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias']
    ];
$current_lev = $row['lev'];

while ($current_lev > 0) {
    $sql = 'SELECT file_name, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $_row = $db->query($sql)->fetch();
    if (empty($_row)) {
        break;
    }
    $op = $_row['is_folder'] == 1 ? $module_info['alias']['main'] : $op;
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $_row['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $_row['alias']
    ];
    $current_lev = $_row['lev'];
}
$breadcrumbs = array_reverse($breadcrumbs);
$array_mod_title = array_merge($array_mod_title ?? [], $breadcrumbs);

$group_level = $row['p_group'];
$other_level = $row['p_other'];

if ($nv_Request->isset_request('submit', 'post')) {
    $group_permission = $nv_Request->get_int('group_permission', 'post', 0);
    $other_permission = $nv_Request->get_int('other_permission', 'post', 0);

    $old_group_level = $group_level;
    $old_other_level = $other_level;

    $sql_check = 'SELECT permission_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
    $check_stmt = $db->query($sql_check);

    $sql_upsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
        (file_id, p_group, p_other, updated_at) 
        VALUES (:file_id, :p_group, :p_other, :updated_at)
        ON DUPLICATE KEY UPDATE 
            p_group = VALUES(p_group), 
            p_other = VALUES(p_other), 
            updated_at = VALUES(updated_at)';
    $upsert_stmt = $db->prepare($sql_upsert);
    $upsert_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $upsert_stmt->bindParam(':p_group', $group_permission);
    $upsert_stmt->bindParam(':p_other', $other_permission);
    $upsert_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $upsert_stmt->execute();

    if ($group_permission != $group_level || $other_permission != $other_level) {
        updatePermissions($file_id, $group_permission, $other_permission);
        $status = 'success';
        $message = $lang_module['update_ok'];
    }

    $sql_perm = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = :file_id';
    $stmt_perm = $db->prepare($sql_perm);
    $stmt_perm->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt_perm->execute();
    $row_perm = $stmt_perm->fetch();

    if ($row_perm) {
        $group_level = $row_perm['p_group'];
        $other_level = $row_perm['p_other'];
    }

    if ($old_group_level != $group_level || $old_other_level != $other_level) {
        nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['perm'], 'File id: ' . $file_id . '. Nhóm người dùng mức: ' . $group_level . '. Nhóm khác mức: ' . $other_level, $user_info['userid']);
    }
}

$perm = [
    'p_group' => $group_level,
    'p_other' => $other_level,
];

$contents = nv_fileserver_perm($row, $perm, $status, $message, $back_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
