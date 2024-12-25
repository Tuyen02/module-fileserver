<?php

namespace NukeViet\Module\fileserver\Api;

use NukeViet\Api\Api;
use NukeViet\Api\ApiResult;
use NukeViet\Api\IApi;
use PDO;

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE')) {
    die('Stop!!!');
}

class AddFile implements IApi
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
        global $nv_Request, $db, $user_info, $lang_module;
        $base_dir = '/uploads/fileserver';
        $full_dir = NV_ROOTDIR . $base_dir;
        $base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=fileserver';
        $page_url = $base_url;
        $module_data = 'fileserver';
        $status = 'error';

        $name_f = $nv_Request->get_title("name_f", "post", '');
        $type = $nv_Request->get_int("type", "post", 0); //1 =  folder, 0 file
        if(!empty($array_op)){
            preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
            $lev = $m[2];
            $file_id = $m[2];
        }else{
            $lev = $nv_Request->get_int("lev", "get,post", 0);
        }
        if ($lev > 0) {
            $base_dir = $db->query("SELECT file_path FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = " . $lev)->fetchColumn();
            $full_dir = NV_ROOTDIR . $base_dir;
            $page_url .= '&amp;lev=' . $lev;
            $parentFileType = $this->checkIfParentIsFolder($db, $lev);
            if ($type == 0 && $parentFileType == 0) {
                $status = 'error';
                $this->result->setError()
                    ->setCode('1001')
                    ->setMessage('Error: khong the tao file con trong file');
                    return $this->result->getResult();
            }

            if ($type == 1 && $parentFileType == 0) {
                $status = 'error';
                $this->result->setError()
                    ->setCode('1001')
                    ->setMessage('Error: khong the tao file con trong file');
                    return $this->result->getResult();
            }
        }

        if (!empty($name_f)) {
            $existingFile = $db->query("SELECT COUNT(*) FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_name = " . $db->quote($name_f) . "AND status = 1 AND lev = " . intval($lev))->fetchColumn();
            if ($existingFile > 0) {
                $this->result->setError()
                    ->setCode('1002')
                    ->setMessage('Error: File da ton tai');
                return $this->result->getResult();
            }

            $file_path = $base_dir . '/' . $name_f;
            $sql = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                    VALUES (:file_name, :file_path, :uploaded_by, :is_folder, :created_at, :lev)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $name_f, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
            $stmt->bindParam(':is_folder', $type, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

            if ($type == 1) {
                //tao folder
                $check_dir = nv_mkdir($full_dir, $name_f);
                $status = $check_dir[0] == 1 ? 'success' : 'error';
                $this->result->set('message', $check_dir[1]);
            } else {
                $this->result->set('message', 'khong tao duoc file');
                //tao file
                $_dir = file_put_contents($full_dir . '/' . $name_f, '');
                if (isset($_dir)) {
                    $status = 'success';
                } else {
                    $status = 'error';
                    $this->result->setError()
                        ->setCode('1004')
                        ->setMessage('Error: khong the tao file');
                    return $this->result->getResult();
                }
            }

            if ($status == 'success') {
                $exe = $stmt->execute();
                $file_id = $db->lastInsertId();
                $this->updateAlias($file_id, $name_f);
                $sql1 = "INSERT INTO " . NV_PREFIXLANG . '_' . $module_data . "_permissions (file_id, p_group, p_other, updated_at) 
                    VALUES (:file_id, :p_group, :p_other, :updated_at)";
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $exe = $stmta->execute();
                $this->updateLog($lev);
                $this->result->setSuccess();
                $this->result->set('message', 'file da duoc tao');
            } else {
                $this->result->setError()
                    ->setCode('1003')
                    ->setMessage('Error: khong the tao file');
            }
        }
        return $this->result->getResult();
    }

    public function checkIfParentIsFolder($db, $lev)
    {
        global $lang_module, $module_data;
        $stmt = $db->query("SELECT is_folder FROM " . NV_PREFIXLANG . '_' ."fileserver_files WHERE file_id = " . intval($lev));
        if ($stmt) {
            return $stmt->fetchColumn();
        } else {
            error_log($lang_module["Lỗi truy vấn trong checkIfParentIsFolder với lev: "] . intval($lev));
            return 0;
        }
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
