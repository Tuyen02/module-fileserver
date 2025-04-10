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

$allowed_extensions = [
    'doc',
    'txt',
    'docx',
    'pdf',
    'xlsx',
    'xls',
    'jpg',
    'png',
    'gif',
    'jpeg',
    'zip',
    'rar',
    'html',
    'css',
    'js',
    'php',
    'sql',
    'mp3',
    'mp4',
    'ppt',
    'pptx'
];

if (!empty($array_op)) {
    preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
    $lev = $m[2];
    $file_id = $m[2];
} else {
    $lev = $nv_Request->get_int('lev', 'get,post', 0);
}

$config_value = $module_config[$module_name]['group_admin_fileserver'];
$config_value_array = explode(',', $config_value);

$arr_per = [];

if (defined('NV_IS_SPADMIN')) {
    $arr_per = [];
} elseif (isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array))) {
    $arr_per = array_column(
        $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE p_group > 1')->fetchAll(),
        'file_id'
    );

    $arr_full_per = array_column(
        $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE p_group = 3')->fetchAll(),
        'file_id'
    );
} else {
    $arr_per = array_column(
        $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE p_other > 1')->fetchAll(),
        'file_id'
    );

    $arr_full_per = array_column(
        $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE p_other = 3')->fetchAll(),
        'file_id'
    );
}

function updateAlias($file_id, $file_name)
{
    global $db, $module_data;
    $alias = change_alias($file_name . '_' . $file_id);
    $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET alias=:alias WHERE file_id = ' . $file_id;
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':alias', $alias, PDO::PARAM_STR);
    $stmtUpdate->execute();
    return true;
}

function deleteFileOrFolder($fileId)
{
    global $db, $module_data;
    $base_dir = '/uploads/fileserver';
    $trash_dir = '/data/tmp/fileserver_trash';

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $fileId;
    $row = $db->query($sql)->fetch();

    if (!is_array($row) || empty($row)) {
        return false;
    }

    $filePath = $row['file_path'];
    $isFolder = $row['is_folder'];
    $fullPath = NV_ROOTDIR . $filePath;
    $lev = $row['lev'];

    $relativePath = str_replace($base_dir, '', $filePath);
    $newPath = $trash_dir . $relativePath;
    $newFullPath = NV_ROOTDIR . $newPath;

    $newDir = dirname($newFullPath);
    if (!file_exists($newDir)) {
        mkdir($newDir, 0777, true);
    }

    if (file_exists($fullPath)) {
        if (!rename($fullPath, $newFullPath)) {
            return false;
        }
    } else {
        return false;
    }

    if ($isFolder) {
        $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                SET status = 0, 
                    file_path = REPLACE(file_path, :old_base, :new_base),
                    elastic = 0,
                    deleted_at = :deleted_at
                WHERE file_path LIKE :file_path';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':old_base', $base_dir, PDO::PARAM_STR);
        $stmt->bindValue(':new_base', $trash_dir, PDO::PARAM_STR);
        $stmt->bindValue(':file_path', $filePath . '%', PDO::PARAM_STR);
        $stmt->bindValue(':deleted_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->execute();
    }

    $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                  SET status = 0, 
                      file_path = :new_path,
                      elastic = 0,
                      deleted_at = :deleted_at
                  WHERE file_id = :file_id';
    $stmt = $db->prepare($sqlUpdate);
    $stmt->bindValue(':new_path', $newPath, PDO::PARAM_STR);
    $stmt->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->bindValue(':deleted_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->execute();

    updateParentFolderSize($lev);

    return true;
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
    $sql = "SELECT file_path, file_name FROM " . NV_PREFIXLANG . "_{$module_data}_files 
            WHERE file_id IN ({$placeholders}) AND status = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($fileIds);
    $rows = $stmt->fetchAll();

    if (empty($rows)) {
        return ['status' => $lang_module['error'], 'message' => $lang_module['cannot_find_file']];
    }

    foreach ($rows as $row) {
        $realPath = NV_ROOTDIR . $row['file_path'];
        if (file_exists($realPath)) {
            $fileName = iconv('UTF-8', 'Windows-1252//TRANSLIT', $row['file_name']);
            if ($fileName === false) {
                $fileName = $row['file_name'];
            }
            
            $filePaths[] = [
                PCLZIP_ATT_FILE_NAME => $realPath,
                PCLZIP_ATT_FILE_NEW_FULL_NAME => $fileName
            ];
        } else {
            return ['status' => $lang_module['error'], 'message' => $lang_module['f_hasnt_exit'] . $realPath];
        }
    }

    if (count($filePaths) > 0) {
        $return = $zip->create($filePaths);
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

    $sql = 'SELECT file_id, is_folder, file_size FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status =  1 AND lev = ' . $lev;
    $files = $db->query($sql)->fetchAll();

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

    $sql = 'SELECT lev FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $folderId;
    $parentId = $db->query($sql)->fetchColumn();

    if ($parentId > 0) {
        updateParentFolderSize($parentId);
    }
}

function updateLog($lev, $action = '', $value = '')
{
    global $db, $module_data;

    $stats = calculateFileFolderStats($lev);

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_logs 
                  (action, value, lev, total_files, total_folders, total_size, log_time) 
                  VALUES (:action, :value, :lev, :total_files, :total_folders, :total_size, :log_time)';

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->bindValue(':action', $action, PDO::PARAM_STR);
    $stmtInsert->bindValue(':value', $value, PDO::PARAM_STR);
    $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_files', $stats['files'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders', $stats['folders'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size', $stats['size'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':log_time', NV_CURRENTTIME, PDO::PARAM_INT);

    $stmtInsert->execute();
}

function getAllChildFileIds($fileId)
{
    global $module_data, $db;
    $childFileIds = [];
    $sql = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND lev =' . $fileId;
    $result = $db->query($sql)->fetchAll();
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

// function pr($a)
// {
//     exit('<pre><code>' . htmlspecialchars(print_r($a, true)) . '</code></pre>');
// }