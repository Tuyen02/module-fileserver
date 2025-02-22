<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}
define('NV_CONSOLE_DIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME))));

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 and lev = 0';
$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll();

if ($nv_Request->isset_request('submit', 'post')) {
    /*
     * Lưu dữ liệu vào file excel
     */
    // Bỏ time limit
    set_time_limit(0);
    // kiểm tra Library
    if (!is_dir(NV_ROOTDIR . '/vendor/phpoffice/phpspreadsheet')) {
        trigger_error('No phpspreadsheet lib. Run command &quot;composer require phpoffice/phpspreadsheet&quot; to install phpspreadsheet', 256);
    }

    // Đặt tên file, đường dẫn
    // Loại file lưu
    $excel_ext = 'xlsx';
    // đặt tên file excel
    $file_folder = 'export-file';
    $file_folder_path = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/' . $file_folder;

    // xử lý xóa dữ liệu cũ trước khi tạo mới
    if (file_exists($file_folder_path)) {
        $check = nv_deletefile($file_folder_path, true);
        if ($check[0] != 1) {
            $error = $check[1];
        }
    }

    //tạo thư mục
    if (empty($error)) {
        $check = nv_mkdir(NV_ROOTDIR . '/' . NV_TEMP_DIR, $file_folder);
        if ($check[0] != 1) {
            $error = $check[1];
        }
    }

    $page_title = 'Xuất excel';
    $module_name = 'fileserver';

    // Ghi dữ liệu vào file
    if (empty($error)) {
        if ($sys_info['ini_set_support']) {
            set_time_limit(0);
            ini_set('memory_limit', '1028M');
        }

        // lấy dữ liệu

        // Tạo dòng tiêu đề
        $arr_header_row = [
            'STT',
            'Tên File',
            'Đường dẫn',
            'Kích thước',
            'Người tải lên',
            'Ngày tải lên',
            'Là thư mục',
            'Trạng thái',
        ];
        // bắt đầu in từ ô
        $title_char_from = 'A';
        $title_number_from = 4;


        // style tiêu đề
        $styleTitleArray = [
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => '006100'
                ]
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ],

            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => [
                    'rgb' => 'C6EFCE'
                ]
            ]
        ];
        // lấy ô cuối cùng
        $title_char_to = get_cell_code_to($title_char_from, $arr_header_row);
        $title_number_to = $title_number_from;
        if (empty($title_char_to)) {
            $title_char_to = 'A';
        }
        // style table
        $styleTableArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '000000'
                    ]
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOTTED,
                    'color' => [
                        'rgb' => '000000'
                    ]
                ]
            ],
            'font' => [
                'name' => 'Time New Roman'
            ]
        ];

        // gọi thư viện zip file
        $tmp_file = $file_folder_path . '/report_' . date('d/m/Y H:i:s', NV_CURRENTTIME) . '.zip';
        $zip = new PclZip($tmp_file);

        $templatePath = NV_CONSOLE_DIR . '/export_excel/template2.xlsx';
        if (!file_exists($templatePath)) {
            die('Template file does not exist.');
        }

        // Tạo đối tượng objPHPExcel load template
        $objPHPExcel = IOFactory::load($templatePath); //load template mẫu

        // Setting a spreadsheet’s metadata
        $objPHPExcel->getProperties()->setCreator('NukeViet CMS');
        $objPHPExcel->getProperties()->setLastModifiedBy('NukeViet CMS');
        $objPHPExcel->getProperties()->setTitle($page_title . time());
        $objPHPExcel->getProperties()->setSubject($page_title . time());
        $objPHPExcel->getProperties()->setDescription($page_title);
        $objPHPExcel->getProperties()->setKeywords($page_title);
        $objPHPExcel->getProperties()->setCategory($module_name);

        $objWorksheet = $objPHPExcel->getActiveSheet();

        // Rename sheet
        $objWorksheet->setTitle('Main');
        // Set page orientation and size
        $objWorksheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $objWorksheet->getPageSetup()
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
        $objWorksheet->getPageSetup()->setHorizontalCentered(true);
        $objWorksheet->getPageSetup()
            ->setRowsToRepeatAtTopByStartAndEnd(1, 3);

        //xử lý tiêu đề cho file excel
        $style_title = [
            'font' => [
                'bold' => true,
                'size' => 14
            ]
        ];
        $objWorksheet->setCellValue('A1', 'Danh sách file tải lên')
            ->getStyle('A1')
            ->applyFromArray($style_title);
        // in tiêu đề
        $objWorksheet->fromArray(
            $arr_header_row, // The data to set
            null, // Array values with this value will not be set
            $title_char_from . $title_number_from // Top left coordinate of the worksheet range where
            // we want to set these values (default is A1)
        );
        $objWorksheet->getStyle($title_char_from . $title_number_from . ':' . $title_char_to . $title_number_to)
            ->applyFromArray($styleTitleArray);


        $i = 4; // bắt đầu từ dòng số 4
        $stt = 0;
        $row_id = 0;
        // in từng dòng dữ liệu vào file excel
        $data_sbj = $db->query($sql);

        while ($_data2 = $data_sbj->fetch()) {
            $i++;
            $stt++;
            $row_id++;
            // bắt đầu in từ ô
            $table_char_from = $title_char_from;

            // các dữ liệu row kết quả
            $objWorksheet->setCellValue($table_char_from++ . $i, $stt);
            $objWorksheet->setCellValue($table_char_from++ . $i, $_data2['file_name']);
            $objWorksheet->setCellValue($table_char_from++ . $i, $_data2['file_path']);
            if ($_data2['is_folder'] == 1) {
                $objWorksheet->setCellValue($table_char_from++ . $i, number_format(calculateFolderSize($_data2['file_id']) / 1024, 2) . ' KB');
            } else {
                $objWorksheet->setCellValue($table_char_from++ . $i, $_data2['file_size'] ? number_format($_data2['file_size'] / 1024, 2) . ' KB' : '--');
            }
            $sql = 'SELECT username, first_name, last_name FROM ' . NV_USERS_GLOBALTABLE . ' WHERE userid = ' . $_data2['uploaded_by'];
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $user = $stmt->fetch();

            $username = $user['last_name'] . ' ' . $user['first_name'] . ' (' . $user['username'] . ')';
            $objWorksheet->setCellValue($table_char_from++ . $i, $username);
            $objWorksheet->setCellValue($table_char_from++ . $i, date('d/m/Y H:i:s', $_data2['created_at']));
            $type = ($_data2['is_folder'] == 1) ? 'Thư mục' : 'Tệp tin';
            $objWorksheet->setCellValue($table_char_from++ . $i, $type);
            $status = ($_data2['status'] == 1) ? 'Hoạt động' : 'Không hoạt động';
            $objWorksheet->setCellValue($table_char_from++ . $i, $status);

            $objWorksheet->getRowDimension($i)->setRowHeight(20);
            if ($_data2['is_folder'] == 1) {
                $folderSheet = $objPHPExcel->createSheet();
                $folderSheet->setTitle($_data2['file_name']);
                $folderSheet->fromArray(
                    $arr_header_row,
                    null,
                    $title_char_from . $title_number_from
                );
                $folderSheet->getStyle($title_char_from . $title_number_from . ':' . $title_char_to . $title_number_to)
                    ->applyFromArray($styleTitleArray);

                // Ghi dữ liệu của thư mục vào sheet mới
                $folderFiles = $db->query('SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE lev = ' . $_data2['file_id'])->fetchAll();
                $j = 4; // bắt đầu từ dòng số 4
                $folder_stt = 0;
                foreach ($folderFiles as $folderFile) {
                    $j++;
                    $folder_stt++;
                    $table_char_from = $title_char_from;

                    $folderSheet->setCellValue($table_char_from++ . $j, $folder_stt);
                    $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_name']);
                    $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_path']);
                    if ($folderFile['is_folder'] == 1) {
                        $folderSheet->setCellValue($table_char_from++ . $j, number_format(calculateFolderSize($folderFile['file_id']) / 1024, 2) . ' KB');
                    } else {
                        $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_size'] ? number_format($folderFile['file_size'] / 1024, 2) . ' KB' : '--');
                    }
                    $sql = 'SELECT username, first_name, last_name FROM ' . NV_USERS_GLOBALTABLE . ' WHERE userid = ' . $_data2['uploaded_by'];
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    $user = $stmt->fetch();

                    $username = $user['last_name'] . ' ' . $user['first_name'] . ' (' . $user['username'] . ')';

                    $folderSheet->setCellValue($table_char_from++ . $j, $username);
                    $folderSheet->setCellValue($table_char_from++ . $j, date('d/m/Y H:i:s', $folderFile['created_at']));
                    $type = ($folderFile['is_folder'] == 1) ? 'Thư mục' : 'Tệp tin';
                    $folderSheet->setCellValue($table_char_from++ . $j, $type);
                    $status = ($folderFile['status'] == 1) ? 'Hoạt động' : 'Không hoạt động';
                    $folderSheet->setCellValue($table_char_from++ . $j, $status);

                    $folderSheet->getRowDimension($j)->setRowHeight(20);
                }
                // style table cho sheet mới
                $folderSheet->getStyle('A4:H' . $j)
                    ->applyFromArray($styleTableArray);
                // auto size cho sheet mới
                $folderSheet->getColumnDimension('A')->setWidth(5);
                $folderSheet->getColumnDimension('B')->setWidth(50);
                $folderSheet->getColumnDimension('C')->setWidth(50);
                $folderSheet->getColumnDimension('D')->setWidth(15);
                $folderSheet->getColumnDimension('E')->setWidth(40);
                $folderSheet->getColumnDimension('F')->setWidth(30);
                $folderSheet->getColumnDimension('G')->setWidth(15);
                $folderSheet->getColumnDimension('H')->setWidth(15);
            }
        }
        // style table
        $objWorksheet->getStyle('A4:H' . $i)
            ->applyFromArray($styleTableArray);
        // auto size
        $objWorksheet->getColumnDimension('A')->setWidth(5);
        $objWorksheet->getColumnDimension('B')->setWidth(50);
        $objWorksheet->getColumnDimension('C')->setWidth(50);
        $objWorksheet->getColumnDimension('D')->setWidth(15);
        $objWorksheet->getColumnDimension('E')->setWidth(40);
        $objWorksheet->getColumnDimension('F')->setWidth(30);
        $objWorksheet->getColumnDimension('G')->setWidth(15);
        $objWorksheet->getColumnDimension('H')->setWidth(15);

        // lưu file
        $file_path = $file_folder_path . '/ssssss' . $key . '.' . $excel_ext;

        $objWriter = IOFactory::createWriter($objPHPExcel, ucfirst($excel_ext));
        $objWriter->save($file_path);
        $zip->add($file_path, PCLZIP_OPT_REMOVE_PATH, $file_folder_path);
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);

        $download = new NukeViet\Files\Download($file_path, $file_folder_path, 'report_' . date('d/m/Y', NV_CURRENTTIME) . '.' . $excel_ext);
        $download->download_file();
    }
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);

    $sql = 'SELECT file_path, file_name, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = :file_id';
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
            $zipFilePath = '/data/tmp/' . $zipFileName;
            $zipFullPath = NV_ROOTDIR . $zipFilePath;

            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFullPath, ZipArchive::CREATE) === TRUE) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($file_path),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($file_path) + 1);
                        $zipArchive->addFile($filePath, $relativePath);
                    } else {
                        $relativePath = substr($name, strlen($file_path) + 1);
                        $zipArchive->addEmptyDir($relativePath);
                    }
                }

                $zipArchive->close();

                if (file_exists($zipFullPath)) {
                    $zip = $zipFullPath;
                }
            }
        } elseif (pathinfo($file_path, PATHINFO_EXTENSION) === 'zip') {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        } else {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        }

        if (!empty($zip) && file_exists($zip)) {
            $downloadPath = ($is_folder == 1) ? '/data/tmp/' : '/uploads/fileserver/';
            $_download = new NukeViet\Files\Download($zip, NV_ROOTDIR . $downloadPath, basename($zip), true, 0);
            $_download->download_file();
        }
    }
}

$stt = 1;
$xtpl = new XTemplate('export.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op);
$xtpl->assign('MODULE_DATA', $module_data);

foreach ($result as $row) {
    $row['stt'] = $stt++;
    $row['url_download'] = NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=export&amp;file_id=' . $row['file_id'] . '&download=1';
    $row['created_at'] = date('d/m/Y', $row['created_at']);
    if ($row['is_folder'] == 1) {
        $row['file_size'] = number_format(calculateFolderSize($row['file_id']) / 1024, 2) . ' KB';
    } else {
        $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / 1024, 2) . ' KB' : '--';
    }
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
