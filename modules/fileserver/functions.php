<?php

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}

define('NV_IS_MOD_FILESERVER', true);

if (in_array($config_value = get_config_value(), $user_info['in_groups'])) {
    $arr_per = array_column($db->query("SELECT p_group, file_id FROM `nv4_vi_fileserver_permissions` WHERE p_group > 1")->fetchAll(), 'p_group', 'file_id');
} else {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}
function get_config_value()
{
    global $db;

    $sql = "SELECT config_value FROM nv4_config WHERE config_name = :config_name";
    $stmt = $db->prepare($sql);
    $config_name = 'group_admin_fileserver';
    $stmt->bindParam(':config_name', $config_name, PDO::PARAM_STR);

    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && isset($row['config_value'])) {
        return $row['config_value'];
    }

    return 0;
}

function deleteFileOrFolder($fileId)
{
    global $db, $module_data;

    $sql = "SELECT * FROM ". NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = :file_id";
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

        $sqlUpdate = "UPDATE ". NV_PREFIXLANG . '_' . $module_data . "_files SET status = 0 WHERE file_id = :file_id";
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':file_id', $fileId, PDO::PARAM_INT);
        $stmtUpdate->execute();
    }

    return true;
}

function updateDirectoryStatus($parentId)
{
    global $db, $module_data;

    $sqlParent = "SELECT file_path FROM ". NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = :file_id";
    $stmtParent = $db->prepare($sqlParent);
    $stmtParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
    $stmtParent->execute();
    $parent = $stmtParent->fetch();

    if (empty($parent)) {
        return false; 
    }

    $parentPath = $parent['file_path'];
    $fullParentPath = NV_ROOTDIR . $parentPath;

    $sql = "SELECT file_id, file_path, is_folder FROM ". NV_PREFIXLANG . '_' . $module_data . "_files WHERE lev = :lev AND status = 1";
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

        $sqlUpdate = "UPDATE ". NV_PREFIXLANG . '_' . $module_data . "_files SET status = 0 WHERE file_id = :file_id";
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

    $sqlUpdateParent = "UPDATE ". NV_PREFIXLANG . '_' . $module_data . "_files SET status = 0 WHERE file_id = :file_id";
    $stmtUpdateParent = $db->prepare($sqlUpdateParent);
    $stmtUpdateParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
    $stmtUpdateParent->execute();

    return true;
}


function checkIfParentIsFolder($db, $lev)
{
    global $lang_module, $module_data;
    $stmt = $db->query("SELECT is_folder FROM ". NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . intval($lev));
    if ($stmt) {
        return $stmt->fetchColumn();
    } else {
        error_log($lang_module["Lỗi truy vấn trong checkIfParentIsFolder với lev: "] . intval($lev));
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

    $placeholders = implode(',', array_fill(0, count($fileIds), '?')); ///
    $sql = "SELECT file_path, file_name FROM ". NV_PREFIXLANG . '_' . $module_data . "_files 
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


function addToDatabase($files, $parent_id, $db)
{
    global $module_data;
    foreach ($files as $file) {
        $isFolder = ($file['folder'] == 1) ? 1 : 0;
        $filePath = str_replace(NV_ROOTDIR, '', $file['filename']);

        $insert_sql = "INSERT INTO ". NV_PREFIXLANG . '_' . $module_data . "_files 
                       (file_name, file_path, file_size, is_folder, lev, compressed) 
                       VALUES (:file_name, :file_path, :file_size, :is_folder, :lev, 0)";
        $insert_stmt = $db->prepare($insert_sql);
        $file_name = basename($file['filename']);
        $insert_stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
        $insert_stmt->bindParam(':file_path', $filePath, PDO::PARAM_STR);
        $insert_stmt->bindParam(':file_size', $file['size'], PDO::PARAM_INT);
        $insert_stmt->bindParam(':is_folder', $isFolder, PDO::PARAM_INT);
        $insert_stmt->bindParam(':lev', $parent_id, PDO::PARAM_INT);
        $insert_stmt->execute();
    }
}

function calculateFolderSize($folderId)
{
    global $db, $module_data;
    $totalSize = 0;

    $sql = "SELECT file_id, is_folder, file_size FROM ". NV_PREFIXLANG . '_' . $module_data . "_files WHERE lev = :lev";
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

    $sql = "SELECT file_id, is_folder, file_size FROM ". NV_PREFIXLANG . '_' . $module_data . "_files WHERE lev = :lev AND status = 1 ";
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
