<?php

namespace NukeViet\Module\fileserver\Api;

use NukeViet\Api\Api;
use NukeViet\Api\ApiResult;
use NukeViet\Api\IApi;
use PDO;

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE')) {
    die('Stop!!!');
}

class UpdateFile implements IApi
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

        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        $newName = trim($nv_Request->get_title('new_name', 'post', ''));

        $sql = "SELECT * FROM " . NV_PREFIXLANG . '_' . "fileserver_files WHERE file_id =" . $fileId;
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $file = $stmt->fetch();

        if ($file) {
            $oldFilePath = $file['file_path'];
            $oldFullPath = NV_ROOTDIR . '/' . $oldFilePath;

            $newFilePath = dirname($oldFilePath) . '/' . $newName;
            $newFullPath = NV_ROOTDIR . '/' . $newFilePath;

            $childCount = $db->query("SELECT COUNT(*) FROM " . NV_PREFIXLANG . '_' . "fileserver_files WHERE lev = " . $fileId)->fetchColumn();
            if ($file['is_folder'] == 1 && $childCount > 0) {
                $this->result->setError()
                    ->setCode('1002')
                    ->setMessage('Error: khong the doi ten file');
            } else {
                if (rename($oldFullPath, $newFullPath)) {
                    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . '_' . "fileserver_files SET file_name = :new_name,alias=:alias, file_path = :new_path, updated_at = :updated_at WHERE file_id = :file_id";
                    $stmtUpdate = $db->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':new_name', $newName);
                    $stmtUpdate->bindParam(':alias', change_alias($newName . '_' . $fileId));
                    $stmtUpdate->bindParam(':new_path', $newFilePath);
                    $stmtUpdate->bindParam(':file_id', $fileId, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    if ($stmtUpdate->execute()) {
                        $this->result->setSuccess();
                        $this->result->set('message', 'Doi ten file thanh cong');
                    }
                } else {
                    $this->result->setError()
                        ->setCode('1003')
                        ->setMessage('Error: khong the doi cap nhat file');
                }
            }
        } else {
            $this->result->setError()
                ->setCode('1001')
                ->setMessage('Error: file khong ton tai');
        }
        return $this->result->getResult();
    }
}
