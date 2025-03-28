<?php

$db_config = [
    'dbhost' => '127.0.0.1',
    'dbname' => 'nukeviet_demo',
    'dbuname' => 'root',
    'dbpass' => ''
];

$db = new PDO(
    "mysql:host={$db_config['dbhost']};dbname={$db_config['dbname']};charset=utf8",
    $db_config['dbuname'],
    $db_config['dbpass']
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$threshold = time() - (30 * 24 * 60 * 60);

$sql = 'UPDATE nv4_vi_fileserver_trash 
        SET status = 0 
        WHERE status != 0 
        AND deleted_at < :threshold';
$stmt = $db->prepare($sql);
$stmt->execute([':threshold' => $threshold]);

$affectedRows = $stmt->rowCount();

if ($affectedRows > 0) {
    error_log("Success! Đã cập nhật $affectedRows bản ghi trong bảng trash về status = 0 (quá 30 ngày).");
} else {
    error_log("Success! Không có bản ghi nào quá 30 ngày cần cập nhật.");
}