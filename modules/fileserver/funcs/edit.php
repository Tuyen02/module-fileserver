<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

$page_title = $lang_module['edit'];

$sql = 'SELECT file_id, file_name, file_path, lev, alias, is_folder, uploaded_by FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE is_folder = 0 AND status = 1 AND file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

if (empty($row)) {
    nv_redirect_location($base_url);
}

$status = '';
$message = '';
$current_permission = get_user_permission($file_id, $row['uploaded_by']);

if ($current_permission < 3) {
    $status = $lang_module['error'];
    $message = $lang_module['not_permission_to_edit'];
}

$breadcrumbs[] = [
    'catid' => $row['lev'],
    'title' => $row['file_name'],
    'link' => $page_url
];
$current_lev = $row['lev'];

while ($current_lev > 0) {
    $sql = 'SELECT file_name, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $_row = $db->query($sql)->fetch();
    if (empty($_row)) {
        break;
    }
    $op = $_row['is_folder'] == 1 ? $module_info['alias']['main'] : $op;
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $_row['file_name'],
        'link' => $page_url
    ];
    $current_lev = $_row['lev'];
}
$breadcrumbs = array_reverse($breadcrumbs);
$array_mod_title = $breadcrumbs;

$view_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '&lev=' . $row['lev'];

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

$file_content = '';
if (file_exists($full_path)) {
    if ($file_extension == 'pdf') {
        $file_content = NV_BASE_SITEURL . ltrim($file_path, '/');
    } elseif (in_array($file_extension, ['doc', 'docx'])) {
        if (!is_dir(NV_ROOTDIR . '/vendor/phpoffice/phpword')) {
            trigger_error('No phpword lib. Run command "composer require phpoffice/phpword" to install', 256);
        }
        try {
            $phpWord = IOFactory::load($full_path);
            $text = '';
            foreach ($phpWord->getSections() as $section) {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    }
                }
            }
            $file_content = $text;
        } catch (Exception $e) {
            $file_content = '';
            $status = $lang_module['error'];
            $message = $lang_module['cannot_open_word_file'] . $e->getMessage();
        }
    } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
        if (!is_dir(NV_ROOTDIR . '/vendor/phpoffice/phpspreadsheet')) {
            trigger_error('No phpspreadsheet lib. Run command "composer require phpoffice/phpspreadsheet" to install', 256);
        }
        try {
            $spreadsheet = SpreadsheetIOFactory::load($full_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $text = '';

            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }
                $text .= implode("\t", $rowData) . "\n";
            }
            $file_content = $text;
        } catch (Exception $e) {
            $file_content = '';
            $status = $lang_module['error'];
            $message = $lang_module['cannot_open_excel_file'] . $e->getMessage();
        }
    } else {
        $file_content = file_get_contents($full_path);
    }
}

if ($current_permission >= 3 && empty($status) && $nv_Request->get_int('file_id', 'post') > 0) {
    $old_content = '';
    $has_changes = false;
    $file_content = $nv_Request->get_string('file_content', 'post');

    if (in_array($file_extension, ['doc', 'docx'])) {
        $old_content = file_get_contents($full_path);
        if ($old_content === false) {
            $old_content = file_get_contents($full_path, FILE_BINARY);
        }
    } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
        $status = $lang_module['error'];
        $message = $lang_module['cannot_edit_excel_file_'];
        $has_changes = false;
    } elseif (in_array($file_extension, ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'sql'])) {
        $old_content = file_get_contents($full_path);
    } else if ($file_extension != 'pdf') {
        $old_content = file_get_contents($full_path);
    }

    if (empty($status) && !empty($old_content)) {
        $old_content = str_replace(["\r\n", "\r"], "\n", $old_content);
        $file_content = str_replace(["\r\n", "\r"], "\n", $file_content);
        
        $old_md5 = md5(trim($old_content));
        $new_md5 = md5(trim($file_content));
        $has_changes = ($old_md5 !== $new_md5);

        if ($has_changes) {
            if (in_array($file_extension, ['doc', 'docx'])) {
                try {
                    $phpWord = new \PhpOffice\PhpWord\PhpWord();
                    $section = $phpWord->addSection();
                    $section->addText($file_content);
                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                    $writer->save($full_path);
                } catch (Exception $e) {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'] . $e->getMessage();
                    $has_changes = false;
                }
            } elseif (in_array($file_extension, ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'sql']) || $file_extension != 'pdf') {
                if (file_put_contents($full_path, $file_content) === false) {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'];
                    $has_changes = false;
                }
            }

            if ($has_changes) {
                $file_size = filesize($full_path);

                $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET updated_at = :updated_at, file_size = :file_size, elastic = :elastic WHERE file_id = :file_id';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
                $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
                $stmt->bindValue(':elastic', 0, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    updateStat($row['lev']);
                    nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['edit'], 'File id: ' . $file_id, $user_info['userid']);

                    if ($row['lev'] > 0) {
                        updateParentFolderSize($row['lev']);
                    }

                    $status = $lang_module['success'];
                    $message = $lang_module['update_ok'];
                } else {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'];
                }
            }
        } else {
            $status = $lang_module['error'];
            $message = $lang_module['no_changes'];
        }
    }
}

$reponse = [
    'status' => $status,
    'message' => $message,
];

$contents = nv_fileserver_edit($file_content, $file_id, $file_name, $view_url, $reponse, $current_permission);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
