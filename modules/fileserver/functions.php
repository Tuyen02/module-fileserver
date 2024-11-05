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

    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET status = 0 WHERE file_path LIKE :file_path";
    $stmtUpdate = $db->prepare($sqlUpdate);
    $stmtUpdate->bindValue(':file_path', $filePath . '%', PDO::PARAM_STR);
    $stmtUpdate->execute();

    if (is_dir($filePath)) {
        deleteDirectory($filePath);
    } else {
        if (!unlink($filePath)) {
            return false;
        }
    }

    return true;
}

function deleteDirectory($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                deleteDirectory($filePath);
            } else {
                unlink($filePath); 
            }
        }
    }
    rmdir($dir);
}



function checkIfParentIsFolder($db, $lev) {
    return $db->query("SELECT is_folder FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . intval($lev))->fetchColumn();
}