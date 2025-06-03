<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

if (!defined('NV_IS_SPADMIN')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$page_title = $lang_module['perm'];

$sql_file = 'SELECT file_name, file_path, alias, lev, uploaded_by FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $file_id . ' AND status = 1';
$stmt_file = $db->prepare($sql_file);
$stmt_file->execute();
$row = $stmt_file->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

if (empty($row)) {
    nv_redirect_location($base_url);
}

$status = '';
$message = '';

$current_permission = get_user_permission($file_id, $row['uploaded_by']);
$row['p_group'] = $current_permission;
$row['p_other'] = $current_permission;

$breadcrumbs[] = [
    'catid' => $row['lev'],
    'title' => $row['file_name'],
    'link' => $page_url
];

$current_lev = $row['lev'];
while ($current_lev > 0) {
    $sql = 'SELECT file_name, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $_row = $stmt->fetch();
    
    if (empty($_row)) {
        break;
    }
    
    $op = $_row['is_folder'] == 1 ? $module_info['alias']['main'] : $op;
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $_row['file_name'],
        'link' => $page_url
    ];  
    $current_lev = $_row['lev'];
}

$breadcrumbs = array_reverse($breadcrumbs);
$array_mod_title = array_merge(isset($array_mod_title) ? $array_mod_title : [], $breadcrumbs);

if ($nv_Request->isset_request('submit', 'post')) {
    $group_permission = $nv_Request->get_int('group_permission', 'post', 0);
    $other_permission = $nv_Request->get_int('other_permission', 'post', 0);

    $sql_perm = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
    $stmt_perm = $db->prepare($sql_perm);
    $stmt_perm->execute();
    $row_perm = $stmt_perm->fetch();

    $old_group_level = $row_perm ? $row_perm['p_group'] : 0;
    $old_other_level = $row_perm ? $row_perm['p_other'] : 0;

    if ($group_permission == $old_group_level && $other_permission == $old_other_level) {
        $status = 'error';
        $message = $lang_module['no_changes'];
    } else {
        try {
            $db->beginTransaction();

            $sql_upsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                (file_id, p_group, p_other, updated_at) 
                VALUES (:file_id, :p_group, :p_other, :updated_at)
                ON DUPLICATE KEY UPDATE 
                    p_group = VALUES(p_group), 
                    p_other = VALUES(p_other), 
                    updated_at = VALUES(updated_at)';
                    
            $upsert_stmt = $db->prepare($sql_upsert);
            $upsert_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $upsert_stmt->bindParam(':p_group', $group_permission, PDO::PARAM_INT);
            $upsert_stmt->bindParam(':p_other', $other_permission, PDO::PARAM_INT);
            $upsert_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $upsert_stmt->execute();

            updatePermissions($file_id, $group_permission, $other_permission);
            
            $current_permission = get_user_permission($file_id, $row['uploaded_by']);
            $row['p_group'] = $current_permission;
            $row['p_other'] = $current_permission;

            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['perm'], 
                'File id: ' . $file_id . '. Nhóm người dùng mức: ' . $row['p_group'] . '. Nhóm khác mức: ' . $row['p_other'], 
                $user_info['userid']
            );

            $db->commit();
            $status = 'success';
            $message = $lang_module['update_ok'];
        } catch (Exception $e) {
            $db->rollBack();
            $status = 'error';
            $message = $lang_module['error_save'];
        }
    }
}

$perm = [
    'p_group' => $row['p_group'],
    'p_other' => $row['p_other'],
];

$reponse = [
    'status' => $status,
    'message' => $message,
];

$contents = nv_fileserver_perm($row, $perm, $reponse);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
