<?php


/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE') or !defined('NV_IS_MODADMIN')) {
    exit('Stop!!!');
}
$allow_func = [
    'main',
    'export',
    'import',
    'recycle_bin',
    'config',
];

define('NV_IS_FILE_ADMIN', true);

if (!empty($array_op)) {
    preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
    $lev = $m[2];
    $file_id = $m[2];
} else {
    $lev = $nv_Request->get_int('lev', 'get,post', 0);
}

function get_cell_code_to($cell_char_from = 'A', $arr_header_row = [])
{
    if (preg_match('/[A-z]/', $cell_char_from)) {
        $cell_char_from = strtoupper($cell_char_from);
        $cell_char_int_from = stringtointvalue($cell_char_from);
        $cell_char_int_to = count($arr_header_row) + $cell_char_int_from - 1;
        $cell_char_to = intvaluetostring($cell_char_int_to);
        return $cell_char_to;
    } else {
        return false;
    }
}

function getcolumnrange($min, $max)
{
    $pointer = strtoupper($min);
    $output = [];
    while (positionalcomparison($pointer, strtoupper($max)) <= 0) {
        array_push($output, $pointer);
        $pointer++;
    }
    return $output;
}

function positionalcomparison($a, $b)
{
    $a1 = stringtointvalue($a);
    $b1 = stringtointvalue($b);
    if ($a1 > $b1) {
        return 1;
    } else {
        if ($a1 < $b1) {
            return -1;
        } else
            return 0;
    }
}

function stringtointvalue($str)
{
    $amount = 0;
    $strarra = array_reverse(str_split($str));

    for ($i = 0; $i < strlen($str); $i++) {
        $amount += (ord($strarra[$i]) - 64) * pow(26, $i);
    }
    return $amount;
}

function intvaluetostring($int)
{
    $start = 'A';
    $int = (int) $int;
    for ($i = 0; $i < $int; $i++) {
        $end = $start++;
    }
    return $end;
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

function updatePerm($file_id)
{
    global $db, $module_data;
    $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                        VALUES (:file_id, :p_group, :p_other, :updated_at)';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_STR);
    $stmt->bindValue(':p_group', '1', PDO::PARAM_INT);
    $stmt->bindValue(':p_other', '1', PDO::PARAM_INT);
    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->execute();
    return true;
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

    $sql = 'SELECT file_id, is_folder, file_size FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = :lev AND status = 0';
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
                  (lev, action, value, total_files, total_folders, total_size, total_files_del, total_folders_del, total_size_del, log_time) 
                  VALUES (:lev, :action, :value, :total_files, :total_folders, :total_size, :total_files_del, :total_folders_del, :total_size_del, :log_time)';

    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
    $stmtInsert->bindValue(':action', $action, PDO::PARAM_STR);
    $stmtInsert->bindValue(':value', $value, PDO::PARAM_STR);
    $stmtInsert->bindValue(':total_files', $stats['files'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders', $stats['folders'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size', $stats['size'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_files_del', $stats['files_del'] ?? 0, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders_del', $stats['folders_del'] ?? 0, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size_del', $stats['size_del'] ?? 0, PDO::PARAM_INT);
    $stmtInsert->bindValue(':log_time', NV_CURRENTTIME, PDO::PARAM_INT);

    $stmtInsert->execute();
}

function deleteFileOrFolder($fileId)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false;
    }

    $filePath = $row['file_path'];
    $isFolder = $row['is_folder'];
    $fullPath = NV_ROOTDIR . $filePath;

    if ($isFolder) {
        updateDirectoryStatus($fileId);
    } else {
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET status = 0 WHERE file_id = :file_id';
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':file_id', $fileId, PDO::PARAM_INT);
        $stmtUpdate->execute();
    }

    return true;
}

function updateDirectoryStatus($parentId)
{
    global $db, $module_data;

    $sqlParent = 'SELECT file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
    $stmtParent = $db->prepare($sqlParent);
    $stmtParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
    $stmtParent->execute();
    $parent = $stmtParent->fetch();

    if (empty($parent)) {
        return false;
    }

    $parentPath = $parent['file_path'];
    $fullParentPath = NV_ROOTDIR . $parentPath;

    $sql = 'SELECT file_id, file_path, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = :lev AND status = 0';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $parentId, PDO::PARAM_INT);
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($files as $file) {
        $fileId = $file['file_id'];
        $filePath = $file['file_path'];
        $isFolder = $file['is_folder'];
        $fullFilePath = NV_ROOTDIR . $filePath;

        if ($isFolder) {
            updateDirectoryStatus($fileId);
        } else {
            if (file_exists($fullFilePath)) {
                unlink($fullFilePath);
            }
        }

        $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET status = 0 WHERE file_id = :file_id';
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':file_id', $fileId, PDO::PARAM_INT);
        $stmtUpdate->execute();
    }

    $indexFile = $fullParentPath . '/index.html';
    if (file_exists($indexFile)) {
        unlink($indexFile);
    }

    if (is_dir($fullParentPath)) {
        $isEmpty = count(scandir($fullParentPath)) == 2;
        if ($isEmpty) {
            rmdir($fullParentPath);
        }
    }

    $sqlUpdateParent = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET status = 0 WHERE file_id = :file_id';
    $stmtUpdateParent = $db->prepare($sqlUpdateParent);
    $stmtUpdateParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
    $stmtUpdateParent->execute();

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


