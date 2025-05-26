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
        'use_captcha' => $nv_Request->get_int('use_captcha', 'post', 0)
    ];

    if ($array_config['use_elastic']) {
        $array_config += [
            'elas_host' => $nv_Request->get_title('elas_host', 'post', ''),
            'elas_port' => $nv_Request->get_title('elas_port', 'post', ''),
            'elas_user' => $nv_Request->get_title('elas_user', 'post', ''),
            'elas_pass' => $nv_Request->get_title('elas_pass', 'post', '')
        ];
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

        if ($status) {
            $message = $lang_module['config_updated'];
            $message_type = 'success';
            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['config'], $lang_module['config_elastic'], $admin_info['userid']);
            $nv_Cache->delAll();
        } else {
            throw new Exception($lang_module['config_update_failed']);
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
        error_log($lang_module['config_update_failed'] . $e->getMessage());
    }
}

if ($nv_Request->isset_request('sync_elastic', 'post')) {
    try {
        $elastic_config = [
            'use_elastic' => $module_config['fileserver']['use_elastic'],
            'elas_host' => $module_config['fileserver']['elas_host'],
            'elas_port' => $module_config['fileserver']['elas_port'],
            'elas_user' => $module_config['fileserver']['elas_user'],
            'elas_pass' => $module_config['fileserver']['elas_pass']
        ];

        if (!$elastic_config['use_elastic']) {
            $message = $lang_module['elastic_not_enabled'];
            $message_type = 'warning';
        } elseif (
            empty($elastic_config['elas_host']) || empty($elastic_config['elas_port'])
            || empty($elastic_config['elas_user']) || empty($elastic_config['elas_pass'])
        ) {
            $message = $lang_module['elastic_config_incomplete'];
            $message_type = 'warning';
        } else {
            $client = ClientBuilder::create()
                ->setHosts([$elastic_config['elas_host'] . ':' . $elastic_config['elas_port']])
                ->setBasicAuthentication($elastic_config['elas_user'], $elastic_config['elas_pass'])
                ->setSSLVerification(false)
                ->build();

            $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE elastic = 0';
            $result = $db->query($sql);

            $updated_count = 0;
            $file_ids = [];

            $bulk_params = ['body' => []];

            while ($row = $result->fetch()) {
                $bulk_params['body'][] = [
                    'index' => [
                        '_index' => $module_data,
                        '_id' => $row['file_id']
                    ]
                ];

                $bulk_params['body'][] = [
                    'file_id' => $row['file_id'],
                    'file_name' => $row['file_name'],
                    'file_path' => $row['file_path'],
                    'file_size' => $row['file_size'],
                    'uploaded_by' => $row['uploaded_by'],
                    'is_folder' => $row['is_folder'],
                    'status' => $row['status'],
                    'lev' => $row['lev'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'compressed' => $row['compressed'],
                ];

                $file_ids[] = $row['file_id'];
                $updated_count++;

                if (count($bulk_params['body']) >= 2000) {
                    if (!empty($bulk_params['body'])) {
                        $client->bulk($bulk_params);
                        $bulk_params = ['body' => []];
                    }

                    if (!empty($file_ids)) {
                        $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                      SET elastic = ' . NV_CURRENTTIME . ' 
                                      WHERE file_id IN (' . implode(',', $file_ids) . ')';
                        $db->exec($update_sql);
                        $file_ids = [];
                    }
                }
            }

            if (!empty($bulk_params['body'])) {
                $client->bulk($bulk_params);
            }

            if (!empty($file_ids)) {
                $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                              SET elastic = ' . NV_CURRENTTIME . ' 
                              WHERE file_id IN (' . implode(',', $file_ids) . ')';
                $db->exec($update_sql);
            }

            $message = sprintf($lang_module['sync_elastic_success'], $updated_count);
            $message_type = 'success';
            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['config'], $lang_module['sync_elastic'], $admin_info['userid']);

            $nv_Cache->delAll();
        }
    } catch (Exception $e) {
        $message = $lang_module['sync_elastic_failed'] . ': ' . $e->getMessage();
        $message_type = 'danger';
        error_log($lang_module['error_sync_elastic'] . $e->getMessage());
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
$xtpl->assign('USE_CAPTCHA_CHECKED', $array_config['use_captcha'] == 1 ? ' checked="checked"' : '');

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
