<?php

namespace NukeViet\Module\fileserver\Api;

use NukeViet\Api\Api;
use NukeViet\Api\ApiResult;
use NukeViet\Api\IApi;
use PDO;
use NukeViet\Files\Upload;

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
        global $nv_Request, $db, $user_info, $global_config, $lang_global;


        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['excel_file'])) {
            $this->result->setError()
                ->setCode('1006')
                ->setMessage('Không có file Excel du?c t?i lên.');
 
        } else {
            $excelTmpPath = $_FILES['excel_file']['tmp_name'];
            $excelSize = $_FILES['excel_file']['size'] ?? 0;
            $excelError = $_FILES['excel_file']['error'] ?? UPLOAD_ERR_NO_FILE;
            $lev = $nv_Request->get_int("lev", "get,post", 0);

            if ($excelError !== UPLOAD_ERR_OK) {
                $this->result->setError()
                    ->setCode('1001')
                    ->setMessage('L?i khi t?i lên file Excel: ' . $excelError);
            } elseif ($excelSize > NV_UPLOAD_MAX_FILESIZE) {
                $this->result->setError()
                    ->setCode('1002')
                    ->setMessage('File Excel vu?t quá kích thu?c cho phép.');
                file_put_contents($log_file, "Step 5: Kích thu?c vu?t quá - $excelSize > " . NV_UPLOAD_MAX_FILESIZE . "\n", FILE_APPEND);
            } elseif (!file_exists($excelTmpPath) || !is_readable($excelTmpPath)) {
                $this->result->setError()
                    ->setCode('1009')
                    ->setMessage('File Excel t?m không t?n t?i ho?c không d?c du?c: ' . $excelTmpPath);
            } else {
                try {
                    if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
                        throw new \Exception("Thu vi?n PhpSpreadsheet không du?c cài d?t.");
                    }

                    $objSpreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelTmpPath);
                    $sheetNames = $objSpreadsheet->getSheetNames();
                    $importedSheets = [];


                    $sheet = $objSpreadsheet->getSheet(0);
                    $this->importSheetData($sheet, $lev, $importedSheets);

                    foreach ($sheetNames as $sheetIndex => $sheetName) {
                        if ($sheetIndex == 0) continue;

                        if (!in_array($sheetName, $importedSheets)) {
                            $sheet = $objSpreadsheet->getSheet($sheetIndex);

                            $sql = "SELECT file_id, file_path FROM " . NV_PREFIXLANG . "_fileserver_files 
                                    WHERE file_name = :file_name AND is_folder = 1 AND lev = :lev";
                            $stmt = $db->prepare($sql);
                            $stmt->bindParam(':file_name', $sheetName, PDO::PARAM_STR);
                            $stmt->bindParam(':lev', $lev, PDO::PARAM_INT);
                            $stmt->execute();
                            $parent = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($parent) {
                                $this->importSheetData($sheet, $parent['file_id'], $importedSheets, $parent['file_path']);
                            }
                        }
                    }

                    $this->result->setSuccess()
                        ->setMessage('Import danh sách file/folder t? Excel thành công.');
                } catch (\Exception $e) {
                    $this->result->setError()
                        ->setCode('1007')
                        ->setMessage('L?i khi d?c file Excel: ' . $e->getMessage());
                }
            }
        }

        $result_data = $this->result->getResult();
    }

    private function importSheetData($sheet, $parent_id, &$importedSheets, $parent_path = '/uploads/fileserver')
    {
        global $db, $global_config, $lang_global, $module_data, $user_info;

        $log_file = NV_ROOTDIR . '/data/tmp/import-file/api_debug.log';
        file_put_contents($log_file, "ImportSheetData: B?t d?u - Parent ID: $parent_id, Parent Path: $parent_path\n", FILE_APPEND);

        $Totalrow = $sheet->getHighestRow();
        $uploadDir = NV_ROOTDIR . $parent_path;

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $this->result->setError()
                    ->setCode('1008')
                    ->setMessage('Không th? t?o thu m?c luu tr?: ' . $uploadDir);
                return;
            }
        }

        for ($i = 5; $i <= $Totalrow; $i++) {
            $real_path = $sheet->getCell('C' . $i)->getValue();

            if (!empty($real_path)) {
                $file_name = basename($real_path);
                $file_path = $parent_path . '/' . $file_name;
                $full_path = NV_ROOTDIR . $file_path;
                $is_folder = (pathinfo($file_name, PATHINFO_EXTENSION) == '') ? 1 : 0;

                $file_size = 0;

                if ($is_folder) {
                    if (!file_exists($full_path)) {
                        mkdir($full_path, 0777, true);
                    }
                } else {
                    if (!file_exists($full_path)) {
                        file_put_contents($full_path, ''); // T?o file r?ng
                    }
                }

                $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files 
                        (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                        VALUES (:file_name, :file_path, :file_size, :uploaded_by, :is_folder, :created_at, :lev)";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
                $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
                $stmt->bindParam(':file_size', $file_size, PDO::PARAM_INT);
                $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
                $stmt->bindValue(':is_folder', $is_folder, PDO::PARAM_INT);
                $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindValue(':lev', $parent_id, PDO::PARAM_INT);
                $stmt->execute();

                $file_id = $db->lastInsertId();
                $this->updateAlias($file_id, $file_name);
                $this->updatePerm($file_id);
                $this->updateLog($parent_id);

                if ($is_folder && !in_array($file_name, $importedSheets)) {
                    $sub_sheet = $sheet->getParent()->getSheetByName($file_name);
                    if ($sub_sheet) {
                        $importedSheets[] = $file_name;
                        $this->importSheetData($sub_sheet, $file_id, $importedSheets, $file_path);
                    }
                }
            }
        }
    }

    private function updateAlias($file_id, $file_name)
    {
        global $db, $module_data;
        $alias = change_alias($file_name . '_' . $file_id);
        $sql = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET alias = :alias WHERE file_id = :file_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':alias', $alias, PDO::PARAM_STR);
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function updatePerm($file_id)
    {
        global $db, $module_data;
        $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions 
                (file_id, p_group, p_other, updated_at) 
                VALUES (:file_id, :p_group, :p_other, :updated_at)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->bindValue(':p_group', '1', PDO::PARAM_INT);
        $stmt->bindValue(':p_other', '1', PDO::PARAM_INT);
        $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function updateLog($lev)
    {
        global $db;

        $stats = $this->calculateFileFolderStats($lev);

        $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_fileserver_logs 
                (lev, total_files, total_folders, total_size, log_time) 
                VALUES (:lev, :total_files, :total_folders, :total_size, :log_time)
                ON DUPLICATE KEY UPDATE 
                  total_files = :update_files, 
                  total_folders = :update_folders, 
                  total_size = :update_size';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
        $stmt->bindValue(':total_files', $stats['files'], PDO::PARAM_INT);
        $stmt->bindValue(':total_folders', $stats['folders'], PDO::PARAM_INT);
        $stmt->bindValue(':total_size', $stats['size'], PDO::PARAM_INT);
        $stmt->bindValue(':log_time', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':update_files', $stats['files'], PDO::PARAM_INT);
        $stmt->bindValue(':update_folders', $stats['folders'], PDO::PARAM_INT);
        $stmt->bindValue(':update_size', $stats['size'], PDO::PARAM_INT);
        $stmt->execute();
    }

    private function calculateFileFolderStats($lev)
    {
        global $db, $module_data;

        $total_files = 0;
        $total_folders = 0;
        $total_size = 0;

        $sql = "SELECT file_id, is_folder, file_size FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :lev AND status = 1";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($files as $file) {
            if ($file['is_folder'] == 1) {
                $total_folders++;
                $folder_stats = $this->calculateFileFolderStats($file['file_id']);
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