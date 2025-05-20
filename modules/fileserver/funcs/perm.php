<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

if (!defined('NV_IS_SPADMIN')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$page_title = $lang_module['perm'];
$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT file_id, file_name, file_path, alias, lev FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND file_id = ' . $file_id;
$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetch();

if ($row) {
    $sql_perm = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
    $stmt_perm = $db->prepare($sql_perm);
    $stmt_perm->execute();
    $perm = $stmt_perm->fetch();
    if ($perm) {
        $row['p_group'] = $perm['p_group'];
        $row['p_other'] = $perm['p_other'];
    } else {
        $row['p_group'] = 0;
        $row['p_other'] = 0;
    }
}

if (empty($row)) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$back_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name;
if ($row['lev'] > 0) {
    $sql = 'SELECT lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $row['lev'];
    $parent = $db->query($sql)->fetch();
    if ($parent && $parent['lev'] > 0) {
        $parent_alias = $db->query('SELECT alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $parent['lev'])->fetchColumn();
        if ($parent_alias) {
            $back_url .= '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '/' . $parent_alias;
        }
    }
}

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

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

$group_level = $row['p_group'] ?? 0;
$other_level = $row['p_other'] ?? 0;
$status = '';
$message = '';

if ($nv_Request->isset_request('submit', 'post')) {
    $group_permission = $nv_Request->get_int('group_permission', 'post', 0);
    $other_permission = $nv_Request->get_int('other_permission', 'post', 0);

    if ($group_permission != $group_level || $other_permission != $other_level) {
        $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                SET p_group = :p_group, p_other = :p_other, updated_at = :updated_at 
                WHERE file_id = :file_id';
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':p_group', $group_permission, PDO::PARAM_INT);
        $stmt->bindParam(':p_other', $other_permission, PDO::PARAM_INT);
        $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->execute();

        updatePermissions($file_id, $group_permission, $other_permission);
        nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['perm'], 'File id: ' . $file_id . '. Nhóm người dùng mức: ' . $group_permission . '. Nhóm khác mức: ' . $other_permission, $user_info['userid']);

        $group_level = $group_permission;
        $other_level = $other_permission;
        $perm = [
            'p_group' => $group_level,
            'p_other' => $other_level
        ];
        $status = 'success';
        $message = $lang_module['update_ok'];
    } else {
        $status = 'error';
        $message = $lang_module['no_changes'];
    }
}

$contents = nv_fileserver_perm($row, $perm, $status, $message, $back_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';