function restoreChildItems($parentId, $parentNewPath)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE lev = :lev';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $parentId, PDO::PARAM_INT);
    $stmt->execute();
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($children as $child) {
        $oldChildPath = NV_ROOTDIR . $child['file_path'];
        $relativePath = str_replace('/data/tmp/trash/', '', $child['file_path']);
        $newChildPathBase = $parentNewPath . '/' . basename($relativePath);
        $newChildPath = NV_ROOTDIR . $newChildPathBase;

        if (file_exists($newChildPath)) {
            $newChildPathBase = str_replace(NV_ROOTDIR, '', $newChildPath);
        }

        $childParentDir = dirname($newChildPath);
        if (!file_exists($childParentDir)) {
            mkdir($childParentDir, 0777, true);
        }

        if (file_exists($oldChildPath)) {
            if (!rename($oldChildPath, $newChildPath)) {
                continue;
            }
        }

        $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                      (file_id, file_name, alias, file_path, file_size, uploaded_by, created_at, updated_at, is_folder, status, lev, view, share, compressed) 
                      VALUES (:file_id, :file_name, :alias, :file_path, :file_size, :uploaded_by, :created_at, :updated_at, :is_folder, 1, :lev, :view, :share, :compressed)';
        $stmtInsert = $db->prepare($sqlInsert);
        $stmtInsert->execute([
            ':file_id' => $child['file_id'],
            ':file_name' => $child['file_name'],
            ':alias' => $child['alias'],
            ':file_path' => $newChildPathBase,
            ':file_size' => $child['file_size'],
            ':uploaded_by' => $child['uploaded_by'],
            ':created_at' => NV_CURRENTTIME,
            ':updated_at' => 0,
            ':is_folder' => $child['is_folder'],
            ':lev' => $child['lev'],
            ':view' => $child['view'],
            ':share' => $child['share'],
            ':compressed' => $child['compressed']
        ]);

        $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindValue(':file_id', $child['file_id'], PDO::PARAM_INT);
        $stmtDelete->execute();

        if ($child['is_folder'] == 1) {
            restoreChildItems($child['file_id'], $newChildPathBase);
        }
    }
}

function deletePermanently($fileId)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false;
    }

    $fullPath = NV_ROOTDIR . $row['file_path'];
    if ($row['is_folder'] == 1 && is_dir($fullPath)) {
        nv_deletefile($fullPath, true);
    } elseif (file_exists($fullPath)) {
        unlink($fullPath);
    }

    $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmtDelete->execute();

    return true;
}

function restoreFileOrFolder($fileId)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false;
    }

    $oldPath = NV_ROOTDIR . $row['file_path'];
    $newPathBase = str_replace('/data/tmp/trash/', '/uploads/fileserver/', $row['file_path']);
    $newPath = NV_ROOTDIR . $newPathBase;

    if (file_exists($newPath)) {
        $newPathBase = str_replace(NV_ROOTDIR, '', $newPath);
    }

    $parentDir = dirname($newPath);
    if (!file_exists($parentDir)) {
        mkdir($parentDir, 0777, true);
    }

    if (file_exists($oldPath)) {
        if (!rename($oldPath, $newPath)) {
            return false;
        }
    }

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                  (file_id, file_name, alias, file_path, file_size, uploaded_by, created_at, updated_at, is_folder, status, lev, view, share, compressed) 
                  VALUES (:file_id, :file_name, :alias, :file_path, :file_size, :uploaded_by, :created_at, :updated_at, :is_folder, 1, :lev, :view, :share, :compressed)';
    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->execute([
        ':file_id' => $row['file_id'],
        ':file_name' => $row['file_name'],
        ':alias' => $row['alias'],
        ':file_path' => $newPathBase,
        ':file_size' => $row['file_size'],
        ':uploaded_by' => $row['uploaded_by'],
        ':created_at' => NV_CURRENTTIME,
        ':updated_at' => 0,
        ':is_folder' => $row['is_folder'],
        ':lev' => $row['lev'],
        ':view' => $row['view'],
        ':share' => $row['share'],
        ':compressed' => $row['compressed']
    ]);

    $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmtDelete->execute();

    if ($row['is_folder'] == 1) {
        restoreChildItems($fileId, $newPathBase);
    }

    return true;
}

function purgeOldTrashItems()
{
    global $db, $module_data;

    $threshold = NV_CURRENTTIME - (30 * 24 * 60 * 60);
    $sql = 'SELECT file_id, file_path, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE deleted_at < :threshold';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
    $stmt->execute();
    $oldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deletedFileIds = [];
    foreach ($oldItems as $item) {
        $fullPath = NV_ROOTDIR . $item['file_path'];
        if ($item['is_folder'] == 1 && is_dir($fullPath)) {
            nv_deletefile($fullPath, true);
        } elseif (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindValue(':file_id', $item['file_id'], PDO::PARAM_INT);
        $stmtDelete->execute();

        $deletedFileIds[] = $item['file_id'];
    }

    if (!empty($deletedFileIds)) {
        updateLog(0, 'auto_purge', implode(',', $deletedFileIds));
    }
}

// function pr($a)
// {
//     exit('<pre><code>' . htmlspecialchars(print_r($a, true)) . '</code></pre>');
// }
