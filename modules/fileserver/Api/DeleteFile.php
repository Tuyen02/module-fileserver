<?php

namespace NukeViet\Module\fileserver\Api;

use NukeViet\Api\Api;
use NukeViet\Api\ApiResult;
use NukeViet\Api\IApi;
use PDO;

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE')) {
    die('Stop!!!');
}

class DeleteFile implements IApi
{
    private $result;

    /**
     * @return number
     */
    public static function getAdminLev()
    {
        return Api::ADMIN_LEV_MOD;
    }

    /**
     * @return string
     */
    public static function getCat()
    {
        return 'fileserver';
    }

    /**
     * {@inheritDoc}
     * @see \NukeViet\Api\IApi::setResultHander()
     */
    public function setResultHander(ApiResult $result)
    {
        $this->result = $result;
    }

    /**
     * {@inheritDoc}
     * @see \NukeViet\Api\IApi::execute()
     */
    public function execute()
    {
        global $nv_Request, $db;

        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        if ($fileId > 0) {
            $deleted = $this->deleteFileOrFolder($fileId);
            if ($deleted) {
                $this->result->setSuccess()
                    ->set('message', 'Delete file success');
            } else {
                $this->result->setError()
                    ->setCode('1001')
                    ->setMessage('Error: delete file fail');
            }
        }
        return $this->result->getResult();
    }

    public function deleteFileOrFolder($fileId)
    {
        global $db, $module_data;

        $sql = "SELECT * FROM " . NV_PREFIXLANG .'_'."fileserver_files WHERE file_id = :file_id";
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
            $this->updateDirectoryStatus($fileId);
        } else {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            $sqlUpdate = "UPDATE " . NV_PREFIXLANG .'_'."fileserver_files SET status = 0 WHERE file_id = :file_id";
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->bindValue(':file_id', $fileId, PDO::PARAM_INT);
            $stmtUpdate->execute();
        }

        return true;
    }

    public function updateDirectoryStatus($parentId)
    {
        global $db;

        $sqlParent = "SELECT file_path FROM " . NV_PREFIXLANG .'_'."fileserver_files WHERE file_id = :file_id";
        $stmtParent = $db->prepare($sqlParent);
        $stmtParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
        $stmtParent->execute();
        $parent = $stmtParent->fetch();

        if (empty($parent)) {
            return false;
        }

        $parentPath = $parent['file_path'];
        $fullParentPath = NV_ROOTDIR . $parentPath;

        $sql = "SELECT file_id, file_path, is_folder FROM " . NV_PREFIXLANG .'_'."fileserver_files WHERE lev = :lev AND status = 1";
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

            $sqlUpdate = "UPDATE " . NV_PREFIXLANG .'_'."fileserver_files SET status = 0 WHERE file_id = :file_id";
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

        $sqlUpdateParent = "UPDATE " . NV_PREFIXLANG .'_'."fileserver_files SET status = 0 WHERE file_id = :file_id";
        $stmtUpdateParent = $db->prepare($sqlUpdateParent);
        $stmtUpdateParent->bindParam(':file_id', $parentId, PDO::PARAM_INT);
        $stmtUpdateParent->execute();

        return true;
    }
}
