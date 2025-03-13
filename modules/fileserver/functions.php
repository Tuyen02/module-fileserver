<?php

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}

define('NV_IS_MOD_FILESERVER', true);

require 'vendor/autoload.php';
use Elastic\Elasticsearch\ClientBuilder;

$config_elastic = [
    'elas_host' => 'https://localhost',
    'elas_port' => '9200',
    'elas_user' => 'elastic',
    'elas_pass' => 'vSml*ViLoSh1OGM1KxXk'
];

$db_host = 'localhost';         // Địa chỉ MySQL
$db_name = 'nukeviet_demo';     // Tên database
$db_user = 'root';              // Tên người dùng MySQL
$db_pass = '';

// Kiểm tra config trước
if (!isset($config_elastic) || !is_array($config_elastic)) {
    die("Cấu hình Elasticsearch không hợp lệ");
}

try {
    // Khởi tạo Elasticsearch client
    $client = ClientBuilder::create()
        ->setHosts([$config_elastic['elas_host'] . ':' . $config_elastic['elas_port']])
        ->setBasicAuthentication($config_elastic['elas_user'], $config_elastic['elas_pass'])
        ->setSSLVerification(false)
        ->build();

    // Kết nối database
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $db = new PDO($dsn, $db_user, $db_pass, $options);

    // Thực hiện query
    $query = $db->query('SELECT * FROM nv4_vi_fileserver_files WHERE status = 1 ORDER BY file_id ASC LIMIT 100');
    if ($query === false) {
        throw new Exception("Query thất bại: " . implode(' ', $db->errorInfo()));
    }

    $rows = $query->fetchAll();
    
    if (empty($rows)) {
        echo "Không có dữ liệu để đẩy lên Elasticsearch.\n";
        exit;
    }

    foreach ($rows as $row) {
        $params = [
            'index' => 'fileserver',
            'id' => $row['file_id'],
            'body' => [
                'file_id' => $row['file_id'],
                'file_name' => $row['file_name'],
                'alias' => $row['alias'],
                'file_path' => $row['file_path'],
                'file_size' => $row['file_size'],
                'uploaded_by' => $row['uploaded_by'],
                'created_at' => isset($row['created_at']) ? date('c', $row['created_at']) : null,
                'updated_at' => isset($row['updated_at']) ? date('c', $row['updated_at']) : null,
                'is_folder' => $row['is_folder'],
                'status' => $row['status'],
                'lev' => $row['lev'],
                'view' => $row['view'],
                'share' => $row['share'],
                'compressed' => $row['compressed']
            ]
        ];

        try {
            $response = $client->index($params);
        } catch (Exception $e) {
            echo "Lỗi khi thêm dữ liệu cho file_id {$row['file_id']}: " . $e->getMessage() . "\n";
            continue; // Tiếp tục với bản ghi tiếp theo thay vì die
        }
    }

} catch (PDOException $e) {
    die("Lỗi kết nối Database: " . $e->getMessage());
} catch (Exception $e) {
    die("Lỗi kết nối Elasticsearch: " . $e->getMessage());
}

if (!empty($array_op)) {
    preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
    $lev = $m[2];
    $file_id = $m[2];
} else {
    $lev = $nv_Request->get_int('lev', 'get,post', 0);
}

$config_value = $module_config[$module_name]['group_admin_fileserver'];
$config_value_array = explode(',', $config_value);

if (defined('NV_IS_SPADMIN') || is_array($user_info['in_groups']) && array_intersect($user_info['in_groups'], $config_value_array)) {
    $arr_per = array_column($db->query('SELECT p_group, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE p_group > 1')->fetchAll(), 'p_group', 'file_id');
} else {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}
function updateAlias($file_id, $file_name)
{
    global $db, $module_data;
    $alias = change_alias($file_name . '_' . $file_id);
    $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET alias=:alias WHERE file_id = :file_id';
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':alias', $alias, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':file_id', $file_id, PDO::PARAM_INT);
    $stmtUpdate->execute();
    return true;
}

