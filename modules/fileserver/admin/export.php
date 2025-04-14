<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

$page_title = $lang_module['export_title'];

define('NV_CONSOLE_DIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME))));

function getUserCache()
{
    global $db, $module_data;
    $user_cache = [];
    $sql = 'SELECT DISTINCT u.userid, u.username, u.first_name, u.last_name 
            FROM ' . NV_USERS_GLOBALTABLE . ' u 
            INNER JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_files f 
            ON u.userid = f.uploaded_by';
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute();
        while ($user = $stmt->fetch()) {
            $user_cache[$user['userid']] = trim($user['last_name'] . ' ' . $user['first_name'] . ' (' . $user['username'] . ')');
        }
    } catch (PDOException $e) {
        trigger_error('Error fetching users: ' . $e->getMessage(), 256);
    }
    return $user_cache;
}

function createFolderSheet($objPHPExcel, $folderId, $folderName, $user_cache, $arr_header_row, $styleTitleArray, $styleTableArray, $title_char_from, $title_number_from) {
    global $db, $module_data;

    $folderSheet = $objPHPExcel->createSheet();
    $folderSheet->setTitle(substr($folderName, 0, 31));
    $folderSheet->fromArray($arr_header_row, null, $title_char_from . $title_number_from);
    $title_char_to = chr(ord($title_char_from) + count($arr_header_row) - 1);
    $folderSheet->getStyle($title_char_from . $title_number_from . ':' . $title_char_to . $title_number_from)
        ->applyFromArray($styleTitleArray);

    $folderFiles = $db->query('SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = ' . $folderId)->fetchAll();
    $j = 4;
    $folder_stt = 0;

    foreach ($folderFiles as $folderFile) {
        $j++;
        $folder_stt++;
        $table_char_from = $title_char_from;

        $folderSheet->setCellValue($table_char_from++ . $j, $folder_stt);
        $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_name']);
        $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_path']);
        $size = ($folderFile['is_folder'] == 1)
            ? number_format(calculateFolderSize($folderFile['file_id']) / 1024, 2) . ' KB'
            : ($folderFile['file_size'] ? number_format($folderFile['file_size'] / 1024, 2) . ' KB' : '--');
        $folderSheet->setCellValue($table_char_from++ . $j, $size);
        $username = $user_cache[$folderFile['uploaded_by']] ?? 'Unknown';
        $folderSheet->setCellValue($table_char_from++ . $j, $username);
        $folderSheet->setCellValue($table_char_from++ . $j, date('d/m/Y H:i:s', $folderFile['created_at']));
        $folderSheet->setCellValue($table_char_from++ . $j, ($folderFile['is_folder'] == 1) ? 'Thư mục' : 'Tệp tin');
        $folderSheet->setCellValue($table_char_from++ . $j, ($folderFile['status'] == 1) ? 'Hoạt động' : 'Không hoạt động');
        $folderSheet->getRowDimension($j)->setRowHeight(20);

        if ($folderFile['is_folder'] == 1) {
            createFolderSheet($objPHPExcel, $folderFile['file_id'], $folderFile['file_name'], $user_cache, $arr_header_row, $styleTitleArray, $styleTableArray, $title_char_from, $title_number_from);
        }
    }

    $folderSheet->getStyle('A4:H' . $j)->applyFromArray($styleTableArray);
    foreach (['A' => 5, 'B' => 50, 'C' => 50, 'D' => 15, 'E' => 40, 'F' => 30, 'G' => 15, 'H' => 15] as $col => $width) {
        $folderSheet->getColumnDimension($col)->setWidth($width);
    }
}

