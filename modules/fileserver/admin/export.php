<?php

use Sabberworm\CSS\Property\Import;

/**
 *
 * @Project       NUKEVIET 4.x
 * @author        VINADES.,JSC (contact@vinades.vn)
 * @copyright (C) 2014 VINADES.,JSC. All rights reserved
 * @license       GNU/GPL version 2 or any later version
 * @Createdate    31/05/2010, 00:36
 */
if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}
define('NV_CONSOLE_DIR', str_replace(DIRECTORY_SEPARATOR, '/', realpath(pathinfo(str_replace(DIRECTORY_SEPARATOR, '/', __file__), PATHINFO_DIRNAME))));

//hàm viết sẵn
function get_cell_code_to ($cell_char_from = 'A', $arr_header_row = [])
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

function getcolumnrange ($min, $max)
{
    $pointer = strtoupper($min);
    $output = [];
    while (positionalcomparison($pointer, strtoupper($max)) <= 0) {
        array_push($output, $pointer);
        $pointer++;
    }
    return $output;
}

function positionalcomparison ($a, $b)
{
    $a1 = stringtointvalue($a);
    $b1 = stringtointvalue($b);
    if ($a1 > $b1) {
        return 1;
    } else {
        if ($a1 < $b1) {
            return -1;
        } else return 0;
    }
}

function stringtointvalue ($str)
{
    $amount = 0;
    $strarra = array_reverse(str_split($str));

    for ($i = 0; $i < strlen($str); $i++) {
        $amount += (ord($strarra[$i]) - 64) * pow(26, $i);
    }
    return $amount;
}

function intvaluetostring ($int)
{
    $start = 'A';
    $int = (int)$int;
    for ($i = 0; $i < $int; $i++) {
        $end = $start++;
    }
    return $end;
}

//mảng này chứa danh sách các sheet
$khoi_lop_v2 = [

];
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
    $module_name = 'module_name Xuất excel';

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
            'Mã môn',
            'Tên môn',
            'Họ tên chủ tịch',
            'Email chủ tịch',
            'Họ tên thư ký',
            'Email thư ký'
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

        foreach ($khoi_lop_v2 as $key => $_ar_data) {
            //chia từng sheet trong excel

            // Tạo đối tượng objPHPExcel load template
            $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load(NV_CONSOLE_DIR . '/export_excel/template2.xlsx'); //load template mẫu
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
            $objWorksheet->setCellValue('A1', 'Phân công hội đồng đánh giá cuộc thi Elearning 2021')
                ->getStyle('A1')
                ->applyFromArray($style_title);
            // in tiêu đề
            $objWorksheet->fromArray($arr_header_row, // The data to set
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
            $data_sbj = $db->query("SELECT * FROM `nv4_vi_fileserver_files` WHERE status = 1");
            while ($_data2 = $data_sbj->fetchColumn()) {
                $i++;
                $stt++;
                $row_id++;
                // bắt đầu in từ ô
                $table_char_from = $title_char_from;

                // các dữ liệu row kết quả
                $objWorksheet->setCellValue($table_char_from++ . $i, $stt);
                $objWorksheet->setCellValue($table_char_from++ . $i, $_data2);
                $objWorksheet->setCellValue($table_char_from++ . $i, $array_subjects[$_data2]);
                $objWorksheet->setCellValue($table_char_from++ . $i, '');
                $objWorksheet->setCellValue($table_char_from++ . $i, '');
                $objWorksheet->setCellValue($table_char_from++ . $i, '');
                $objWorksheet->setCellValue($table_char_from++ . $i, '');

                $objWorksheet->getRowDimension($i)->setRowHeight(20);
            }
            // style table
            $objWorksheet->getStyle('A4:G' . $i)
                ->applyFromArray($styleTableArray);
            // auto size
            $objWorksheet->getColumnDimension('A')->setWidth(15);
            $objWorksheet->getColumnDimension('B')->setWidth(17);
            $objWorksheet->getColumnDimension('C')->setWidth(35);
            $objWorksheet->getColumnDimension('D')->setWidth(30);
            $objWorksheet->getColumnDimension('E')->setWidth(30);
            $objWorksheet->getColumnDimension('F')->setWidth(30);
            $objWorksheet->getColumnDimension('G')->setWidth(30);

            // lưu file
            $file_path = $file_folder_path . '/' . $key . '.' . $excel_ext;
            $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, ucfirst($excel_ext));
            $objWriter->save($file_path);
            $zip->add($file_path, PCLZIP_OPT_REMOVE_PATH, $file_folder_path);
            $objPHPExcel->disconnectWorksheets();
            unset($objPHPExcel);
        }
        $file_folder_path = NV_ROOTDIR . '/' . NV_TEMP_DIR . '/excel-testv2';
        $file_path = $file_folder_path . '/' . 'excel-testv2.zip';
        $download = new NukeViet\Files\Download($file_path, $file_folder_path, 'excel-testv2.zip');
        $download->download_file();
    }
}

$xtpl = new XTemplate('excel-testv2.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('GLANG', $lang_global);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op);
$xtpl->assign('file_id', $file_id);
$xtpl->assign('MODULE_DATA', $module_data);

if (!empty($error)) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';