function deleteFileOrFolder($fileId)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!is_array($row) || empty($row)) {
        return false;
    }

    $filePath = $row['file_path'];
    $isFolder = $row['is_folder'];
    $fullPath = NV_ROOTDIR . $filePath;
    $lev = $row['lev'];

    $parts = explode('/uploads/fileserver/', $filePath);
    $relativePath = end($parts);
    $relativePath = ltrim($relativePath, '/');

    $newFolderName = $isFolder ? getUniqueFolderName($relativePath, $lev) : basename($relativePath);
    $newRelativePath = $isFolder ? $newFolderName : dirname($relativePath) . '/' . getUniqueFolderName($relativePath, $lev);
    $backupPath = NV_ROOTDIR . '/data/tmp/trash/' . $newRelativePath;

    $backupDir = dirname($backupPath);
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    if (file_exists($fullPath)) {
        if (!rename($fullPath, $backupPath)) {
            return false;
        }
    } else {
        return false;
    }

    $fileSize = $isFolder ? calculateFolderSize($fileId) : filesize($backupPath);

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_trash 
        (file_id, file_name, alias, file_path, file_size, uploaded_by, deleted_at, updated_at, is_folder, status, lev, view, share, compressed) 
        VALUES (:file_id, :file_name, :alias, :file_path, :file_size, :uploaded_by, :deleted_at, :updated_at, :is_folder, :status, :lev, :view, :share, :compressed)';

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->execute([
        ':file_id' => $row['file_id'],
        ':file_name' => $isFolder ? $newFolderName : $row['file_name'],
        ':alias' => $row['alias'],
        ':file_path' => '/data/tmp/trash/' . $newRelativePath,
        ':file_size' => $fileSize,
        ':uploaded_by' => $row['uploaded_by'],
        ':deleted_at' => NV_CURRENTTIME,
        ':updated_at' => $row['updated_at'],
        ':is_folder' => $row['is_folder'],
        ':status' => 0,
        ':lev' => $row['lev'],
        ':view' => $row['view'],
        ':share' => $row['share'],
        ':compressed' => $row['compressed']
    ]);

    if ($isFolder) {
        updateDirectoryStatus($fileId, $newFolderName);
    }

    $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmtDelete->execute();

    return true;
}

function updateDirectoryStatus($parentId, $parentNewName = null)
{
    global $db, $module_data;

    $sqlParent = 'SELECT file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
    $stmtParent = $db->prepare($sqlParent);
    $stmtParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
    $stmtParent->execute();
    $parent = $stmtParent->fetch(PDO::FETCH_ASSOC);

    if (!is_array($parent) || empty($parent)) {
        return false;
    }

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = :lev AND status = 1';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $parentId, PDO::PARAM_INT);
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($files as $file) {
        $fileId = $file['file_id'];
        $filePath = $file['file_path'];
        $isFolder = $file['is_folder'];
        $fullFilePath = NV_ROOTDIR . $filePath;
        $lev = $file['lev'];

        $parts = explode('/uploads/fileserver/', $filePath);
        $relativeFilePath = end($parts);
        $relativeFilePath = ltrim($relativeFilePath, '/');

        if ($parentNewName !== null) {
            $relativeFilePath = $parentNewName . '/' . basename($relativeFilePath);
        }

        $newName = $isFolder ? getUniqueFolderName($relativeFilePath, $lev) : basename($relativeFilePath);
        $newRelativeFilePath = $isFolder ? $newName : $parentNewName . '/' . getUniqueFolderName($relativeFilePath, $lev);
        $backupFilePath = NV_ROOTDIR . '/data/tmp/trash/' . $newRelativeFilePath;

        $fileSize = $isFolder ? calculateFolderSize($fileId) : $file['file_size'];

        $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_trash 
            (file_id, file_name, alias, file_path, file_size, uploaded_by, deleted_at, updated_at, is_folder, status, lev, view, share, compressed) 
            VALUES (:file_id, :file_name, :alias, :file_path, :file_size, :uploaded_by, :deleted_at, :updated_at, :is_folder, :status, :lev, :view, :share, :compressed)';

        $stmtInsert = $db->prepare($sqlInsert);
        $stmtInsert->execute([
            ':file_id' => $file['file_id'],
            ':file_name' => $isFolder ? $newName : $file['file_name'],
            ':alias' => $file['alias'],
            ':file_path' => '/data/tmp/trash/' . $newRelativeFilePath,
            ':file_size' => $fileSize,
            ':uploaded_by' => $file['uploaded_by'],
            ':deleted_at' => NV_CURRENTTIME,
            ':updated_at' => $file['updated_at'],
            ':is_folder' => $file['is_folder'],
            ':status' => 0,
            ':lev' => $file['lev'],
            ':view' => $file['view'],
            ':share' => $file['share'],
            ':compressed' => $file['compressed']
        ]);

        if ($isFolder) {
            updateDirectoryStatus($fileId, $newName);
        }

        $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindValue(':file_id', $fileId, PDO::PARAM_INT);
        $stmtDelete->execute();
    }

    return true;
}
function getUniqueFolderName($baseName, $lev)
{
    global $db, $module_data;

    $baseName = basename($baseName);
    $counter = 0;
    $newName = $baseName;

    do {
        $sql = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash 
                WHERE lev = :lev AND file_name LIKE :file_name';
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmt->bindValue(':file_name', $newName, PDO::PARAM_STR);
        $stmt->execute();
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $counter++;
            $newName = $baseName . '_' . $counter;
        }
    } while ($exists);

    return $newName;
}