function exportExcel()
{
    global $db, $module_data, $lang_module, $sys_info, $module_name, $admin_info;
    set_time_limit(0);

    if (!is_dir(NV_ROOTDIR . '/vendor/phpoffice/phpspreadsheet')) {
        trigger_error('No phpspreadsheet lib. Run command "composer require phpoffice/phpspreadsheet" to install', 256);
    }

    $excel_ext = 'xlsx';
    $file_folder = 'export-file';
    $file_folder_path = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . $file_folder;
    $error = '';

    if (file_exists($file_folder_path)) {
        $check = nv_deletefile($file_folder_path, true);
        if (!$check[0])
            $error = $check[1];
    }
    if (empty($error)) {
        $check = nv_mkdir(NV_ROOTDIR . '/' . NV_TEMP_DIR, $file_folder);
        if (!$check[0])
            $error = $check[1];
    }

    if (empty($error)) {
        if ($sys_info['ini_set_support']) {
            ini_set('memory_limit', '1024M');
        }

        $module_name = 'fileserver';
        $user_cache = getUserCache();

        $sql = 'SELECT COUNT(*) as total FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND lev = 0';
        $result = $db->query($sql)->fetch();
        if ($result['total'] == 0) {
            return $error = $lang_module['blank_list'];
        }

        $arr_header_row = ['STT', 'Tên File', 'Đường dẫn', 'Kích thước', 'Người tải lên', 'Ngày tải lên', 'Là thư mục', 'Trạng thái'];
        $title_char_from = 'A';
        $title_number_from = 4;

        $styleTitleArray = [
            'font' => ['bold' => true, 'color' => ['rgb' => '006100']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'C6EFCE']]
        ];

        $styleTableArray = [
            'borders' => [
                'outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                'inside' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED, 'color' => ['rgb' => '000000']]
            ],
            'font' => ['name' => 'Times New Roman']
        ];

        $templatePath = NV_ROOTDIR . '/themes/default/images/fileserver/export_template.xlsx';
        if (!file_exists($templatePath)) {
            error_log('Template file does not exist.');
        }

        $objPHPExcel = IOFactory::load($templatePath);
        $objPHPExcel->getProperties()
            ->setCreator('NukeViet CMS')
            ->setLastModifiedBy('NukeViet CMS')
            ->setTitle($lang_module['export_title'] . time())
            ->setSubject($lang_module['export_title'] . time())
            ->setDescription($lang_module['export_title'])
            ->setKeywords($lang_module['export_title'])
            ->setCategory($module_name);

        $objWorksheet = $objPHPExcel->getActiveSheet();
        $objWorksheet->setTitle('Danh sách gốc');
        $objWorksheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 3);

        $objWorksheet->setCellValue('A1', 'Danh sách file tải lên')
            ->getStyle('A1')
            ->applyFromArray(['font' => ['bold' => true, 'size' => 14]]);

        $objWorksheet->fromArray($arr_header_row, null, $title_char_from . $title_number_from);
        $title_char_to = chr(ord($title_char_from) + count($arr_header_row) - 1);
        $objWorksheet->getStyle($title_char_from . $title_number_from . ':' . $title_char_to . $title_number_from)
            ->applyFromArray($styleTitleArray);

        $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND lev = 0';
        $data_sbj = $db->query($sql);
        $i = 4;
        $stt = 0;

        while ($_data2 = $data_sbj->fetch()) {
            $i++;
            $stt++;
            $table_char_from = $title_char_from;

            $objWorksheet->setCellValue($table_char_from++ . $i, $stt);
            $objWorksheet->setCellValue($table_char_from++ . $i, $_data2['file_name']);
            $objWorksheet->setCellValue($table_char_from++ . $i, $_data2['file_path']);
            $size = ($_data2['is_folder'] == 1)
                ? number_format(calculateFolderSize($_data2['file_id']) / 1024, 2) . ' KB'
                : ($_data2['file_size'] ? number_format($_data2['file_size'] / 1024, 2) . ' KB' : '--');
            $objWorksheet->setCellValue($table_char_from++ . $i, $size);
            $username = $user_cache[$_data2['uploaded_by']] ?? 'Unknown';
            $objWorksheet->setCellValue($table_char_from++ . $i, $username);
            $objWorksheet->setCellValue($table_char_from++ . $i, date('d/m/Y H:i:s', $_data2['created_at']));
            $objWorksheet->setCellValue($table_char_from++ . $i, ($_data2['is_folder'] == 1) ? 'Thư mục' : 'Tệp tin');
            $objWorksheet->setCellValue($table_char_from++ . $i, ($_data2['status'] == 1) ? 'Hoạt động' : 'Không hoạt động');
            $objWorksheet->getRowDimension($i)->setRowHeight(20);

            if ($_data2['is_folder'] == 1) {
                createFolderSheet($objPHPExcel, $_data2['file_id'], $_data2['file_name'], $user_cache, $arr_header_row, $styleTitleArray, $styleTableArray, $title_char_from, $title_number_from);
            }
        }

        $objWorksheet->getStyle('A4:H' . $i)->applyFromArray($styleTableArray);
        foreach (['A' => 5, 'B' => 50, 'C' => 50, 'D' => 15, 'E' => 40, 'F' => 30, 'G' => 15, 'H' => 15] as $col => $width) {
            $objWorksheet->getColumnDimension($col)->setWidth($width);
        }

        $file_path = $file_folder_path . '/report_' . date('Ymd_His') . '.' . $excel_ext;
        $tmp_file = $file_folder_path . '/report_' . date('Ymd_His') . '.zip';
        $zip = new PclZip($tmp_file);

        $objWriter = IOFactory::createWriter($objPHPExcel, ucfirst($excel_ext));
        $objWriter->save($file_path);
        $zip->add($file_path, PCLZIP_OPT_REMOVE_PATH, $file_folder_path);

        updateLog(0);
        nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['export'], $lang_module['export_title'], $admin_info['userid']);


        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);

        $download = new NukeViet\Files\Download($file_path, $file_folder_path, basename($file_path));
        $download->download_file();
        exit;
    }
    return $error;
}

