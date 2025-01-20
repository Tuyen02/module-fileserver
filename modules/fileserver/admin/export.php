<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}
define('NV_CONSOLE_DIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __FILE__), PATHINFO_DIRNAME))));

//hàm viết sẵn
function get_cell_code_to($cell_char_from = 'A', $arr_header_row = [])
{
    if (preg_match('/[A-z]/', $cell_char_from)) {
        $cell_char_from = strtoupper($cell_char_from);
        $cell_char_int_from = stringtointvalue($cell_char_from);
        $cell_char_int_to = count($arr_header_row) + $cell_char_int_from - 1;
        $cell_char_to = intvaluetostring($cell_char_int_to);
        return $cell_char_to;
    } else {
        return false;
    }
}

function getcolumnrange($min, $max)
{
    $pointer = strtoupper($min);
    $output = [];
    while (positionalcomparison($pointer, strtoupper($max)) <= 0) {
        array_push($output, $pointer);
        $pointer++;
    }
    return $output;
}

function positionalcomparison($a, $b)
{
    $a1 = stringtointvalue($a);
    $b1 = stringtointvalue($b);
    if ($a1 > $b1) {
        return 1;
    } else {
        if ($a1 < $b1) {
            return -1;
        } else
            return 0;
    }
}

function stringtointvalue($str)
{
    $amount = 0;
    $strarra = array_reverse(str_split($str));

    for ($i = 0; $i < strlen($str); $i++) {
        $amount += (ord($strarra[$i]) - 64) * pow(26, $i);
    }
    return $amount;
}

function intvaluetostring($int)
{
    $start = 'A';
    $int = (int) $int;
    for ($i = 0; $i < $int; $i++) {
        $end = $start++;
    }
    return $end;
}

$sql = "SELECT * FROM `nv4_vi_fileserver_files` WHERE status = 1 and lev = 0";
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
        $tmp_file = $file_folder_path . '/excel-testv2.zip';
        $zip = new PclZip($tmp_file);

        //chia từng sheet trong excel
        // Tạo đối tượng objPHPExcel load template

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
        $objWorksheet->setTitle('Sheet1');
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
            $objWorksheet->setCellValue($table_char_from++ . $i, $_data2['file_size'] ? number_format($_data2['file_size'] / 1024, 2) . ' KB' : '--');
            $sql = "SELECT username FROM nv4_users WHERE userid = " . $_data2['uploaded_by'];
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $username = $stmt->fetchColumn();

            $objWorksheet->setCellValue($table_char_from++ . $i,  $username);
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
                $folderFiles = $db->query("SELECT * FROM `nv4_vi_fileserver_files` WHERE lev = " . $_data2['file_id'])->fetchAll();
                $j = 4; // bắt đầu từ dòng số 4
                foreach ($folderFiles as $folderFile) {
                    $j++;
                    $stt++;
                    $table_char_from = $title_char_from;

                    $folderSheet->setCellValue($table_char_from++ . $j, $stt);
                    $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_name']);
                    $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_path']);
                    $folderSheet->setCellValue($table_char_from++ . $j, $folderFile['file_size'] ? number_format($folderFile['file_size'] / 1024, 2) . ' KB' : '--');
                    $sql = "SELECT username FROM nv4_users WHERE userid = " . $folderFile['uploaded_by'];
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    $username = $stmt->fetchColumn();

                    $folderSheet->setCellValue($table_char_from++ . $j,  $username);
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
                $folderSheet->getColumnDimension('A')->setWidth(10);
                $folderSheet->getColumnDimension('B')->setWidth(35);
                $folderSheet->getColumnDimension('C')->setWidth(35);
                $folderSheet->getColumnDimension('D')->setWidth(35);
                $folderSheet->getColumnDimension('E')->setWidth(35);
                $folderSheet->getColumnDimension('F')->setWidth(35);
                $folderSheet->getColumnDimension('G')->setWidth(35);
                $folderSheet->getColumnDimension('H')->setWidth(35);
            }
        }
        // style table
        $objWorksheet->getStyle('A4:H' . $i)
            ->applyFromArray($styleTableArray);
        // auto size
        $objWorksheet->getColumnDimension('A')->setWidth(10);
        $objWorksheet->getColumnDimension('B')->setWidth(35);
        $objWorksheet->getColumnDimension('C')->setWidth(35);
        $objWorksheet->getColumnDimension('D')->setWidth(35);
        $objWorksheet->getColumnDimension('E')->setWidth(35);
        $objWorksheet->getColumnDimension('F')->setWidth(35);
        $objWorksheet->getColumnDimension('G')->setWidth(35);
        $objWorksheet->getColumnDimension('H')->setWidth(35);

        // lưu file
        $file_path = $file_folder_path . '/ssssss' . $key . '.' . $excel_ext;

        $objWriter = IOFactory::createWriter($objPHPExcel, ucfirst($excel_ext));
        $objWriter->save($file_path);
        $zip->add($file_path, PCLZIP_OPT_REMOVE_PATH, $file_folder_path);
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);

        $download = new NukeViet\Files\Download($file_path, $file_folder_path, 'excel-testv2.' . $excel_ext);
        $download->download_file();
    }
}

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
            $zipFileName = $file_name . '_' . NV_CURRENTTIME . '.zip';
            $zipFilePath = '/data/tmp/' . $zipFileName;
            $zipFullPath = NV_ROOTDIR . $zipFilePath;

            $zipArchive = new PclZip($zipFullPath);
            $zipArchive->create($file_path, PCLZIP_OPT_REMOVE_PATH, NV_ROOTDIR);

            if (file_exists($zipFullPath)) {
                $zip = $zipFullPath;
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
    $row['url_download'] = NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=export&amp;file_id=' . $row['file_id'] . "&download=1";
    $row['created_at'] = date('d/m/Y', $row['created_at']);
    $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / 1024, 2) . ' KB' : '--';
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