function checkIfParentIsFolder($db, $lev)
{
    global $lang_module, $module_data;
    $stmt = $db->query('SELECT is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . intval($lev));
    if ($stmt) {
        return $stmt->fetchColumn();
    } else {
        error_log($lang_module['Lỗi truy vấn trong checkIfParentIsFolder với lev: '] . intval($lev));
        return 0;
    }
}

function compressFiles($fileIds, $zipFilePath)
{
    global $db, $lang_module, $module_data;

    if (empty($fileIds) || !is_array($fileIds)) {
        return ['status' => $lang_module['error'], 'message' => $lang_module['list_invalid']];
    }

    if (file_exists($zipFilePath)) {
        unlink($zipFilePath);
    }

    $zip = new PclZip($zipFilePath);
    $filePaths = [];

    $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
    $sql = "SELECT file_path, file_name FROM " . NV_PREFIXLANG . '_' . $module_data . "_files 
            WHERE file_id IN ($placeholders) AND status = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($fileIds);

    if ($stmt->rowCount() == 0) {
        return ['status' => $lang_module['error'], 'message' => $lang_module['cannot_find_file']];
    }

    while ($row = $stmt->fetch()) {
        $realPath = NV_ROOTDIR . $row['file_path'];
        if (file_exists($realPath)) {
            $filePaths[] = $realPath;
        } else {
            return ['status' => $lang_module['error'], 'message' => $lang_module['f_hasnt_exit'] . $realPath];
        }
    }

    if (count($filePaths) > 0) {
        $return = $zip->add($filePaths, PCLZIP_OPT_REMOVE_PATH, NV_ROOTDIR . '/uploads/fileserver');
        if ($return == 0) {
            return ['status' => $lang_module['error'], 'message' => $lang_module['zip_false'] . $zip->errorInfo(true)];
        }
        return ['status' => $lang_module['success'], 'message' => $lang_module['zip_ok']];
    } else {
        return ['status' => $lang_module['error'], 'message' => $lang_module['file_invalid']];
    }
}

function addToDatabase($dir, $parent_id = 0)
{
    global $module_data, $db;

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $filePath = $dir . '/' . $file;
        $isFolder = is_dir($filePath) ? 1 : 0;
        $fileSize = $isFolder ? 0 : filesize($filePath);
        $created_at = NV_CURRENTTIME;

        $insert_sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                       (file_name, file_path, file_size, is_folder, created_at, lev, compressed) 
                       VALUES (:file_name, :file_path, :file_size, :is_folder, :created_at, :lev, 0)';
        $insert_stmt = $db->prepare($insert_sql);
        $file_name = basename($filePath);
        $relativePath = str_replace(NV_ROOTDIR, '', $filePath);
        $insert_stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
        $insert_stmt->bindParam(':file_path', $relativePath, PDO::PARAM_STR);
        $insert_stmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
        $insert_stmt->bindParam(':is_folder', $isFolder, PDO::PARAM_INT);
        $insert_stmt->bindValue(':created_at', $created_at, PDO::PARAM_INT);
        $insert_stmt->bindParam(':lev', $parent_id, PDO::PARAM_INT);
        $insert_stmt->execute();

        $file_id = $db->lastInsertId();
        updateAlias($file_id, $file_name);

        if ($isFolder) {
            addToDatabase($filePath, $file_id);
        }
    }
}

function calculateFolderSize($folderId)
{
    global $db, $module_data;
    $totalSize = 0;

    $sql = 'SELECT file_id, is_folder, file_size FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = :lev';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $folderId, PDO::PARAM_INT);
    $stmt->execute();
    $files = $stmt->fetchAll();

    foreach ($files as $file) {
        if ($file['is_folder'] == 1) {
            $totalSize += calculateFolderSize($file['file_id']);
        } else {
            $totalSize += $file['file_size'];
        }
    }
    return $totalSize;
}

