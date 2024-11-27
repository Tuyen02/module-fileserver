<?php

if (!defined('NV_SYSTEM')) {
    exit('Stop!!');
}

define('NV_IS_MOD_FILESERVER', true);


if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

//13: id của group
//kiểm tra xem user này có trong group hay không?
if (in_array(13, $user_info['in_groups'])) {
    //nếu có thì lấy id những file mà quản trị cho phép xem hoặc sửa
    $arr_per = array_column($db->query("SELECT p_group, file_id FROM `nv4_vi_fileserver_permissions` WHERE p_group > 1")->fetchAll(),'p_group','file_id');
} else {
    //nếu không có thì chuyển hướng ra bên ngoài
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

function deleteFileOrFolder($fileId)
{
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

function updateDirectoryStatus($dir)
{
    global $db;
    $full_dir = NV_ROOTDIR . '/' . $dir;

    $files = scandir($full_dir);

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $dir . '/' . $file;
            $fullFilePath = NV_ROOTDIR . '/' . $filePath;

            if (is_dir($fullFilePath)) {
                updateDirectoryStatus($filePath);
            } else {
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

function checkIfParentIsFolder($db, $lev)
{
    $stmt = $db->query("SELECT is_folder FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . intval($lev));
    if ($stmt) {
        return $stmt->fetchColumn();
    } else {
        error_log("Lỗi truy vấn trong checkIfParentIsFolder với lev: " . intval($lev));
        return null;
    }
}

function compressFiles($fileIds, $zipFilePath)
{
    global $db;

    if (!is_array($fileIds) || empty($fileIds)) {
        return ['status' => 'error', 'message' => 'Danh sách file không hợp lệ: '];
    }

    $zip = new PclZip($zipFilePath);
    $filePaths = [];
    $errors = [];

    $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
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
        return ['status' => 'success', 'message' => count($filePaths) . ' file đã được nén thành công.'];
    } else {
        return ['status' => 'error', 'message' => 'Không có file hợp lệ để nén.'];
    }
}


function addToDatabase($files, $parent_id, $db) {
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

function calculateFolderSize($db, $folderId) {
    $totalSize = 0;

    $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :lev";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $folderId, PDO::PARAM_INT);
    $stmt->execute();
    $files = $stmt->fetchAll();

    foreach ($files as $file) {
        if ($file['is_folder'] == 1) {
            $totalSize += calculateFolderSize($db, $file['file_id']);
        } else {
            $totalSize += $file['file_size'];
        }
    }

    return $totalSize;
}

function updatePermissions($file_id, $permissions) {
    global $db;

    $sql_update = "UPDATE " . NV_PREFIXLANG . "_fileserver_permissions 
                   SET `p_group` = :p_group, p_other = :p_other, updated_at = :updated_at 
                   WHERE file_id = :file_id";
    $update_stmt = $db->prepare($sql_update);
    $update_stmt->bindParam(':p_group', $permissions['p_group']);
    $update_stmt->bindParam(':p_other', $permissions['p_other']);
    $update_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $update_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $update_stmt->execute();

    $sql_children = "SELECT file_id FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :file_id";
    $children_stmt = $db->prepare($sql_children);
    $children_stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $children_stmt->execute();
    $children = $children_stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($children as $child_id) {
        $sql_check = "SELECT permission_id FROM " . NV_PREFIXLANG . "_fileserver_permissions WHERE file_id = :file_id";
        $check_stmt = $db->prepare($sql_check);
        $check_stmt->bindParam(':file_id', $child_id, PDO::PARAM_INT);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $update_stmt->bindParam(':file_id', $child_id, PDO::PARAM_INT);
            $update_stmt->execute();
        } else {
            $sql_insert = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions 
                           (file_id, `p_group`, p_other, updated_at) 
                           VALUES (:file_id, :p_group, :p_other, :updated_at)";
            $insert_stmt = $db->prepare($sql_insert);
            $insert_stmt->bindParam(':file_id', $child_id, PDO::PARAM_INT);
            $insert_stmt->bindParam(':p_group', $permissions['p_group']);
            $insert_stmt->bindParam(':p_other', $permissions['p_other']);
            $insert_stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $insert_stmt->execute();
        }
    }
}