if ($nv_Request->isset_request('submit', 'post')) {
    $error = exportExcel();
}

$base_dir = '/uploads/fileserver/';
$tmp_dir = '/data/tmp/';

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);

    $sql = "SELECT file_path, file_name, is_folder FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch();

    if ($file) {
        $file_path = NV_ROOTDIR . $file['file_path'];
        $file_name = $file['file_name'];
        $is_folder = $file['is_folder'];
        $zip = '';

        if ($is_folder == 1) {
            $zipFileName = $file_name . '.zip';
            $zipFilePath = $tmp_dir . $zipFileName;
            $zipFullPath = NV_ROOTDIR . $zipFilePath;

            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $zipArchive->addEmptyDir($file_name);

                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file_path), RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($files as $name => $fileInfo) {
                    if (!$fileInfo->isDir()) {
                        $fileRealPath = $fileInfo->getRealPath();
                        $relativePath = substr($fileRealPath, strlen($file_path) + 1);
                        $zipArchive->addFile($fileRealPath, $file_name . '/' . $relativePath);
                    }
                }
                $zipArchive->close();

                if (file_exists($zipFullPath)) {
                    $zip = $zipFullPath;
                }
            }
        } elseif (pathinfo($file_path, PATHINFO_EXTENSION) == 'zip') {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        } else {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        }

        if (!empty($zip) && file_exists($zip)) {
            $downloadPath = ($is_folder == 1) ? $tmp_dir : $base_dir;
            $_download = new NukeViet\Files\Download($zip, NV_ROOTDIR . $downloadPath, basename($zip), true, 0);
            $_download->download_file();

            if (file_exists($zipFullPath)) {
                unlink($zipFullPath);
            }
        }
    }
}

$xtpl = new XTemplate('export.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op);
$xtpl->assign('MODULE_DATA', $module_data);

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND lev = 0';
$result = $db->query($sql);
$stt = 1;

while ($row = $result->fetch()) {
    $row['stt'] = $stt++;
    $row['url_download'] = NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=export&file_id=' . $row['file_id'] . '&download=1';
    $row['created_at'] = date('d/m/Y H:i:s', $row['created_at']);
    $row['file_size'] = ($row['is_folder'] == 1)
        ? number_format(calculateFolderSize($row['file_id']) / 1024, 2) . ' KB'
        : ($row['file_size'] ? ($row['file_size'] >= 1048576 ? number_format($row['file_size'] / 1048576, 2) . ' MB' : number_format($row['file_size'] / 1024, 2) . ' KB') : '--');
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

if (!empty($error)) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
