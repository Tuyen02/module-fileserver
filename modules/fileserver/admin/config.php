<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

$page_title = $lang_module['config'];
$message = '';
$message_type = '';

if ($nv_Request->isset_request('submit', 'post')) {
    $array_config = [];
    $use_elastic = $nv_Request->get_int('use_elastic', 'post', 0);
    $array_config['use_elastic'] = $use_elastic;

    if ($use_elastic) {
        $array_config['elas_host'] = $nv_Request->get_title('elas_host', 'post', '');
        $array_config['elas_port'] = $nv_Request->get_title('elas_port', 'post', '');
        $array_config['elas_user'] = $nv_Request->get_title('elas_user', 'post', '');
        $array_config['elas_pass'] = $nv_Request->get_title('elas_pass', 'post', '');
    }

    $success = true;
    foreach ($array_config as $config_name => $config_value) {
        $sql = "INSERT INTO " . NV_CONFIG_GLOBALTABLE . " (lang, module, config_name, config_value) 
                VALUES (:lang, :module, :config_name, :config_value) 
                ON DUPLICATE KEY UPDATE config_value = :config_value_update";
        $sth = $db->prepare($sql);
        $sth->bindValue(':lang', NV_LANG_DATA);
        $sth->bindValue(':module', $module_name);
        $sth->bindValue(':config_name', $config_name);
        $sth->bindValue(':config_value', $config_value);
        $sth->bindValue(':config_value_update', $config_value);

        try {
            if (!$sth->execute()) {
                $success = false;
            }
        } catch (PDOException $e) {
            $success = false;
            trigger_error("Error updating config: " . $e->getMessage());
        }
    }

    if ($success) {
        $message = $lang_module['config_updated'];
        $message_type = 'success';
    } else {
        $message = $lang_module['config_failed'];
        $message_type = 'danger';
    }
}

$array_config = [];
$query = $db->query("SELECT config_name, config_value FROM " . NV_CONFIG_GLOBALTABLE . " WHERE module = 'fileserver' AND lang = '" . NV_LANG_DATA . "'");
while ($row = $query->fetch()) {
    $array_config[$row['config_name']] = $row['config_value'];
}

$array_config['use_elastic'] = isset($array_config['use_elastic']) ? (int)$array_config['use_elastic'] : 0;
$array_config['elas_host'] = isset($array_config['elas_host']) ? $array_config['elas_host'] : 'https://localhost';
$array_config['elas_port'] = isset($array_config['elas_port']) ? $array_config['elas_port'] : '9200';
$array_config['elas_user'] = isset($array_config['elas_user']) ? $array_config['elas_user'] : 'elastic';
$array_config['elas_pass'] = isset($array_config['elas_pass']) ? $array_config['elas_pass'] : '';

$xtpl = new XTemplate('config.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
$xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
$xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('OP', $op);
$xtpl->assign('CONFIG', $array_config);

$xtpl->assign('USE_ELASTIC_CHECKED', $array_config['use_elastic'] ? ' checked="checked"' : '');

if ($message != '') {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->assign('MESSAGE_TYPE', $message_type);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';