function calculateFileFolderStats($lev)
{
    global $db, $module_data;

    $total_files = 0;
    $total_folders = 0;
    $total_size = 0;

    $sql = 'SELECT file_id, is_folder, file_size FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = :lev AND status = 1 ';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $lev, PDO::PARAM_INT);
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($files as $file) {
        if ($file['is_folder'] == 1) {
            $total_folders++;
            $folder_stats = calculateFileFolderStats($file['file_id']);
            $total_files += $folder_stats['files'];
            $total_folders += $folder_stats['folders'];
            $total_size += $folder_stats['size'];
        } else {
            $total_files++;
            $total_size += $file['file_size'];
        }
    }
    return [
        'files' => $total_files,
        'folders' => $total_folders,
        'size' => $total_size
    ];
}

function updateLog($lev, $action = '', $value = '')
{
    global $db, $module_data;

    $stats = calculateFileFolderStats($lev);

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_logs 
                  (action, value, lev, total_files, total_folders, total_size, total_files_del, total_folders_del, total_size_del, log_time) 
                  VALUES (:action, :value, :lev, :total_files, :total_folders, :total_size, :total_files_del, :total_folders_del, :total_size_del, :log_time)';

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->bindValue(':action', $action, PDO::PARAM_STR);
    $stmtInsert->bindValue(':value', $value, PDO::PARAM_STR);
    $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_files', $stats['files'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders', $stats['folders'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size', $stats['size'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_files_del', $stats['files_del'] ?? 0, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders_del', $stats['folders_del'] ?? 0, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size_del', $stats['size_del'] ?? 0, PDO::PARAM_INT);
    $stmtInsert->bindValue(':log_time', NV_CURRENTTIME, PDO::PARAM_INT);

    $stmtInsert->execute();
}

function getAllChildFileIds($fileId)
{
    global $module_data, $db;
    $childFileIds = [];
    $sql = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = :file_id and status = 1';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $childFileIds[] = $row['file_id'];
        $childFileIds = array_merge($childFileIds, getAllChildFileIds($row['file_id']));
    }
    return $childFileIds;
}

function buildTree($list)
{
    $tree = [];
    $items = [];
    foreach ($list as $item) {
        $items[$item['file_id']] = $item;
        $items[$item['file_id']]['children'] = [];
    }
    foreach ($items as $item) {
        if ($item['lev'] != 0) {
            $items[$item['lev']]['children'][] = &$items[$item['file_id']];
        } else {
            $tree[] = &$items[$item['file_id']];
        }
    }
    return $tree;
}

function displayTree($tree)
{
    $output = '<ul>';
    foreach ($tree as $node) {
        $output .= '<li><i class="fa ' . getFileIconClass($node) . '"></i> ' . $node['file_name'];
        if (!empty($node['children'])) {
            $output .= displayTree($node['children']);
        }
        $output .= '</li>';
    }
    $output .= '</ul>';
    return $output;
}

function getFileIconClass($file)
{
    $file_icons = [
        'pdf' => 'fa-file-pdf-o',
        'doc' => 'fa-file-word-o',
        'docx' => 'fa-file-word-o',
        'xls' => 'fa-file-excel-o',
        'xlsx' => 'fa-file-excel-o',
        'ppt' => 'fa-file-powerpoint-o',
        'pptx' => 'fa-file-powerpoint-o',
        'jpg' => 'fa-file-image-o',
        'jpeg' => 'fa-file-image-o',
        'png' => 'fa-file-image-o',
        'gif' => 'fa-file-image-o',
        'zip' => 'fa-file-archive-o',
        'rar' => 'fa-file-archive-o',
        '7z' => 'fa-file-archive-o',
        'html' => 'fa-file-code-o',
        'css' => 'fa-file-code-o',
        'js' => 'fa-file-code-o',
        'php' => 'fa-file-code-o',
        'sql' => 'fa-file-code-o',
        'txt' => 'fa-file-text-o',
        'mp3' => 'fa-file-audio-o',
        'wav' => 'fa-file-audio-o',
        'wma' => 'fa-file-audio-o',
        'mp4' => 'fa-file-video-o',
        'avi' => 'fa-file-video-o',
        'flv' => 'fa-file-video-o',
        'mkv' => 'fa-file-video-o',
        'mov' => 'fa-file-video-o',
        'wmv' => 'fa-file-video-o',
        'ps' => 'fa-file-o',
    ];

    $file['compressed'] = isset($file['compressed']) ? $file['compressed'] : 0;
    $file['is_folder'] = isset($file['is_folder']) ? $file['is_folder'] : 0;
    $file['file_name'] = isset($file['file_name']) ? $file['file_name'] : '';

    if ($file['compressed'] != 0) {
        return 'fa-file-archive-o';
    } else {
        if ($file['is_folder']) {
            return 'fa-folder-o';
        } else {
            $extension = pathinfo($file['file_name'], PATHINFO_EXTENSION);
            return isset($file_icons[$extension]) ? $file_icons[$extension] : 'fa-file-o';
        }
    }
}

