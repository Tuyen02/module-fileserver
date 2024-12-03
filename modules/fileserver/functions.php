<?php

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}

define('NV_IS_MOD_FILESERVER', true);

if (in_array(13, $user_info['in_groups'])) {
    $arr_per = array_column($db->query("SELECT p_group, file_id FROM `nv4_vi_fileserver_permissions` WHERE p_group > 1")->fetchAll(), 'p_group', 'file_id');
} else {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

function deleteFileOrFolder($fileId)
{
    global $db;

    $sql = "SELECT file_path, is_folder FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false; 
    }

    $filePath = $row['file_path'];
    $isFolder = $row['is_folder'];

    if ($isFolder) {
        deleteDirectoryContents($fileId);
    } else {
        $sqlDeleteFile = "DELETE FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
        $stmtDeleteFile = $db->prepare($sqlDeleteFile);
        $stmtDeleteFile->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        $stmtDeleteFile->execute();
    }

    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET status = 0 WHERE file_id = :file_id";
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    $fullPath = NV_ROOTDIR . '/' . $filePath;
    if (is_dir($fullPath)) {
        rmdir($fullPath); 
    } else {
        unlink($fullPath);
    }

    if ($isFolder) {
        $sqlDeleteFolder = "DELETE FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
        $stmtDeleteFolder = $db->prepare($sqlDeleteFolder);
        $stmtDeleteFolder->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        $stmtDeleteFolder->execute();
    }
    return true;
}

function deleteDirectoryContents($folderId)
{
    global $db;

    $sql = "SELECT file_id, file_path, is_folder FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :lev";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $folderId, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        $fileId = $item['file_id'];
        $filePath = $item['file_path'];
        $isFolder = $item['is_folder'];

        if ($isFolder) {
            deleteDirectoryContents($fileId);
        } else {
            $sqlDeleteFile = "DELETE FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
            $stmtDeleteFile = $db->prepare($sqlDeleteFile);
            $stmtDeleteFile->bindParam(':file_id', $fileId, PDO::PARAM_INT);
            $stmtDeleteFile->execute();
        }

        $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET status = 0 WHERE file_id = :file_id";
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $fullPath = NV_ROOTDIR . '/' . $filePath;
        if (is_dir($fullPath)) {
            rmdir($fullPath); 
        } else {
            unlink($fullPath);
        }
    }
}

function checkIfParentIsFolder($db, $lev)
{
    $stmt = $db->query("SELECT is_folder FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . intval($lev));
    if ($stmt) {
        return $stmt->fetchColumn();
    } else {
        error_log("Lỗi truy vấn trong checkIfParentIsFolder với lev: " . intval($lev));
        return 0;
    }
}

function compressFiles($fileIds, $zipFilePath)
{
    global $db;

    if ( empty($fileIds) || !is_array($fileIds)) {
        return ['status' => 'error', 'message' => 'Danh sách file không hợp lệ: '];
    }

    if (file_exists($zipFilePath)) {
        unlink($zipFilePath);
    }

    $zip = new PclZip($zipFilePath);
    $filePaths = [];
    $errors = '';

    $placeholders = implode(',', array_fill(0, count($fileIds), '?'));///
    $sql = "SELECT file_path, file_name FROM " . NV_PREFIXLANG . "_fileserver_files 
            WHERE file_id IN ($placeholders) AND status = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($fileIds);

    if ($stmt->rowCount() == 0) {
        return ['status' => 'error', 'message' => 'Không tìm thấy file nào hợp lệ với file_id đã chọn.'];
    }

    while ($row = $stmt->fetch()) {
        $realPath = NV_ROOTDIR . $row['file_path'];
        if (file_exists($realPath)) {
            $filePaths[] = $realPath;
        } else {
            $errors = "File không tồn tại: " . $realPath;
        }
    }

    if (count($filePaths) > 0) {
        $return = $zip->add($filePaths, PCLZIP_OPT_REMOVE_PATH, NV_ROOTDIR . '/uploads/fileserver');
        if ($return == 0) {
            return ['status' => 'error', 'message' => 'Có lỗi khi nén file: ' . $zip->errorInfo(true)];
        }
        return ['status' => 'success', 'message' => count($filePaths) . ' file đã được nén thành công.'];
    } else {
        return ['status' => 'error', 'message' => 'Không có file hợp lệ để nén.'];
    }
}


function addToDatabase($files, $parent_id, $db)
{
    foreach ($files as $file) {
        $isFolder = ($file['folder'] == 1) ? 1 : 0;
        $filePath = str_replace(NV_ROOTDIR, '', $file['filename']);

        $insert_sql = 'INSERT INTO ' . NV_PREFIXLANG . '_fileserver_files 
                       (file_name, file_path, file_size, is_folder, lev, compressed) 
                       VALUES (:file_name, :file_path, :file_size, :is_folder, :lev, 0)';
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

function calculateFolderSize( $folderId)
{
    global $db;
    $totalSize = 0;

    $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :lev";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $folderId, PDO::PARAM_INT);
    $stmt->execute();
    $files = $stmt->fetchAll();

    foreach ($files as $file) {
        if ($file['is_folder'] == 1) {
            $totalSize += calculateFolderSize( $file['file_id']);
        } else {
            $totalSize += $file['file_size'];
        }
    }
    return $totalSize;
}

function calculateFileFolderStats($lev)
{
    global $db;

    $total_files = 0;
    $total_folders = 0;
    $total_size = 0;

    $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :lev AND status = 1 ";
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






