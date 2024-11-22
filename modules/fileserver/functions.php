<?php

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}

define('NV_IS_MOD_FILESERVER', true);
if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}
function deleteFileOrFolder($fileId) {
    global $db;

    $sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false; 
    }

    $filePath = $row['file_path'];
    $full_dir = NV_ROOTDIR . $filePath;

    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET status = 0 WHERE file_path = :file_path";
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':file_path', $filePath, PDO::PARAM_STR);
    $stmtUpdate->execute();

    if (is_dir($full_dir)) {
        updateDirectoryStatus($filePath);
    } else {
        if (!unlink($full_dir)) {
            return false;
        }
    }

    return true;
}

function updateDirectoryStatus($dir) {
    global $db;
    $full_dir = NV_ROOTDIR . '/' . $dir;

    $files = scandir($full_dir);

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $dir . '/' . $file;
            $fullFilePath = NV_ROOTDIR . '/' . $filePath;

            if (is_dir($fullFilePath)) {
                updateDirectoryStatus($filePath);
            }else {
                unlink($fullFilePath); 
            }

            $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET status = 0 WHERE file_path = :file_path";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':file_path', $filePath, PDO::PARAM_STR);
            $stmtUpdate->execute();

    
        }
    }
    rmdir($full_dir);
}

function checkIfParentIsFolder($db, $lev) {
    $stmt = $db->query("SELECT is_folder FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . intval($lev));
    if ($stmt) {
        return $stmt->fetchColumn();
    } else {
        error_log("Lỗi truy vấn trong checkIfParentIsFolder với lev: " . intval($lev));
        return null;
    }
}

function compressFiles($files, $zipFilePath) {
    global $db;

    $zip = new PclZip($zipFilePath);

    $filePaths = [];
    $errors = [];

    if (!is_array($files) || empty($files)) {
        return ['status' => 'error', 'message' => 'Danh sách file không hợp lệ.' . $files];
    }

    $filePlaceholders = implode(',', array_fill(0, count($files), '?'));
    $sql = "SELECT file_path, file_name FROM " . NV_PREFIXLANG . "_fileserver_files 
            WHERE file_path IN ($filePlaceholders) AND status = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($files);

    if ($stmt->rowCount() == 0) {
        return ['status' => 'error', 'message' => 'Không tìm thấy file nào.'];
    }

    while ($row = $stmt->fetch()) {
        $realPath = NV_ROOTDIR . $row['file_path'];
        if (file_exists($realPath)) {
            $filePaths[] = $realPath;
        } else {
            $errors[] = "File không tồn tại: " . $realPath;
        }
    }

    foreach ($errors as $error) {
        error_log($error);
    }

    if (count($filePaths) > 0) {
        $return = $zip->add($filePaths, PCLZIP_OPT_REMOVE_PATH, NV_ROOTDIR . '/uploads/fileserver');
        if ($return == 0) {
            return ['status' => 'error', 'message' => 'Có lỗi khi nén file: ' . $zip->errorInfo(true)];
        }
        return ['status' => 'success', 'message' => count($filePaths) . ' file đã được nén thành công'];
    } else {
        return ['status' => 'error', 'message' => 'Không có file hợp lệ để nén'];
    }
}



