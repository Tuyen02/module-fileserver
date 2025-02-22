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
];

define('NV_IS_FILE_ADMIN', true);

function updatePerm($file_id)
{
    global $db, $module_data;
    $sql = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_permissions (file_id, p_group, p_other, updated_at) 
                        VALUES (:file_id, :p_group, :p_other, :updated_at)";
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
    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . '_' . $module_data . "_files SET alias=:alias WHERE file_id = :file_id";
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

    $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE lev = :lev";
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

    $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE lev = :lev AND status = 1 ";
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

function updateLog($lev)
{
    global $db;

    $stats = calculateFileFolderStats($lev);

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_fileserver_logs 
                      (lev, total_files, total_folders, total_size, log_time) 
                      VALUES (:lev, :total_files, :total_folders, :total_size, :log_time)
                      ON DUPLICATE KEY UPDATE 
                        total_files = :update_files, 
                        total_folders = :update_folders, 
                        total_size = :update_size';
    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_files', $stats['files'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_folders', $stats['folders'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':total_size', $stats['size'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':log_time', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmtInsert->bindValue(':update_files', $stats['files'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':update_folders', $stats['folders'], PDO::PARAM_INT);
    $stmtInsert->bindValue(':update_size', $stats['size'], PDO::PARAM_INT);
    $stmtInsert->execute();
}

function importSheetData($sheet, $parent_id, &$importedSheets, $parent_path = '/uploads/fileserver')
{
    global $db, $objPHPExcel;
    $Totalrow = $sheet->getHighestRow();

    for ($i = 5; $i <= $Totalrow; $i++) {
        $real_path = $sheet->getCell('C' . $i)->getValue();
        $file_path = $real_path;

        if (!empty($file_path)) {
            $file_name = basename($file_path);
            $file_path = $parent_path . '/' . $file_name;
            $full_path = NV_ROOTDIR . $file_path;
            $is_folder = pathinfo($file_name, PATHINFO_EXTENSION) == '' ? 1 : 0;

            $file_content = '';
            if (!$is_folder && file_exists($real_path)) {
                $file_content = file_get_contents($real_path);
            }

            $folder_path = NV_ROOTDIR . $file_path;
            if ($is_folder && !file_exists($folder_path)) {
                mkdir($folder_path, 0777, true);
            } else {
                $dir_path = dirname($full_path);
                if (!file_exists($dir_path)) {
                    mkdir($dir_path, 0777, true);
                }
                if (!file_exists($full_path)) {
                    file_put_contents($full_path, $file_content);
                }
            }

            $file_size = file_exists($full_path) ? filesize($full_path) : 0;

            $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . 'fileserver_files (file_name, file_path, file_size, uploaded_by, created_at, is_folder, lev) 
                    VALUES (:file_name, :file_path, :file_size, :uploaded_by, :created_at, :is_folder, :lev)';
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':file_size', $file_size, PDO::PARAM_INT);
            $uploaded_by = 1;
            $stmt->bindParam(':uploaded_by', $uploaded_by, PDO::PARAM_INT);
            $created_at = NV_CURRENTTIME;
            $stmt->bindParam(':created_at', $created_at, PDO::PARAM_INT);
            $stmt->bindParam(':is_folder', $is_folder, PDO::PARAM_INT);
            $stmt->bindParam(':lev', $parent_id, PDO::PARAM_INT);
            $stmt->execute();

            $file_id = $db->lastInsertId();
            updateAlias($file_id, $file_name);
            updatePerm($file_id);
            updateLog($parent_id);

            if ($is_folder && !in_array($file_name, $importedSheets)) {
                $sub_sheet = $objPHPExcel->getSheetByName($file_name);
                if ($sub_sheet) {
                    $importedSheets[] = $file_name;
                    importSheetData($sub_sheet, $file_id, $importedSheets, $file_path);
                }
            }
        }
    }
}

//hàm viết sẵn
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

// function pr($a)
// {
//     exit('<pre><code>' . htmlspecialchars(print_r($a, true)) . '</code></pre>');
// }
