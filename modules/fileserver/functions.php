<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}
define('NV_IS_MOD_FILESERVER', true);

function deleteFileOrFolderById($fileId) {
    global $db;

    $sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch();

    if ($file) {
        $file_path = $file['file_path'];

        if ($file['is_folder']) {
            // Xóa thư mục
            if (is_dir($file_path)) {
                rmdir($file_path);
            }
        } else {
            // Xóa file
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $sqlDelete = "DELETE FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindParam(':file_id', $fileId, PDO::PARAM_INT);
        return $stmtDelete->execute();
    }

    return false; 
}