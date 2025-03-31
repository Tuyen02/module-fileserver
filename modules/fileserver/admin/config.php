<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

require_once NV_ROOTDIR . '/vendor/autoload.php';
use Elastic\Elasticsearch\ClientBuilder;

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

    $status = false;
    foreach ($array_config as $config_name => $config_value) {
        $sql = "UPDATE " . NV_CONFIG_GLOBALTABLE . " SET config_value = :config_value 
            WHERE lang = :lang AND module = :module AND config_name = :config_name";
        $sth = $db->prepare($sql);
        $sth->bindValue(':lang', NV_LANG_DATA);
        $sth->bindValue(':module', $module_name);
        $sth->bindValue(':config_name', $config_name);
        $sth->bindValue(':config_value', $config_value);

        try {
            if ($sth->execute()) {
                $status = true;
            }
        } catch (PDOException $e) {
            $status = false;
            error_log("Error updating config: " . $e->getMessage());
        }
    }

    if ($status) {
        $message = $lang_module['config_updated'];
        $message_type = 'success';
    } else {
        $message = $lang_module['config_failed'];
        $message_type = 'danger';
    }
}

if ($nv_Request->isset_request('sync_elastic', 'post')) {
    try {
        $config_query = $db->query('SELECT config_name, config_value FROM ' . NV_CONFIG_GLOBALTABLE . ' 
            WHERE module = ' . $db->quote($module_name) . ' AND lang = ' . $db->quote(NV_LANG_DATA));
        $elastic_config = [];
        while ($row = $config_query->fetch()) {
            $elastic_config[$row['config_name']] = $row['config_value'];
        }

        if ($elastic_config['use_elastic']) {
            $client = ClientBuilder::create()
                ->setHosts([$elastic_config['elas_host'] . ':' . $elastic_config['elas_port']])
                ->setBasicAuthentication($elastic_config['elas_user'], $elastic_config['elas_pass'])
                ->setSSLVerification(false)
                ->build();

            $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE elastic = 0';
            $result = $db->query($sql);
            
            $updated_count = 0;
            while ($row = $result->fetch()) {
                $params = [
                    'index' => $module_data,
                    'id'    => $row['file_id'],
                    'body'  => [
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
                    ]
                ];
                
                $client->index($params);
                
                $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                    SET elastic = ' . NV_CURRENTTIME . ' 
                    WHERE file_id = ' . $row['file_id'];
                $db->exec($update_sql);
                
                $updated_count++;
            }
            
            $message = sprintf($lang_module['sync_elastic_success'], $updated_count);
            $message_type = 'success';
        }
    } catch (Exception $e) {
        $message = $lang_module['sync_elastic_failed'] . ': ' . $e->getMessage();
        $message_type = 'danger';
        error_log($lang_module['error_sync_elastic'] . $e->getMessage());
    }
}

$array_config = [];
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