<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get,post', 0);

$sql = "SELECT f.file_name, f.file_path, p.owner, p.`group`, p.other
        FROM " . NV_PREFIXLANG . "_fileserver_files f
        LEFT JOIN " . NV_PREFIXLANG . "_fileserver_permissions p ON f.file_id = p.file_id
        WHERE f.file_id = :file_id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

$message = '';

if ($nv_Request->isset_request('submit', 'post')) {
    $owner_read = $nv_Request->get_int('owner_read', 'post', 0);
    $owner_write = $nv_Request->get_int('owner_write', 'post', 0);
    $owner_execute = $nv_Request->get_int('owner_execute', 'post', 0);
    $group_read = $nv_Request->get_int('group_read', 'post', 0);
    $group_write = $nv_Request->get_int('group_write', 'post', 0);
    $group_execute = $nv_Request->get_int('group_execute', 'post', 0);
    $other_read = $nv_Request->get_int('other_read', 'post', 0);
    $other_write = $nv_Request->get_int('other_write', 'post', 0);
    $other_execute = $nv_Request->get_int('other_execute', 'post', 0);

    $owner_permissions = $owner_read * 4 + $owner_write * 2 + $owner_execute;
    $group_permissions = $group_read * 4 + $group_write * 2 + $group_execute;
    $other_permissions = $other_read * 4 + $other_write * 2 + $other_execute;

    $permissions = [
        'owner' => $owner_permissions,
        'group' => $group_permissions,
        'other' => $other_permissions,
    ];

    $sql_check = "SELECT permission_id FROM " . NV_PREFIXLANG . "_fileserver_permissions WHERE file_id = :file_id";
    $check_stmt = $db->prepare($sql_check);
    $check_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        $sql_update = "UPDATE " . NV_PREFIXLANG . "_fileserver_permissions 
                       SET owner = :owner, `group` = :group, other = :other, updated_at = :updated_at 
                       WHERE file_id = :file_id";
        $update_stmt = $db->prepare($sql_update);
        $update_stmt->bindParam(':owner', $permissions['owner']);
        $update_stmt->bindParam(':group', $permissions['group']);
        $update_stmt->bindParam(':other', $permissions['other']);
        $update_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $update_stmt->execute();
    } else {
        $sql_insert = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions 
                       (file_id, owner, `group`, other, updated_at) 
                       VALUES (:file_id, :owner, :group, :other, :updated_at)";
        $insert_stmt = $db->prepare($sql_insert);
        $insert_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $insert_stmt->bindParam(':owner', $permissions['owner']);
        $insert_stmt->bindParam(':group', $permissions['group']);
        $insert_stmt->bindParam(':other', $permissions['other']);
        $insert_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $insert_stmt->execute();
    }
    $message = 'Permissions updated successfully!';
    $stmt = $db->prepare("SELECT f.file_name, f.file_path, p.owner, p.`group`, p.other
                          FROM " . NV_PREFIXLANG . "_fileserver_files f
                          LEFT JOIN " . NV_PREFIXLANG . "_fileserver_permissions p 
                          ON f.file_id = p.file_id WHERE f.file_id = :file_id");
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();
}

$xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', htmlspecialchars($row['file_name']));
$xtpl->assign('FILE_PATH', htmlspecialchars($row['file_path']));

$permissions = [
    'owner' => $row['owner'],
    'group' => $row['group'],
    'other' => $row['other'],
];

foreach (['owner', 'group', 'other'] as $type) {
    foreach (['read', 'write', 'execute'] as $perm) {
        $perm_value = ${$type} . '_' . $perm;
        $xtpl->assign(strtoupper($type . '_' . $perm . '_CHECKED'), ($permissions[$type] & (4 * ($perm == 'read') + 2 * ($perm == 'write') + 1 * ($perm == 'execute'))) ? 'checked' : '');
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
