<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}

$page_title = $module_info['site_title'];
$key_words = $module_info['keywords'];
$description = $module_info['description'];

$sql = 'SELECT *    
        FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
        WHERE status = 0 AND lev = :lev';
$stmt = $db->prepare($sql);
$result = $stmt->fetchAll();

$action = $nv_Request->get_title('action', 'post', '');
$fileIds = $nv_Request->get_array('files', 'post', []);

$error = '';
$success = '';

if (!empty($result)) {
    foreach ($result as $row) {
        $sql_logs = 'SELECT log_id, total_size, total_files,total_folders FROM ' . NV_PREFIXLANG . '_' . $module_data . '_logs WHERE lev = :lev';
        $sql_logs = $db->prepare($sql_logs);
        $sql_logs->bindParam(':lev', $row['lev'], PDO::PARAM_INT);
        $sql_logs->execute();
        $logs = $sql_logs->fetch(PDO::FETCH_ASSOC);

        $sql_permissions = 'SELECT `p_group`, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = :file_id';
        $stmt_permissions = $db->prepare($sql_permissions);
        $stmt_permissions->bindParam(':file_id', $row['file_id'], PDO::PARAM_INT);
        $stmt_permissions->execute();
        $permissions = $stmt_permissions->fetch(PDO::FETCH_ASSOC);
    }
} else {
    $logs = [];
    $permissions = [];
}
$nv_BotManager->setFollow()->setNoIndex();

$xtpl = new XTemplate('del_history.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);

if ($error != '') {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}
if ($success != '') {
    $xtpl->assign('success', $success);
    $xtpl->parse('main.success');
}

foreach ($result as $row) {
    if (!empty($logs)) {
        $row['total_size'] = $logs['total_size'] ? number_format($logs['total_size'] / 1024, 2) . ' KB' : '--';
        $row['total_files'] = $logs['total_files'];
        $row['total_folders'] = $logs['total_folders'];
    }

    $row['created_at'] = date('d/m/Y', $row['created_at']);

    $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
    $row['icon_class'] = getFileIconClass($row);

    if ($permissions) {
        $row['p_group'] = $permissions['p_group'];
        $row['p_other'] = $permissions['p_other'];
        $row['permissions'] = $row['p_group'] . $row['p_other'];
    } else {
        $row['permissions'] = 'N/A';
    }

    $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / 1024, 2) . ' KB' : '--';
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';