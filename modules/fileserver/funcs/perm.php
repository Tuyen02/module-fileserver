<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$message = '';

$file_id = $nv_Request->get_int('file_id', 'get,post', 0);

$sql = "SELECT f.file_name, f.file_path, p.`p_group`, p.p_other
        FROM " . NV_PREFIXLANG . "_fileserver_files f
        LEFT JOIN " . NV_PREFIXLANG . "_fileserver_permissions p ON f.file_id = p.file_id
        WHERE f.file_id = :file_id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$group_read_checked = ($row['p_group'] & 1) ? 'checked' : '';
$group_write_checked = ($row['p_group'] & 2) ? 'checked' : '';
$other_read_checked = ($row['p_other'] & 1) ? 'checked' : '';
$other_write_checked = ($row['p_other'] & 2) ? 'checked' : '';

 if(defined('NV_IS_SPADMIN')){
    if ($nv_Request->isset_request('submit', 'post')) {
    
        $group_read = $nv_Request->get_int('group_read', 'post', 0);
        $group_write = $nv_Request->get_int('group_write', 'post', 0);
        
        $other_read = $nv_Request->get_int('other_read', 'post', 0);
        $other_write = $nv_Request->get_int('other_write', 'post', 0);
    
        $group_permissions = $group_read  + $group_write ;
        $other_permissions = $other_read  + $other_write ;
    
        $permissions = [
            'p_group' => $group_permissions,
            'p_other' => $other_permissions,
        ];
    
        $sql_check = "SELECT permission_id FROM " . NV_PREFIXLANG . "_fileserver_permissions WHERE file_id = :file_id";
        $check_stmt = $db->prepare($sql_check);
        $check_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $check_stmt->execute();
    
        if ($check_stmt->rowCount() > 0) {
            $sql_update = "UPDATE " . NV_PREFIXLANG . "_fileserver_permissions 
                           SET  `p_group` = :p_group, p_other = :p_other, updated_at = :updated_at 
                           WHERE file_id = :file_id";
            $update_stmt = $db->prepare($sql_update);
            $update_stmt->bindParam(':p_group', $permissions['p_group']);
            $update_stmt->bindParam(':p_other', $permissions['p_other']);
            $update_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            $sql_insert = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions 
                           (file_id, `p_group`, p_other, updated_at) 
                           VALUES (:file_id, :p_group, :p_other, :updated_at)";
            $insert_stmt = $db->prepare($sql_insert);
            $insert_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':p_group', $permissions['p_group']);
            $insert_stmt->bindParam(':p_other', $permissions['p_other']);
            $insert_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $insert_stmt->execute();
        }
        $message = 'Permissions updated successfully!';
        $stmt = $db->prepare("SELECT f.file_name, f.file_path, p.`p_group`, p.p_other
                              FROM " . NV_PREFIXLANG . "_fileserver_files f
                              LEFT JOIN " . NV_PREFIXLANG . "_fileserver_permissions p 
                              ON f.file_id = p.file_id WHERE f.file_id = :file_id");
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
    }
}else{
    $message = 'Không có quyền thao tác.';
}

$xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', htmlspecialchars($row['file_name']));
$xtpl->assign('FILE_PATH', htmlspecialchars($row['file_path']));
$xtpl->assign('GROUP_READ_CHECKED', $group_read_checked);
$xtpl->assign('GROUP_WRITE_CHECKED', $group_write_checked);
$xtpl->assign('OTHER_READ_CHECKED', $other_read_checked);
$xtpl->assign('OTHER_WRITE_CHECKED', $other_write_checked);

$permissions = [
    'p_group' => $row['p_group'],
    'p_other' => $row['p_other'],
];

foreach (['p_group', 'p_other'] as $type) {
    foreach (['read', 'write'] as $perm) {
        $perm_value = ($perm == 'read') ? 1 : 2; // Quyền đọc là 1, quyền sửa là 2
        $checked = ($permissions[$type] & $perm_value) == $perm_value ? 'checked' : '';
        $xtpl->assign(strtoupper($type . '_' . $perm . '_CHECKED'), $checked);
    }
}

if ($message != '') {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
