<?php

if (!defined('NV_ROOTDIR')) {
    define('NV_ROOTDIR', str_replace('\\', '/', realpath(dirname(__DIR__, 2))));
}
define('NV_SYSTEM', true);
require NV_ROOTDIR . '/includes/mainfile.php';

$module_name = 'fileserver';
$module_data = 'fileserver';
$module_file = 'fileserver';

$use_elastic = isset($module_config['fileserver']['use_elastic']) ? $module_config['fileserver']['use_elastic'] : 0;

if (!$use_elastic) {
    die("Elasticsearch chưa được bật trong cấu hình module fileserver!\n");
}

require_once NV_ROOTDIR . '/vendor/autoload.php';
use Elastic\Elasticsearch\ClientBuilder;

$elas_host = $module_config['fileserver']['elas_host'];
$elas_port = $module_config['fileserver']['elas_port'];
$elas_user = $module_config['fileserver']['elas_user'];
$elas_pass = $module_config['fileserver']['elas_pass'];

$hosts = [$elas_host . ':' . $elas_port];

try {
    $client = ClientBuilder::create()
        ->setHosts($hosts)
        ->setBasicAuthentication($elas_user, $elas_pass)
        ->setSSLVerification(false)
        ->build();
} catch (Exception $e) {
    die("Không thể kết nối Elasticsearch: " . $e->getMessage() . "\n");
}

$index = 'fileserver';
if (!$client->indices()->exists(['index' => $index])) {
    $client->indices()->create([
        'index' => $index,
        'body' => [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                'properties' => [
                    'file_id' => ['type' => 'keyword'],
                    'file_name' => ['type' => 'text'],
                    'file_path' => ['type' => 'text'],
                    'file_size' => ['type' => 'long'],
                    'uploaded_by' => ['type' => 'keyword'],
                    'is_folder' => ['type' => 'boolean'],
                    'status' => ['type' => 'integer'],
                    'lev' => ['type' => 'integer'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                    'compressed' => ['type' => 'keyword']
                ]
            ]
        ]
    ]);
    echo "Đã tạo index fileserver!\n";
}

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE elastic = 0';
$result = $db->query($sql);
$count = 0;
$error_count = 0;

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

while ($row = $result->fetch()) {
    try {
        $params = [
            'index' => $index,
            'id' => $row['file_id'],
            'body' => [
                'file_id' => $row['file_id'],
                'file_name' => $row['file_name'],
                'file_path' => isset($row['file_path']) ? $row['file_path'] : '',
                'file_size' => isset($row['file_size']) ? $row['file_size'] : 0,
                'uploaded_by' => isset($row['uploaded_by']) ? $row['uploaded_by'] : '',
                'is_folder' => $row['is_folder'],
                'status' => $row['status'],
                'lev' => $row['lev'],
                'created_at' => $row['created_at'],
                'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : NV_CURRENTTIME,
                'compressed' => isset($row['compressed']) ? $row['compressed'] : ''
            ]
        ];
        $client->index($params);
        $client->indices()->refresh(['index' => $index]);
        $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET elastic = :elastic WHERE file_id = :file_id';
        $stmt = $db->prepare($update_sql);
        $stmt->bindValue(':elastic', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':file_id', $row['file_id'], PDO::PARAM_INT);
        $stmt->execute();
        $count++;
        echo "Đã đồng bộ file_id: {$row['file_id']} ({$row['file_name']})\n";
    } catch (Exception $e) {
        $error_count++;
        echo "Lỗi khi đồng bộ file_id: {$row['file_id']} - " . $e->getMessage() . "\n";
    }
}

if ($is_ajax) {
    echo "Đồng bộ: Thành công $count, Lỗi $error_count";
    exit();
}

echo "\nĐồng bộ hoàn tất! Thành công: $count, Lỗi: $error_count\n";