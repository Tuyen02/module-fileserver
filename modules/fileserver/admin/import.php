<?php
if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}
use PhpOffice\PhpSpreadsheet\IOFactory;

$error = '';
$success = '';
$admin_info['allow_files_type'] = ['xlsx', 'xls'];
global $module_name;
$page_title = $lang_module['import_file'];
$tmp_dir = '/data/tmp/';
$import_folder = 'import-file';
$import_dir = $tmp_dir . $import_folder;

function downloadFromUrl($fileUrl, $dir = './data/tmp/import-file') {
    if (!file_exists($dir)) {
        mkdir($dir, 777, true);
    }

    if (preg_match('/\/file\/d\/(.+?)(\/|$)/', $fileUrl, $matches)) {
        $fileId = $matches[1];
        $fileUrl = "https://drive.google.com/uc?export=download&id=$fileId";
    } else {
        return false;
    }

    $ch = curl_init($fileUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);

    if (stripos($header, 'Content-Type: text/html') !== false && preg_match('/confirm=([a-zA-Z0-9]+)/', $body, $matches)) {
        $confirmCode = $matches[1];
        $fileUrl .= "&confirm=$confirmCode";
        return downloadFromUrl($fileUrl, $dir);
    }

    $filename = basename($fileUrl);
    if (preg_match('/Content-Disposition: .*filename=["\']?(.+?)["\']/i', $header, $matches)) {
        $filename = $matches[1];
    }

    $filePath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    file_put_contents($filePath, $body);

    return file_exists($filePath) ? $filePath : false;
}

function importSheetData($sheet, $parent_id, &$importedSheets, $parent_path = '/uploads/fileserver', $base_dir = '') {
    global $db;
    $Totalrow = $sheet->getHighestRow();

    for ($i = 5; $i <= $Totalrow; $i++) {
        $file_name = $sheet->getCell('B' . $i)->getValue();
        $drive_url = $sheet->getCell('C' . $i)->getValue();
        if (empty($file_name)) continue;

        $file_path = $parent_path . '/' . $file_name;
        $full_path = NV_ROOTDIR . $file_path;
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $is_folder = ($ext == '') ? 1 : 0;
        $file_size = 0;

        if (!empty($drive_url) && !$is_folder) {
            $downloaded_path = downloadFromUrl($drive_url, NV_ROOTDIR . $parent_path);
            if ($downloaded_path) {
                $file_size = filesize($downloaded_path);
                if (basename($downloaded_path) !== $file_name) {
                    rename($downloaded_path, $full_path);
                }
            }
        }

        if ($is_folder && !file_exists($full_path)) {
            mkdir($full_path, 777, true);
        }

        $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_fileserver_files (file_name, file_path, file_size, uploaded_by, created_at, is_folder, lev) 
                VALUES (:file_name, :file_path, :file_size, :uploaded_by, :created_at, :is_folder, :lev)';
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->bindParam(':file_size', $file_size, PDO::PARAM_INT);
        $uploaded_by = 1;
        $stmt->bindParam(':uploaded_by', $uploaded_by, PDO::PARAM_INT);
        $created_at = NV_CURRENTTIME;
        $stmt->bindParam(':created_at', $created_at, PDO::PARAM_INT);
        $stmt->bindParam(':is_folder', $is_folder, PDO::PARAM_INT);
        $stmt->bindParam(':lev', $parent_id, PDO::PARAM_INT);
        $stmt->execute();

        $file_id = $db->lastInsertId();
        updateAlias($file_id, $file_name);
        updatePerm($file_id);
        updateLog($parent_id, 'import', $file_id);

        if ($is_folder && !in_array($file_name, $importedSheets)) {
            $sub_sheet = $sheet->getParent()->getSheetByName($file_name);
            if ($sub_sheet) {
                $importedSheets[] = $file_name;
                importSheetData($sub_sheet, $file_id, $importedSheets, $file_path, $base_dir);
            }
        }
    }
}

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['excel_file']) && is_uploaded_file($_FILES['excel_file']['tmp_name'])) {
    $file_extension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);
    if (!in_array($file_extension, ['xlsx', 'xls'])) {
        $error = 'Chỉ hỗ trợ file Excel (.xlsx hoặc .xls).';
    } else {
        $upload_dir = NV_ROOTDIR . $import_dir;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 777, true);
        }

        $excel_path = $upload_dir . '/' . $_FILES['excel_file']['name'];
        move_uploaded_file($_FILES['excel_file']['tmp_name'], $excel_path);

        try {
            $objPHPExcel = IOFactory::load($excel_path);
            $sheetNames = $objPHPExcel->getSheetNames();
            $importedSheets = [];

            $sheet = $objPHPExcel->getSheet(0);
            importSheetData($sheet, 0, $importedSheets);

            foreach ($sheetNames as $sheetIndex => $sheetName) {
                if ($sheetIndex == 0) continue;

                if (!in_array($sheetName, $importedSheets)) {
                    $sheet = $objPHPExcel->getSheet($sheetIndex);

                    $sql = 'SELECT file_id, file_path FROM ' . NV_PREFIXLANG . '_fileserver_files_files WHERE file_name = ' . $db->quote($sheetName) . ' AND is_folder = 1 AND lev = 0';
                    $parent = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

                    if ($parent) {
                        importSheetData($sheet, $parent['file_id'], $importedSheets, $parent['file_path']);
                    }
                }
            }
            $success = $lang_module['import_success'];
        } catch (Exception $e) {
            $error = $lang_module['error'] . $e->getMessage();
        }

        unlink($excel_path);
    }
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $path = '/themes/default/images/fileserver/';
    $sample_file = 'import_file.xlsx';
    $file_path = NV_ROOTDIR . $path . $sample_file;
    if (file_exists($file_path)) {
        $download = new NukeViet\Files\Download($file_path, NV_ROOTDIR . $path, $sample_file, true, 0);
        $download->download_file();
    } else {
        $error = $lang_module['error_file_not_found'];
    }
}

$xtpl = new XTemplate('import.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op);
$xtpl->assign('URL_DOWNLOAD', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&download=1');

if (!empty($error)) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}

if (!empty($success)) {
    $xtpl->assign('SUCCESS', $success);
    $xtpl->parse('main.success');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';