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
    if ($file['is_folder']) {
        return 'fa fa-folder-o';
    }

    $extension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));

    $iconClasses = [
        'pdf' => 'fa fa-file-pdf-o',
        'doc' => 'fa fa-file-word-o',
        'docx' => 'fa fa-file-word-o',
        'xls' => 'fa fa-file-excel-o',
        'xlsx' => 'fa fa-file-excel-o',
        'ppt' => 'fa fa-file-powerpoint-o',
        'pptx' => 'fa fa-file-powerpoint-o',
        'txt' => 'fa fa-file-text-o',
        'jpg' => 'fa fa-file-image-o',
        'jpeg' => 'fa fa-file-image-o',
        'png' => 'fa fa-file-image-o',
        'gif' => 'fa fa-file-image-o',
        'zip' => 'fa fa-file-archive-o',
        'rar' => 'fa fa-file-archive-o',
        '7z' => 'fa fa-file-archive-o'
    ];

    return isset($iconClasses[$extension]) ? $iconClasses[$extension] : 'fa fa-file-o';
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
    $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET alias=' . $db->quote($alias) . ' WHERE file_id = ' . $file_id;
    $db->query($sqlUpdate);
    return true;
}

function calculateFolderSize($folderId)
{
    global $db, $module_data;

    $sql = 'SELECT SUM(file_size) as total_size 
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
            WHERE lev = ' . $folderId . ' AND status = 1';
    return $db->query($sql)->fetchColumn();
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

    $stmtInsert->execute();
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

function getAllChildFileIds($fileId)
{
    global $module_data, $db;
    $childFileIds = [];
    $sql = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = ' . intval($fileId) . ' and status = 1';
    $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($result as $row) {
        $childFileIds[] = $row['file_id'];
        $childFileIds = array_merge($childFileIds, getAllChildFileIds($row['file_id']));
    }
    return $childFileIds;
}

function deleteFileOrFolder($fileId)
{
    global $db, $module_data, $admin_info, $module_name;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $fileId . ' AND status = 0';
    $row = $db->query($sql)->fetch();

    if (!$row) {
        return false;
    }

    if ($row['is_folder']) {
        $sqlChildren = 'WITH RECURSIVE file_tree AS (
            -- Lấy các con trực tiếp
            SELECT file_id, file_path, lev, is_folder, 1 as level
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files
            WHERE lev = ' . $fileId . ' AND status = 0
            
            UNION ALL
            
            -- Lấy tiếp các con của con
            SELECT f.file_id, f.file_path, f.lev, f.is_folder, ft.level + 1
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
            INNER JOIN file_tree ft ON f.lev = ft.file_id
            WHERE f.status = 0
        )
        SELECT * FROM file_tree ORDER BY level DESC';

        $children = $db->query($sqlChildren)->fetchAll();

        if (!empty($children)) {
            foreach ($children as $child) {
                $childFullPath = NV_ROOTDIR . $child['file_path'];
                if (file_exists($childFullPath)) {
                    if ($child['is_folder']) {
                        if (is_dir($childFullPath)) {
                            $files = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($childFullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            foreach ($files as $fileinfo) {
                                if ($fileinfo->isDir()) {
                                    rmdir($fileinfo->getRealPath());
                                } else {
                                    unlink($fileinfo->getRealPath());
                                }
                            }
                            rmdir($childFullPath);
                        }
                    } else {
                        unlink($childFullPath);
                    }
                }
            }

            $childIds = array_column($children, 'file_id');
            $sqlUpdateChildren = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                SET status = -1,
                                    deleted_at = ' . NV_CURRENTTIME . '
                                WHERE file_id IN (' . implode(',', $childIds) . ')';
            $db->query($sqlUpdateChildren);
        }
    }

    $fullPath = NV_ROOTDIR . $row['file_path'];
    if (file_exists($fullPath)) {
        if ($row['is_folder']) {
            if (is_dir($fullPath)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $fileinfo) {
                    if ($fileinfo->isDir()) {
                        rmdir($fileinfo->getRealPath());
                    } else {
                        unlink($fileinfo->getRealPath());
                    }
                }
                rmdir($fullPath);
            }
        } else {
            unlink($fullPath);
        }
    }

    $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
            SET status = -1,
                deleted_at = ' . NV_CURRENTTIME . '
            WHERE file_id = ' . $fileId;

    if ($db->query($sql)) {
        updateLog($row['lev']);
        nv_insert_logs(NV_LANG_DATA, $module_name, 'Delete', 'ID: ' . $fileId . ' | File: ' . $row['file_name'], $admin_info['userid']);
        return true;
    }

    return false;
}

