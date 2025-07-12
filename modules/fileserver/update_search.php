<?php

// Xac dinh thu muc goc cua site
define('NV_ROOTDIR', pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __file__), PATHINFO_DIRNAME));
define('NV_SYSTEM', true);
require NV_ROOTDIR . '/mainfile.php';

$location = 'update_search.php';
$limit = intval($request_mode->get('limit', 100));

$seperator = ' あ ';
$seperator_replace = ' SpaceKey '; // Do change_alias làm mất ký tự phân cách nên dùng cái này rồi replace lại sau

$waitTimeoutInSeconds = 2;
if ($fp = fsockopen($module_config['fileserver']['elas_host'], $module_config['fileserver']['elas_port'], $errCode, $errStr, $waitTimeoutInSeconds)) {
    // It worked
    $elastic_online = 1;
    fclose($fp);
} else {
    // It didn't work
    $elastic_online = 0;
    echo "Server Elasticsearch didn't work: ";
    echo "ERROR: $errCode - $errStr<br />\n";
}

if ($elastic_online) {
    $hosts = array(
        $module_config['fileserver']['elas_host'] . ':' . $module_config['fileserver']['elas_port']
    );
    $client = Elastic\Elasticsearch\ClientBuilder::create()->setBasicAuthentication($module_config['fileserver']['elas_user'], $module_config['fileserver']['elas_pass'])
        ->setHosts($hosts)
        ->setRetries(0)
        ->build();
    $params = [
        'body' => []
    ];

    echo "Begin fileserver_files \n";
    $array_id = [];
    $query_url = $db->query('SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_file WHERE elastic<= 19 ORDER BY elastic ASC, file_id ASC LIMIT ' . $limit);
    while ($row = $query_url->fetch()) {
        $params['body'][] = [
            'index' => [
                '_index' => 'fileserver',
                '_id' => $row['file_id']
            ]
        ];
        // $row['content'] = change_alias($row['content']);
        // $row['content'] = str_replace('-', ' ', $row['content']);
        // Xử lý lại content search ở đây cho nhanh

        $params['body'][] = $row;
        $array_id[] = $row['id'];
    }
    $query_url->closeCursor();

    if (!empty($params['body'])) {
        $responses = $client->bulk($params)->asArray();
        if (empty($responses['errors'])) {
            $db->query('UPDATE ' . NV_PREFIXLANG . '_' . BIDDING_MODULE . '_row SET elasticsearch=' . NV_CURRENTTIME . '  WHERE id IN (' . implode(',', $array_id) . ')');
            unset($responses['items']);
        }
        echo '<pre>';
        print_r($responses);
        echo '</pre>';
        unset($params, $array_id, $responses);
    } else {
        echo "Ko co du du lieu\n";
    }
}

$time_run = number_format((microtime(true) - NV_START_TIME), 2, '.', '');
die("xong: time_run: " . $time_run . "\n--------------------\n\n");
