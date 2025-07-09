<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

require_once NV_ROOTDIR . '/vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;

$page_title = $lang_module['config'];
$message = '';
$message_type = '';
$array_config = [];

if ($nv_Request->isset_request('submit', 'post')) {
    $array_config = [
        'use_elastic' => $nv_Request->get_int('use_elastic', 'post', 0),
    ];

    if ($array_config['use_elastic']) {
        $array_config += [
            'elas_host' => $nv_Request->get_title('elas_host', 'post', ''),
            'elas_port' => $nv_Request->get_title('elas_port', 'post', ''),
            'elas_user' => $nv_Request->get_title('elas_user', 'post', ''),
            'elas_pass' => $nv_Request->get_title('elas_pass', 'post', '')
        ];
    }

    $elastic_error = false;
    try {
        $client = ClientBuilder::create()
            ->setHosts([$array_config['elas_host'] . ':' . $array_config['elas_port']])
            ->setBasicAuthentication($array_config['elas_user'], $array_config['elas_pass'])
            ->setSSLVerification(false)
            ->build();
        $client->info();
    } catch (Exception $e) {
        $message = $lang_module['elastic_connection_error'] . ': ' . $e->getMessage();
        $message_type = 'danger';
        $array_config['use_elastic'] = 0;
        $elastic_error = true;
    }

    $sql = 'UPDATE ' . NV_CONFIG_GLOBALTABLE . ' 
            SET config_value = :config_value 
            WHERE lang = :lang 
            AND module = :module 
            AND config_name = :config_name';
    $sth = $db->prepare($sql);

    try {
        $status = true;
        foreach ($array_config as $config_name => $config_value) {
            if (!$sth->execute([
                'config_value' => $config_value,
                'lang' => NV_LANG_DATA,
                'module' => $module_name,
                'config_name' => $config_name
            ])) {
                $status = false;
                break;
            }
        }

        if (!$elastic_error) {
            $message = $lang_module['config_updated'];
            $message_type = 'success';
            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['config'], $lang_module['config_elastic'], $admin_info['userid']);
            $nv_Cache->delAll();
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
        error_log($lang_module['config_update_failed'] . $e->getMessage());
    }
}

$query = $db->query('SELECT config_name, config_value FROM ' . NV_CONFIG_GLOBALTABLE . ' WHERE module = ' . $db->quote($module_name) . ' AND lang = ' . $db->quote(NV_LANG_DATA));
while ($row = $query->fetch()) {
    $array_config[$row['config_name']] = $row['config_value'];
}

$xtpl = new XTemplate('config.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
$xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
$xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('OP', $op);
$xtpl->assign('CONFIG', $array_config);
$xtpl->assign('USE_ELASTIC_CHECKED', !empty($array_config['use_elastic']) && $array_config['use_elastic'] == 1 ? 'checked="checked"' : '');

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