function restoreFileOrFolder($fileId)
{
    global $db, $module_data, $admin_info, $module_name;
    $base_dir = '/uploads/fileserver';
    $trash_dir = '/data/tmp/fileserver_trash';

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $fileId . ' AND status = 0';
    $row = $db->query($sql)->fetch();

    if (!$row) {
        return false;
    }

    if ($row['is_folder']) {
        $sqlChildren = 'WITH RECURSIVE file_tree AS (
            SELECT file_id, file_path, lev, is_folder, file_name, 1 as level
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files
            WHERE lev = ' . $fileId . ' AND status = 0
            
            UNION ALL
            
            SELECT f.file_id, f.file_path, f.lev, f.is_folder, f.file_name, ft.level + 1
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
            INNER JOIN file_tree ft ON f.lev = ft.file_id
            WHERE f.status = 0
        )
        SELECT * FROM file_tree ORDER BY level ASC';

        $children = $db->query($sqlChildren)->fetchAll();
    }

    $oldPath = $row['file_path'];
    $newPath = str_replace($trash_dir, $base_dir, $oldPath);

    $oldFullPath = NV_ROOTDIR . $oldPath;
    $newFullPath = NV_ROOTDIR . $newPath;

    $parentLev = $row['lev'];
    $parentStatus = $db->query('SELECT status FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $parentLev)->fetchColumn();

    if ($parentStatus === false || $parentStatus == 0) {
        $newPath = str_replace($trash_dir, $base_dir, $oldPath);
        $newFullPath = NV_ROOTDIR . $newPath;
        $row['lev'] = 0;
    }

    $newDir = dirname($newFullPath);
    if (!file_exists($newDir)) {
        mkdir($newDir, 0777, true);
    }

    if (!file_exists($oldFullPath) || !rename($oldFullPath, $newFullPath)) {
        return false;
    }

    $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
            SET status = 1,
                file_path = :new_path,
                lev = :new_lev,
                elastic = 0,
                deleted_at = 0
            WHERE file_id = :file_id AND status = 0';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':new_path', $newPath, PDO::PARAM_STR);
    $stmt->bindValue(':new_lev', $row['lev'], PDO::PARAM_INT);
    $stmt->bindValue(':file_id', $fileId, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        return false;
    }

    if ($row['is_folder'] && !empty($children)) {
        foreach ($children as $child) {
            $childOldPath = $child['file_path'];
            $childNewPath = str_replace($trash_dir, $base_dir, $childOldPath);

            $childOldFullPath = NV_ROOTDIR . $childOldPath;
            $childNewFullPath = NV_ROOTDIR . $childNewPath;

            $childNewDir = dirname($childNewFullPath);
            if (!file_exists($childNewDir)) {
                mkdir($childNewDir, 0777, true);
            }

            if (file_exists($childOldFullPath)) {
                rename($childOldFullPath, $childNewFullPath);
            }

            $sqlUpdateChild = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                             SET status = 1,
                                 file_path = :new_path,
                                 elastic = 0,
                                 deleted_at = 0
                             WHERE file_id = :file_id AND status = 0';
            $stmtChild = $db->prepare($sqlUpdateChild);
            $stmtChild->bindValue(':new_path', $childNewPath, PDO::PARAM_STR);
            $stmtChild->bindValue(':file_id', $child['file_id'], PDO::PARAM_INT);
            $stmtChild->execute();

            nv_insert_logs(NV_LANG_DATA, $module_name, 'Restore', 'ID: ' . $child['file_id'] . ' | File: ' . $child['file_name'], $admin_info['userid']);
        }
    }

    updateLog($row['lev']);
    nv_insert_logs(NV_LANG_DATA, $module_name, 'Restore', 'ID: ' . $fileId . ' | File: ' . $row['file_name'], $admin_info['userid']);

    return true;
}


// function pr($a)
// {
//     exit('<pre><code>' . htmlspecialchars(print_r($a, true)) . '</code></pre>');
// }
