<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}
$page_title = 'FILE SERVER';

$sql = 'SELECT group_id, title 
        FROM ' . NV_GROUPSDETAIL_GLOBALTABLE . ' 
        WHERE lang = ' . $db->quote(NV_LANG_DATA) . ' 
        AND group_id != 6
        ORDER BY group_id ASC';
$result = $db->query($sql);
$post = [];
$mess = '';
$err = '';

$post['group_ids'] = $nv_Request->get_array('group_ids', 'post', []);
$group_ids_str = implode(',', $post['group_ids']);

$lang = 'vi';
$config_name = 'group_admin_fileserver';
if ($nv_Request->isset_request('submit', 'post')) {
    if (empty($post['group_ids'])) {
        $err = $lang_module['no_group'];
    } else {
        $group_ids_str = implode(',', $post['group_ids']);

        $sql_check = 'SELECT COUNT(*) AS count FROM ' . NV_CONFIG_GLOBALTABLE . ' WHERE config_name = ' . $db->quote($config_name);
        $count = $db->query($sql_check)->fetchColumn();

        if ($count > 0) {
            $sql_update = 'UPDATE ' . NV_CONFIG_GLOBALTABLE . '
                           SET config_value = :config_value 
                           WHERE config_name = ' . $db->quote($config_name);
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->bindParam(':config_value', $group_ids_str, PDO::PARAM_STR);
            if ($stmt_update->execute()) {
                $nv_Cache->delAll();
                $db->query('UPDATE ' . NV_CONFIG_GLOBALTABLE . " SET config_value = '" . NV_CURRENTTIME . "' WHERE lang = 'sys' AND module = 'global' AND config_name = 'timestamp'");
                nv_save_file_config_global();
                $mess = $lang_module['update_success'];
            } else {
                $err = $lang_module['update_error'];
            }
        } else {
            $sql_insert = 'INSERT INTO ' . NV_CONFIG_GLOBALTABLE . ' (lang, module, config_name, config_value) 
                           VALUES (:lang, :module, :config_name, :config_value)';
            $stmt_insert = $db->prepare($sql_insert);
            $stmt_insert->bindParam(':lang', $lang, PDO::PARAM_STR);
            $stmt_insert->bindParam(':module', $module_name, PDO::PARAM_STR);
            $stmt_insert->bindParam(':config_name', $config_name, PDO::PARAM_STR);
            $stmt_insert->bindParam(':config_value', $group_ids_str, PDO::PARAM_STR);
            if ($stmt_insert->execute()) {
                $nv_Cache->delAll();
                $db->query('UPDATE ' . NV_CONFIG_GLOBALTABLE . " SET config_value = '" . NV_CURRENTTIME . "' WHERE lang = 'sys' AND module = 'global' AND config_name = 'timestamp'");
                nv_save_file_config_global();
                $mess = $lang_module['update_success'];
            } else {
                $err = $lang_module['update_error'];
            }
        }
    }
} else {
    $sql_get = 'SELECT config_value FROM ' . NV_CONFIG_GLOBALTABLE . ' WHERE config_name = ' . $db->quote($config_name);
    $group_ids_str = $db->query($sql_get)->fetchColumn();
    $post['group_ids'] = !empty($group_ids_str) ? explode(',', $group_ids_str) : [];
}

if ($mess == $lang_module['update_success']) {
    nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['choose_group'], $lang_module['main_title'], $admin_info['userid']);
}

$group_titles = [];
if (!empty($post['group_ids'])) {
    $group_ids_str = implode(',', $post['group_ids']);
    $sql_titles = "SELECT group_id, title FROM " . NV_GROUPSDETAIL_GLOBALTABLE . " WHERE group_id IN ($group_ids_str) AND lang = " . $db->quote(NV_LANG_DATA);
    $result_titles = $db->query($sql_titles);
    while ($row = $result_titles->fetch(PDO::FETCH_ASSOC)) {
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
    $row['title'] = ($row['group_id'] < 10) ? $lang_global['level' . $row['group_id']] : $row['title'];
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