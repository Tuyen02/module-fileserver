<?php

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}

define('NV_IS_MOD_FILESERVER', true);

require 'vendor/autoload.php';

use Elastic\Elasticsearch\ClientBuilder;

$use_elastic = $module_config['fileserver']['use_elastic'];

$client = null;

if ($use_elastic == 1) {
    try {
        $elastic_config = [
            'use_elastic' => $module_config['fileserver']['use_elastic'],
            'elas_host' => $module_config['fileserver']['elas_host'],
            'elas_port' => $module_config['fileserver']['elas_port'],
            'elas_user' => $module_config['fileserver']['elas_user'],
            'elas_pass' => $module_config['fileserver']['elas_pass']
        ];

        if (!isset($elastic_config) || !is_array($elastic_config)) {
            error_log($lang_module['invalid_elastic_code']);
        }
        $client = ClientBuilder::create()
            ->setHosts([$elastic_config['elas_host'] . ':' . $elastic_config['elas_port']])
            ->setBasicAuthentication($elastic_config['elas_user'], $elastic_config['elas_pass'])
            ->setSSLVerification(false)
            ->build();

        if (!$client->indices()->exists(['index' => 'fileserver'])) {
            $client->indices()->create([
                'index' => 'fileserver',
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
        }

        $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE elastic = 0';
        $result = $db->query($sql);
        $hasSynced = false;

        while ($row = $result->fetch()) {
            try {
                $params = [
                    'index' => 'fileserver',
                    'id' => $row['file_id'],
                    'body' => [
                        'file_id' => $row['file_id'],
                        'file_name' => $row['file_name'],
                        'file_path' => $row['file_path'] ?? '',
                        'file_size' => $row['file_size'] ?? 0,
                        'uploaded_by' => $row['uploaded_by'] ?? '',
                        'is_folder' => $row['is_folder'],
                        'status' => $row['status'],
                        'lev' => $row['lev'],
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at'] ?? NV_CURRENTTIME,
                        'compressed' => $row['compressed'] ?? ''
                    ]
                ];
                $client->index($params);

                $client->indices()->refresh(['index' => 'fileserver']);

                $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                               SET elastic = :elastic 
                               WHERE file_id = :file_id';
                $stmt = $db->prepare($update_sql);
                $stmt->bindValue(':elastic', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindValue(':file_id', $row['file_id'], PDO::PARAM_INT);
                $stmt->execute();
            } catch (Exception $e) {
                error_log($lang_module['error_update_elastic'] . $e->getMessage());
            }
        }

        if ($hasSynced) {
            nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=main');
        }
    } catch (Exception $e) {
        error_log($lang_module['error_start_elastic'] . $e->getMessage());
        $use_elastic = 0;
    }
}

$allowed_extensions = ['txt', 'xlsx', 'xls', 'html', 'css'];
$base_dir = '/uploads/fileserver';
$tmp_dir = '/data/tmp/';

if (!empty($array_op)) {
    preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
    $lev = $m[2];
    $file_id = $m[2];
} else {
    $lev = $nv_Request->get_int('lev', 'get,post', 0);
}

updateLog(isset($lev) ? $lev : 0);

$config_value = isset($module_config[$module_name]['group_admin_fileserver']) ? $module_config[$module_name]['group_admin_fileserver'] : '';
$config_value_array = !empty($config_value) ? explode(',', $config_value) : [];

if ($lev > 0 && !defined('NV_IS_SPADMIN')) {
    $sql_permissions = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $lev;
    $permissions = $db->query($sql_permissions)->fetch(PDO::FETCH_ASSOC);

    $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty($config_value_array) && !empty(array_intersect($user_info['in_groups'], $config_value_array));

    $current_permission = $permissions ? ($is_group_user ? $permissions['p_group'] : $permissions['p_other']) : 1;

    if ($current_permission == 1) {
        nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
    }
}

$arr_per = [];
$arr_full_per = [];

if (!defined('NV_IS_SPADMIN')) {
    $sql = 'SELECT file_id, p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions';
    
    if (isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty($config_value_array)) {
        $sql .= ' WHERE p_group >= 2';
    } else {
        $sql .= ' WHERE p_other >= 2';
    }
    
    $result = $db->query($sql)->fetchAll();
    
    foreach ($result as $row) {
        $arr_per[] = $row['file_id'];
        if ((isset($user_info['in_groups']) && $row['p_group'] == 3) || 
            (!isset($user_info['in_groups']) && $row['p_other'] == 3)) {
            $arr_full_per[] = $row['file_id'];
        }
    }
}

function get_user_permission($file_id, $row = array())
{
    global $module_config, $module_name, $user_info, $module_data, $db;

    $current_permission = 1;
    if (defined('NV_IS_SPADMIN')) {
        return 3;
    }

    if (defined('NV_IS_USER')) {
        if (isset($user_info['in_groups']) && is_array($user_info['in_groups'])) {
            if (!empty(array_intersect($user_info['in_groups'], explode(',', $module_config[$module_name]['group_admin_fileserver'])))) {
                $sql = 'SELECT p_group FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
                $result = $db->query($sql);
                $perm = $result->fetch();
                $current_permission = isset($perm['p_group']) ? intval($perm['p_group']) : 1;
            } else if (isset($row['userid']) && $row['userid'] == $user_info['userid']) {
                $current_permission = 3;
            } else {
                $sql = 'SELECT p_group FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
                $result = $db->query($sql);
                $perm = $result->fetch();
                $current_permission = isset($perm['p_group']) ? intval($perm['p_group']) : 1;
            }
        }
    } else {
        $sql = 'SELECT p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
        $result = $db->query($sql);
        $perm = $result->fetch();
        $current_permission = isset($perm['p_other']) ? intval($perm['p_other']) : 1;
    }

    return $current_permission;
}

function updateAlias($file_id, $file_name)
{
    global $db, $module_data, $lang_module;

    $alias = change_alias($file_name . '_' . $file_id);
    $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET alias=:alias WHERE file_id = ' . $file_id;
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':alias', $alias, PDO::PARAM_STR);

    if (!$stmtUpdate->execute()) {
        error_log($lang_module['error_update_alias'] . ' - File ID: ' . $file_id);
        return false;
    }

    return true;

}

function suggestNewName($lev, $baseName, $extension, $is_folder = null) {
    global $db, $module_data;
    $i = 1;
    do {
        $suggestedName = $baseName . '_' . $i;
        if ($extension) {
            $suggestedName .= '.' . $extension;
        }
        $params = [
            ':file_name' => $suggestedName,
            ':lev' => $lev
        ];
        $sql = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND file_name = :file_name AND lev = :lev';
        if ($is_folder !== null) {
            $sql .= ' AND is_folder = :is_folder';
            $params[':is_folder'] = $is_folder;
        }
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $i++;
    } while ($count > 0);
    return $suggestedName;
}

function deleteFileOrFolder($fileId)
{
    global $db, $module_data, $tmp_dir;
    $trash_dir = $tmp_dir . 'fileserver_trash';

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $fileId;
    $row = $db->query($sql)->fetch();

    if (!is_array($row) || empty($row)) {
        return false;
    }

    $filePath = $row['file_path'];
    $isFolder = $row['is_folder'];
    $fullPath = NV_ROOTDIR . $filePath;
    $deleted_at = NV_CURRENTTIME;

    $fileName = basename($filePath);
    $fileInfo = pathinfo($fileName);
    $lev = $row['lev'];

    $baseName = $fileInfo['filename'];
    $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
    $counter = 1;
    $newFileName = $baseName . $extension;
    
    while (file_exists(NV_ROOTDIR . $trash_dir . '/' . $newFileName)) {
        $newFileName = $baseName . ' (' . $counter . ')' . $extension;
        $counter++;
    }

    $newFullPath = NV_ROOTDIR . $trash_dir . '/' . $newFileName;
    $newDir = dirname($newFullPath);
    if (!file_exists($newDir)) {
        mkdir($newDir, 0777, true);
    }

    if (file_exists($fullPath)) {
        if (!rename($fullPath, $newFullPath)) {
            return false;
        }
    }

    $oldParentPath = $fileName;
    $newParentPath = $newFileName;

    if ($isFolder) {
        $sqlChildren = 'WITH RECURSIVE file_tree AS (
            SELECT file_id, file_path, lev, is_folder, file_name, 1 as level
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files
            WHERE lev = ' . $fileId . ' AND status = 1
            
            UNION ALL
            
            SELECT f.file_id, f.file_path, f.lev, f.is_folder, f.file_name, ft.level + 1
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
            INNER JOIN file_tree ft ON f.lev = ft.file_id
            WHERE f.status = 1
        )
        SELECT * FROM file_tree ORDER BY level DESC';
        
        $children = $db->query($sqlChildren)->fetchAll();
        
        foreach ($children as $child) {
            $childPath = $child['file_path'];
            $childFullPath = NV_ROOTDIR . $childPath;
            $childName = basename($childPath);
            $childInfo = pathinfo($childName);
            
            $childBaseName = $childInfo['filename'];
            $childExtension = isset($childInfo['extension']) ? '.' . $childInfo['extension'] : '';
            $childCounter = 1;
            $childNewName = $childBaseName . $childExtension;
            
            while (file_exists(NV_ROOTDIR . $trash_dir . '/' . $childNewName)) {
                $childNewName = $childBaseName . ' (' . $childCounter . ')' . $childExtension;
                $childCounter++;
            }
            
            $childNewFullPath = NV_ROOTDIR . $trash_dir . '/' . $childNewName;
            $childNewDir = dirname($childNewFullPath);
            if (!file_exists($childNewDir)) {
                mkdir($childNewDir, 0777, true);
            }

            if (file_exists($childFullPath)) {
                rename($childFullPath, $childNewFullPath);
            }

            $childNewPath = str_replace('/' . $oldParentPath . '/', '/' . $newParentPath . '/', $childPath);
            
            $sqlUpdateChild = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                             SET status = 0,
                                 deleted_at = :deleted_at,
                                 file_path = :file_path,
                                 file_name = :file_name
                             WHERE file_id = :file_id';
            $stmtChild = $db->prepare($sqlUpdateChild);
            $stmtChild->bindValue(':deleted_at', $deleted_at, PDO::PARAM_INT);
            $stmtChild->bindValue(':file_path', $childNewPath, PDO::PARAM_STR);
            $stmtChild->bindValue(':file_name', $childNewName, PDO::PARAM_STR);
            $stmtChild->bindValue(':file_id', $child['file_id'], PDO::PARAM_INT);
            $stmtChild->execute();
        }
    }

    $newPath = str_replace('/' . $oldParentPath, '/' . $newParentPath, $filePath);
    $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                  SET status = 0, 
                      deleted_at = :deleted_at,
                      elastic = 0,
                      file_path = :file_path,
                      file_name = :file_name
                  WHERE file_id = :file_id';
    $stmt = $db->prepare($sqlUpdate);
    $stmt->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->bindValue(':deleted_at', $deleted_at, PDO::PARAM_INT);
    $stmt->bindValue(':file_path', $newPath, PDO::PARAM_STR);
    $stmt->bindValue(':file_name', $newFileName, PDO::PARAM_STR);
    $stmt->execute();
    
    updateLog($row['lev']);
    updateParentFolderSize($row['lev']);

    return true;
}

function checkIfParentIsFolder($lev)
{
    global $lang_module, $module_data, $db;
    $stmt = $db->query('SELECT is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . intval($lev));
    if ($stmt) {
        return $stmt->fetchColumn();
    } else {
        error_log($lang_module['checkIfParentIsFolder_false'] . intval($lev));
        return 0;
    }
}

function compressFiles($fileIds, $zipFilePath)
{
    global $db, $lang_module, $module_data, $tmp_dir;

    if (empty($fileIds) || !is_array($fileIds)) {
        return ['status' => 'error', 'message' => $lang_module['list_invalid']];
    }

    if (!file_exists($tmp_dir)) {
        mkdir($tmp_dir, 0777, true);
    }

    $tmpZipPath = $tmp_dir . basename($zipFilePath);
    if (file_exists($tmpZipPath)) {
        unlink($tmpZipPath);
    }

    if (!is_writable($tmp_dir)) {
        chmod($tmp_dir, 0777);
    }

    try {
        $zipArchive = new ZipArchive();
        $result = $zipArchive->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            $error = 'Lỗi mở file zip: ';
            switch($result) {
                case ZipArchive::ER_EXISTS:
                    $error .= 'File đã tồn tại';
                    break;
                case ZipArchive::ER_INCONS:
                    $error .= 'File zip không nhất quán';
                    break;
                case ZipArchive::ER_INVAL:
                    $error .= 'Tham số không hợp lệ';
                    break;
                case ZipArchive::ER_MEMORY:
                    $error .= 'Không đủ bộ nhớ';
                    break;
                case ZipArchive::ER_NOENT:
                    $error .= 'Không tìm thấy file';
                    break;
                case ZipArchive::ER_NOZIP:
                    $error .= 'Không phải file zip';
                    break;
                case ZipArchive::ER_OPEN:
                    $error .= 'Không thể mở file';
                    break;
                case ZipArchive::ER_READ:
                    $error .= 'Lỗi đọc file';
                    break;
                case ZipArchive::ER_SEEK:
                    $error .= 'Lỗi tìm kiếm';
                    break;
                default:
                    $error .= 'Lỗi không xác định';
            }
            return ['status' => 'error', 'message' => $lang_module['zip_false'] . ' - ' . $error];
        }

        $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
        $sql = 'SELECT file_path, file_name, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                WHERE file_id IN (' . $placeholders . ') AND status = 1';
        $stmt = $db->prepare($sql);
        
        foreach ($fileIds as $key => $fileId) {
            $stmt->bindValue($key + 1, $fileId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            $zipArchive->close();
            return ['status' => 'error', 'message' => $lang_module['cannot_find_file']];
        }

        $hasFiles = false;
        foreach ($rows as $row) {
            $realPath = NV_ROOTDIR . $row['file_path'];
            if (file_exists($realPath)) {
                $hasFiles = true;
                if ($row['is_folder']) {
                    $zipArchive->addEmptyDir(nv_EncString($row['file_name']));
                    
                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($realPath),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    );

                    foreach ($files as $file) {
                        if (!$file->isDir()) {
                            $filePath = $file->getRealPath();
                            $relativePath = substr($filePath, strlen($realPath) + 1);
                            $zipArchive->addFile($filePath, nv_EncString($row['file_name']) . '/' . $relativePath);
                        }
                    }
                } else {
                    $zipArchive->addFile($realPath, nv_EncString($row['file_name']));
                }
            } else {
                $zipArchive->close();
                return ['status' => 'error', 'message' => $lang_module['f_hasnt_exit'] . $realPath];
            }
        }

        $zipArchive->close();

        if (!$hasFiles) {
            return ['status' => 'error', 'message' => $lang_module['file_invalid']];
        }

        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
        
        if (!rename($tmpZipPath, $zipFilePath)) {
            return ['status' => 'error', 'message' => $lang_module['zip_false'] . ' - Không thể di chuyển file zip'];
        }

        return ['status' => 'success', 'message' => $lang_module['zip_ok']];
        
    } catch (Exception $e) {
        if (isset($zipArchive) && $zipArchive instanceof ZipArchive) {
            $zipArchive->close();
        }
        return ['status' => 'error', 'message' => $lang_module['zip_false'] . ' - ' . $e->getMessage()];
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
                       (file_name, file_path, file_size, is_folder, created_at, lev, compressed, elastic) 
                       VALUES (:file_name, :file_path, :file_size, :is_folder, :created_at, :lev, 0, 0)';
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

    $sql = 'SELECT file_id, is_folder, file_size FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 and lev = ' . $folderId;
    $files = $db->query($sql)->fetchAll();

    foreach ($files as $file) {
        if ($file['is_folder'] == 1) {
            $totalSize += calculateFolderSize($file['file_id']);
        } else {
            $totalSize += intval($file['file_size']);
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

    $sql = 'SELECT file_id, is_folder, file_size FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1';
    if ($lev !== null) {
        $sql .= ' AND lev = ' . $lev;
    }
    $files = $db->query($sql)->fetchAll();

    foreach ($files as $file) {
        if ($file['is_folder'] == 1) {
            $total_folders++;
        } else {
            $total_files++;
        }
        $total_size += intval($file['file_size']);
    }
    return [
        'files' => $total_files,
        'folders' => $total_folders,
        'size' => $total_size
    ];
}

function updateParentFolderSize($folderId)
{
    global $db, $module_data;

    $newSize = calculateFolderSize($folderId);

    $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
            SET file_size = :file_size, updated_at = :updated_at 
            WHERE file_id = :file_id AND is_folder = 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':file_size', $newSize, PDO::PARAM_INT);
    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->bindValue(':file_id', $folderId, PDO::PARAM_INT);
    $stmt->execute();

    updateLog($folderId);

    $sql = 'SELECT lev FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $folderId;
    $parentId = $db->query($sql)->fetchColumn();

    if ($parentId > 0) {
        updateParentFolderSize($parentId);
    }
}

function updateLog($lev)
{
    global $db, $module_data;

    $stats = calculateFileFolderStats($lev);

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_stats 
                  (lev, total_files, total_folders, total_size, log_time) 
                  VALUES (:lev, :total_files, :total_folders, :total_size, :log_time)
                  ON DUPLICATE KEY UPDATE 
                  total_files = VALUES(total_files), 
                  total_folders = VALUES(total_folders), 
                  total_size = VALUES(total_size), 
                  log_time = VALUES(log_time)';

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_files', $stats['files'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders', $stats['folders'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size', $stats['size'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':log_time', NV_CURRENTTIME, PDO::PARAM_INT);

    return $stmtInsert->execute();
}

function getAllChildFileIds($fileId)
{
    global $module_data, $db;
    $childFileIds = [];
    $sql = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND lev =' . $fileId;
    $result = $db->query($sql);
    while ($row = $result->fetch()) {
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
        if ($item['lev'] != 0 && isset($items[$item['lev']])) {
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

function normalizePath($path)
{
    $path = str_replace('\\', '/', $path);

    $path = preg_replace('|/{2,}|', '/', $path);

    $path = str_replace('./', '', $path);

    $parts = array_filter(explode('/', $path), 'strlen');
    $absolutes = array();
    foreach ($parts as $part) {
        if ('.' == $part)
            continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }

    return '/' . implode('/', $absolutes);
}

function getAllFilesAndFolders($folder_id, $base_path) {
    global $db, $module_data;
    
    $items = [];
    
    $sql = 'SELECT f.*, p.p_group, p.p_other 
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
            LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id
            WHERE f.lev = ' . $folder_id . ' AND f.status = 1';
    $result = $db->query($sql);
    
    while ($row = $result->fetch()) {
        $items[] = $row;
        
        if ($row['is_folder'] == 1) {
            $children = getAllFilesAndFolders($row['file_id'], $base_path);
            $items = array_merge($items, $children);
        }
    }
    
    return $items;
}

function checkChildrenPermissions($folder_id)
{
    global $db, $module_data;

    $sql = 'WITH RECURSIVE folder_tree AS (
        SELECT file_id, file_name, lev, is_folder
        FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
        WHERE file_id = ' . $folder_id . ' AND status = 1
        
        UNION ALL
        
        SELECT f.file_id, f.file_name, f.lev, f.is_folder
        FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
        INNER JOIN folder_tree ft ON f.lev = ft.file_id
        WHERE f.status = 1
    )
    SELECT file_id, file_name FROM folder_tree WHERE file_id != ' . $folder_id;

    $result = $db->query($sql);
    $restricted_files = [];

    while ($row = $result->fetch()) {
        $permission = get_user_permission($row['file_id']);
        if ($permission <= 2) {
            $restricted_files[] = $row['file_name'];
        }
    }

    if (!empty($restricted_files)) {
        return true;
    }

    return false;
}

function getParentPermissions($parent_id)
{
    global $db, $module_data;

    if ($parent_id <= 0) {
        return [
            'p_group' => 2,
            'p_other' => 1
        ];
    }

    $sql = 'SELECT p_group, p_other 
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
            WHERE file_id = ' . $parent_id;
    $result = $db->query($sql);
    $row = $result->fetch();

    if ($row) {
        return [
            'p_group' => intval($row['p_group']),
            'p_other' => intval($row['p_other'])
        ];
    }
    return [
        'p_group' => 1,
        'p_other' => 1
    ];

}

function getAllFileIds($parent_id, &$file_ids)
{
    global $db, $module_data;

    $file_ids[] = $parent_id;

    $sql = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
            WHERE lev = ' . $parent_id . ' AND status = 1';
    $result = $db->query($sql);

    while ($row = $result->fetch()) {
        getAllFileIds($row['file_id'], $file_ids);
    }
}

function updatePermissions($parent_id, $p_group, $p_other)
{
    global $db, $module_data;

    $file_ids = [];

    getAllFileIds($parent_id, $file_ids);

    $file_ids = array_unique($file_ids);

    foreach ($file_ids as $file_id) {
        $sql_check = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                     WHERE file_id = ' . $file_id;
        $exists = $db->query($sql_check)->fetchColumn();

        if ($exists) {
            $sql_update = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                          SET p_group = :p_group, 
                              p_other = :p_other, 
                              updated_at = :updated_at 
                          WHERE file_id = :file_id';
            $stmt = $db->prepare($sql_update);
        } else {
            $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                          (file_id, p_group, p_other, updated_at) 
                          VALUES (:file_id, :p_group, :p_other, :updated_at)';
            $stmt = $db->prepare($sql_insert);
        }

        $stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->bindValue(':p_group', $p_group, PDO::PARAM_INT);
        $stmt->bindValue(':p_other', $p_other, PDO::PARAM_INT);
        $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->execute();
    }
}

function buildFolderTree($user_info, $page_url, $is_admin = false, $parent_id = 0) {
    global $db, $module_data, $lang_module;
    $tree = [];
    
    if ($parent_id == 0) {
        $root_node = [
            'file_id' => 0,
            'file_name' => $lang_module['root'], 
            'url' => $page_url . '&root=1',
            'path' => $lang_module['root'],
            'children' => []
        ];
        $tree[] = $root_node;
    }

    $sql = 'SELECT f.*, p.p_group, p.p_other 
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
            LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p 
            ON f.file_id = p.file_id
            WHERE f.lev = ' . $parent_id . ' 
            AND f.is_folder = 1 
            AND f.status = 1 
            ORDER BY f.file_id ASC';
    $result = $db->query($sql);
    $dirs = $result->fetchAll();

    foreach ($dirs as $dir) {
        if (checkPermission($dir, $user_info, $is_admin)) {
            $dir['url'] = $page_url . '&rank=' . $dir['file_id'];
            $dir['path'] = $dir['file_name'];
            $dir['children'] = buildFolderTree( $user_info, $page_url, $is_admin, $dir['file_id']);
            $tree[] = $dir;
        }
    }
    return $tree;
}

function checkPermission($directory, $user_info, $is_admin = false) {
    if ($is_admin) {
        return true;
    }
    if (isset($directory['userid']) && $directory['userid'] == $user_info['userid']) {
        return true;
    }
    if (isset($user_info['in_groups']) && is_array($user_info['in_groups'])) {
        if (isset($directory['p_group']) && $directory['p_group'] >= 2) {
            return true;
        }
    }
    return false;
}

function renderFolderTree($tree) {
    $html = '<ul>';
    foreach ($tree as $node) {
        $html .= '<li data-file-id="' . $node['file_id'] . '" data-path="' . htmlspecialchars($node['path']) . '" data-url="' . $node['url'] . '">';
        $html .= '<span class="folder-name"><i class="fa fa-folder-o" aria-hidden="true"></i> ' . htmlspecialchars($node['file_name']) . '</span>';
        if (!empty($node['children'])) {
            $html .= renderFolderTree($node['children']);
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    return $html;
}

function isValidFileName($filename) {
    $filename = rtrim($filename, " .");
    
    if (empty($filename)) {
        return false;
    }
    
    $pathInfo = pathinfo($filename);
    $name = $pathInfo['filename'];
    
    if (substr($name, -1) === ' ' || substr($name, -1) === '.') {
        return false;
    }
    
    if (!mb_check_encoding($filename, 'UTF-8')) {
        return false;
    }
    
    return true;
}