function updateElasticSearch($client, $action, $file_data) {
    global $db, $module_data;

    try {
        switch ($action) {
            case 'create':
            case 'upload':
                $params = [
                    'index' => 'fileserver',
                    'id' => $file_data['file_id'],
                    'body' => [
                        'file_id' => $file_data['file_id'],
                        'file_name' => $file_data['file_name'],
                        'alias' => $file_data['alias'],
                        'file_path' => $file_data['file_path'],
                        'file_size' => $file_data['file_size'] ?? 0,
                        'uploaded_by' => $file_data['uploaded_by'],
                        'created_at' => date('c', $file_data['created_at']),
                        'updated_at' => date('c', $file_data['updated_at'] ?? $file_data['created_at']),
                        'is_folder' => $file_data['is_folder'],
                        'status' => 1,
                        'lev' => $file_data['lev'],
                        'view' => 0,
                        'share' => 0,
                        'compressed' => $file_data['compressed']
                    ]
                ];
                $client->index($params);
                break;

            case 'delete':
                $params = [
                    'index' => 'fileserver',
                    'id' => $file_data['file_id']
                ];
                $client->delete($params);
                break;

            case 'deleteAll':
                foreach ($file_data['file_ids'] as $file_id) {
                    $params = [
                        'index' => 'fileserver',
                        'id' => $file_id
                    ];
                    $client->delete($params);
                }
                break;

            case 'rename':
                $params = [
                    'index' => 'fileserver',
                    'id' => $file_data['file_id'],
                    'body' => [
                        'doc' => [
                            'file_name' => $file_data['file_name'],
                            'alias' => $file_data['alias'],
                            'file_path' => $file_data['file_path'],
                            'updated_at' => date('c', $file_data['updated_at'])
                        ]
                    ]
                ];
                $client->update($params);
                
                if ($file_data['is_folder']) {
                    $bulk_params = [
                        'body' => []
                    ];
                    $sql = 'SELECT file_id, file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_path LIKE :old_path';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':old_path', $file_data['old_file_path'] . '/%', PDO::PARAM_STR);
                    $stmt->execute();
                    $children = $stmt->fetchAll();
                    
                    foreach ($children as $child) {
                        $new_child_path = str_replace($file_data['old_file_path'], $file_data['file_path'], $child['file_path']);
                        $bulk_params['body'][] = [
                            'update' => [
                                '_index' => 'fileserver',
                                '_id' => $child['file_id']
                            ]
                        ];
                        $bulk_params['body'][] = [
                            'doc' => [
                                'file_path' => $new_child_path,
                                'updated_at' => date('c', NV_CURRENTTIME)
                            ]
                        ];
                    }
                    if (!empty($bulk_params['body'])) {
                        $client->bulk($bulk_params);
                    }
                }
                break;

            case 'compress':
                $params = [
                    'index' => 'fileserver',
                    'id' => $file_data['file_id'],
                    'body' => [
                        'file_id' => $file_data['file_id'],
                        'file_name' => $file_data['file_name'],
                        'alias' => $file_data['alias'],
                        'file_path' => $file_data['file_path'],
                        'file_size' => $file_data['file_size'],
                        'uploaded_by' => $file_data['uploaded_by'],
                        'created_at' => date('c', $file_data['created_at']),
                        'updated_at' => date('c', $file_data['updated_at'] ?? $file_data['created_at']),
                        'is_folder' => 0,
                        'status' => 1,
                        'lev' => $file_data['lev'],
                        'view' => 0,
                        'share' => 0,
                        'compressed' => $file_data['compressed']
                    ]
                ];
                $client->index($params);
                break;

            case 'edit':
                $params = [
                    'index' => 'fileserver',
                    'id' => $file_data['file_id'],
                    'body' => [
                        'doc' => [
                            'file_size' => $file_data['file_size'],
                            'updated_at' => date('c', $file_data['updated_at'])
                        ]
                    ]
                ];
                $client->update($params);
                break;
        }
    } catch (Exception $e) {
        error_log("Error updating Elasticsearch for action $action: " . $e->getMessage());
    }
}

// function pr($a)
// {
//     exit('<pre><code>' . htmlspecialchars(print_r($a, true)) . '</code></pre>');
// }