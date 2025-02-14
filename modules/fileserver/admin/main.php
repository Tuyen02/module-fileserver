<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}
$page_title = 'FILE SERVER';

$sql = 'SELECT d.group_id, title FROM ' . NV_GROUPS_GLOBALTABLE . ' AS g LEFT JOIN ' . NV_GROUPSDETAIL_GLOBALTABLE . ' d 
        ON g.group_id = d.group_id AND d.lang = "' . NV_LANG_DATA . '"
        WHERE g.idsite = ' . $global_config['idsite'] . ' OR (g.idsite = 0 AND g.group_id > 3 AND g.siteus = 1) 
        ORDER BY g.idsite, g.weight ASC';
$result = $db->query($sql);
$post = [];
$mess = '';
$err = '';
$post['group_ids'] = $nv_Request->get_array('group_ids', 'post', []);
$group_ids_str = implode(',', $post['group_ids']);

if ($nv_Request->isset_request('submit', 'post')) {
    if (empty($post['group_ids'])) {
        $err = $lang_module['no_group'];
    } else {
        $group_ids_str = implode(',', $post['group_ids']);
        $config_name = 'group_admin_fileserver';

        $sql_check = ' SELECT COUNT(*) FROM ' . NV_CONFIG_GLOBALTABLE . ' WHERE config_name = :config_name';
        $stmt_check = $db->prepare($sql_check);
        $stmt_check->bindParam(':config_name', $config_name, PDO::PARAM_STR);
        $stmt_check->execute();
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $nv_Cache->delMod($module_name, $lang = 'vi');
            $sql_update = ' UPDATE ' . NV_CONFIG_GLOBALTABLE . '
                           SET config_value = :config_value 
                           WHERE config_name = :config_name';
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->bindParam(':config_value', $group_ids_str, PDO::PARAM_STR);
            $stmt_update->bindParam(':config_name', $config_name, PDO::PARAM_STR);
            if ($stmt_update->execute()) {
                $mess = $lang_module['update_success'];
            } else {
                $err = $lang_module['update_error'];
            }
        } else {
            $lang = 'vi';
            $sql_insert = ' INSERT INTO ' . NV_CONFIG_GLOBALTABLE . ' (lang, module, config_name, config_value) 
                           VALUES (:lang, :module, :config_name, :config_value)';
            $stmt_insert = $db->prepare($sql_insert);
            $stmt_insert->bindParam(':lang', $lang, PDO::PARAM_STR);
            $stmt_insert->bindParam(':module', $module_name, PDO::PARAM_STR);
            $stmt_insert->bindParam(':config_name', $config_name, PDO::PARAM_STR);
            $stmt_insert->bindParam(':config_value', $group_ids_str, PDO::PARAM_STR);
            if ($stmt_insert->execute()) {
                $mess = $lang_module['update_success'];
            } else {
                $err = $lang_module['update_error'];
            }
        }
    }
} else {
    $config_name = 'group_admin_fileserver';
    $sql_get = ' SELECT config_value FROM ' . NV_CONFIG_GLOBALTABLE . ' WHERE config_name = :config_name';
    $stmt_get = $db->prepare($sql_get);
    $stmt_get->bindParam(':config_name', $config_name, PDO::PARAM_STR);
    $stmt_get->execute();
    $group_ids_str = $stmt_get->fetchColumn();
    $post['group_ids'] = !empty($group_ids_str) ? explode(',', $group_ids_str) : [];
}

$group_titles = [];
if (!empty($post['group_ids'])) {
    $placeholders = implode(',', array_fill(0, count($post['group_ids']), '?'));
    $sql_titles = "SELECT group_id, title FROM ". NV_GROUPSDETAIL_GLOBALTABLE. " WHERE group_id IN ($placeholders) AND lang = '" . NV_LANG_DATA . "'";
    $stmt_titles = $db->prepare($sql_titles);
    $stmt_titles->execute($post['group_ids']);
    while ($row = $stmt_titles->fetch(PDO::FETCH_ASSOC)) {
        $group_titles[$row['group_id']] = $row['title'];
    }
}

$xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('NV_LANG_VARIABLE', NV_LANG_VARIABLE);
$xtpl->assign('NV_LANG_DATA', NV_LANG_DATA);
$xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
$xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
$xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('OP', $op);
$xtpl->assign('POST', $post);

foreach ($result as $row) {
    $checked = in_array($row['group_id'], $post['group_ids']) ? 'selected' : '';
    $xtpl->assign('ROW', $row);
    $xtpl->assign('CHECKED', $checked);
    $xtpl->parse('main.loop');
}

foreach ($post['group_ids'] as $group_id) {
    if (isset($group_titles[$group_id])) {
        $xtpl->assign('GROUP_TITLE', $group_titles[$group_id]);
        $xtpl->parse('main.selected_groups');
    }
}

if ($mess != '') {
    $xtpl->assign('MESSAGE', $mess);
    $xtpl->parse('main.message');
}

if ($err != '') {
    $xtpl->assign('ERROR', $err);
    $xtpl->parse('main.error');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';