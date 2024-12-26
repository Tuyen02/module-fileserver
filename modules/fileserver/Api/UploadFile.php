<?php

namespace NukeViet\Module\fileserver\Api;

use NukeViet\Api\Api;
use NukeViet\Api\ApiResult;
use NukeViet\Api\IApi;
use PDO;

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE')) {
    die('Stop!!!');
}

class UploadFile implements IApi
{
    private $result;

    public static function getAdminLev()
    {
        return Api::ADMIN_LEV_MOD;
    }

    public static function getCat()
    {
        return 'fileserver';
    }

    public function setResultHander(ApiResult $result)
    {
        $this->result = $result;
    }

    public function execute()
    {
        global $nv_Request, $db, $user_info;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
            $uploadDir = NV_ROOTDIR . '/uploads/fileserver/';
            $fileName = basename($_FILES['file']['name']);
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileSize = $_FILES['file']['size'];
            $fileError = $_FILES['file']['error'];
            $lev = $nv_Request->get_int("lev", "get,post", 0);

            if ($fileError !== UPLOAD_ERR_OK) {
                $this->result->setError()
                    ->setCode('1001')
                    ->setMessage('Lỗi khi tải lên file.');
                return $this->result->getResult();
            }

            if ($fileSize > NV_UPLOAD_MAX_FILESIZE) {
                $this->result->setError()
                    ->setCode('1002')
                    ->setMessage('File vượt quá kích thước cho phép.');
                return $this->result->getResult();
            }

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir)) {
                    $this->result->setError()
                        ->setCode('1003')
                        ->setMessage('Không thể tạo thư mục lưu trữ.');
                    return $this->result->getResult();
                }
            }

            $filePath = $uploadDir . $fileName;
            if (!move_uploaded_file($fileTmpPath, $filePath)) {
                $this->result->setError()
                    ->setCode('1004')
                    ->setMessage('Không thể di chuyển file tới thư mục lưu trữ.');
                return $this->result->getResult();
            }

            $sql = "INSERT INTO " . NV_PREFIXLANG . '_' . "fileserver_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                    VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $filePath, PDO::PARAM_STR);
            $stmt->bindParam(':file_size', $fileSize, PDO::PARAM_INT);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $file_id = $db->lastInsertId();
                $this -> updateAlias($file_id, $fileName);
                $sql1 = "INSERT INTO " . NV_PREFIXLANG . '_' . "fileserver_permissions (file_id, p_group, p_other, updated_at) 
                VALUES (:file_id, :p_group, :p_other, :updated_at)";
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmta->execute();
                $this -> updateLog($lev);
                $this->result->setSuccess()
                    ->setMessage('Tải file lên thành công.')
                    ->set('file_path', $filePath);
            } else {
                $this->result->setError()
                    ->setCode('1005')
                    ->setMessage('Không thể lưu thông tin file vào cơ sở dữ liệu.');
            }
        } else {
            $this->result->setError()
                ->setCode('1006')
                ->setMessage('Không có file được tải lên.');
        }

        return $this->result->getResult();
    }

    public function updateAlias($file_id, $file_name)
    {
        global $db, $module_data;
        $alias = change_alias($file_name . '_' . $file_id);
        $sqlUpdate = "UPDATE " . NV_PREFIXLANG . '_' . "fileserver_files SET alias=:alias WHERE file_id = :file_id";
        $stmtUpdate = $db->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':alias', $alias, PDO::PARAM_INT);
        $stmtUpdate->bindValue(':file_id', $file_id, PDO::PARAM_INT);
        $stmtUpdate->execute();
        return true;
    }

    public function updateLog($lev)
    {
        global $db;

        $stats = $this -> calculateFileFolderStats($lev);

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

    public function calculateFileFolderStats($lev)
    {
        global $db, $module_data;

        $total_files = 0;
        $total_folders = 0;
        $total_size = 0;

        $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . '_' . "fileserver_files WHERE lev = :lev AND status = 1 ";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as $file) {
            if ($file['is_folder'] == 1) {
                $total_folders++;
                $folder_stats = $this -> calculateFileFolderStats($file['file_id']);
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
}
