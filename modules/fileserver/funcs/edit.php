<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$allowed_html_tags = (NV_ALLOWED_HTML_TAGS . ', html, title, meta, link, style, script');

$page_title = $lang_module['edit'];

if ($nv_Request->get_int('download', 'get', 0) == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);
} else {
    if (!empty($array_op) && preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m)) {
        $file_id = $m[2];
    } else {
        $file_id = $nv_Request->get_int('file_id', 'get', 0);
    }
}

if (empty($file_id)) {
    nv_redirect_location($base_url);
}

$sql = 'SELECT file_id, file_name, file_path, lev, alias, is_folder, uploaded_by FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE is_folder = 0 AND status = 1 AND file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

if (empty($row)) {
    nv_redirect_location($base_url);
}

$current_permission = get_user_permission($file_id, $row['uploaded_by']);
if ($current_permission <= 2) {
    nv_redirect_location($base_url);
}

$file_extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
if (!in_array($file_extension, $allowed_create_extensions) && !in_array($file_extension, ['doc', 'docx', 'xls', 'xlsx'])) {
    nv_redirect_location($base_url);
}

$status = '';
$message = '';

$breadcrumbs[] = [
    'catid' => $row['lev'],
    'title' => $row['file_name'],
    'link' => $page_url
];
$current_lev = $row['lev'];

while ($current_lev > 0) {
    $sql = 'SELECT file_id, file_name, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $_row = $db->query($sql)->fetch();
    if (empty($_row)) {
        break;
    }
    $op = $module_info['alias']['main'];
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $_row['file_name'],
        'link' => $base_url . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $_row['alias']
    ];
    $current_lev = $_row['lev'];
}
$breadcrumbs = array_reverse($breadcrumbs);
foreach ($breadcrumbs as $breadcrumb) {
    $array_mod_title[] = $breadcrumb;
}

$view_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '&lev=' . $row['lev'];
$back_url = $view_url;

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;

$file_content = '';
if (file_exists($full_path)) {
    if ($file_extension == 'pdf') {
        $file_content = NV_BASE_SITEURL . ltrim($file_path, '/');
    } elseif (in_array($file_extension, ['doc', 'docx'])) {
        $file_content = NV_BASE_SITEURL . ltrim($file_path, '/');
    } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
        if (!is_dir(NV_ROOTDIR . '/vendor/phpoffice/phpspreadsheet')) {
            trigger_error('No phpspreadsheet lib. Run command "composer require phpoffice/phpspreadsheet" to install', 256);
        }
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($full_path);
            $sheetData = [];
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $worksheet = $spreadsheet->getSheetByName($sheetName);
                $sheetData[$sheetName] = $worksheet->toArray();
            }
            $file_content = json_encode($sheetData);
        } catch (Exception $e) {
            $file_content = NV_BASE_SITEURL . ltrim($file_path, '/');
        }
    } else {
        $file_content = file_get_contents($full_path);
    }
}

if ($nv_Request->get_int('download', 'get', 0) == 1) {
    $token = $nv_Request->get_title('token', 'get', '');

    if ($row['file_id'] == 0 || empty($token) || $token != md5($row['file_id'] . NV_CHECK_SESSION . $global_config['sitekey'])) {
        nv_redirect_location($base_url);
    }

    $file_path = NV_ROOTDIR . $row['file_path'];
    if (file_exists($file_path)) {
        $_download = new NukeViet\Files\Download($file_path, NV_ROOTDIR . $base_dir, $row['file_name'], true, 0);
        $_download->download_file();
        exit();
    }
}

if ($nv_Request->get_int('file_id', 'post') > 0) {
    $old_content = '';
    $has_changes = false;
    $file_content = '';

    if (in_array($file_extension, ['doc', 'docx'])) {
        $old_content = file_get_contents($full_path);
        if ($old_content === false) {
            $old_content = file_get_contents($full_path, FILE_BINARY);
        }
    } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
        $status = $lang_module['error'];
        $message = $lang_module['cannot_edit_excel_file_'];
        $has_changes = false;
    } elseif (in_array($file_extension, $allowed_create_extensions)) {
        $old_content = file_get_contents($full_path);
    } else if ($file_extension != 'pdf') {
        $old_content = file_get_contents($full_path);
    }

    if (empty($status)) {
        if (in_array($file_extension, ['doc', 'docx'])) {
            $file_content = $nv_Request->get_string('file_content', 'post');
        } elseif (in_array($file_extension, $allowed_create_extensions)) {
            $file_content = $nv_Request->get_textarea('file_content', '', '');
        } else if ($file_extension != 'pdf') {
            $file_content = $nv_Request->get_textarea('file_content', '', NV_ALLOWED_HTML_TAGS);
        }

        if (!empty($file_content)) {
            $old_md5 = md5(trim($old_content));
            $new_md5 = md5(trim($file_content));
            $has_changes = ($old_md5 !== $new_md5);
        }

        if (!$has_changes) {
            $status = $lang_module['error'];
            $message = $lang_module['no_changes'];
        } else {
            if (in_array($file_extension, ['doc', 'docx'])) {
                try {
                    $phpWord = new \PhpOffice\PhpWord\PhpWord();
                    $section = $phpWord->addSection();
                    \PhpOffice\PhpWord\Shared\Html::addHtml($section, $file_content);
                    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                    $writer->save($full_path);
                } catch (Exception $e) {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'] . $e->getMessage();
                }
            } elseif (in_array($file_extension, $allowed_create_extensions)) {
                if (file_put_contents($full_path, $file_content) == false) {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'];
                }
            } else if ($file_extension != 'pdf') {
                if (file_put_contents($full_path, $file_content) == false) {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'];
                }
            }

            if (empty($status)) {
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
                }
            }
        }
    }
}

$reponse = [
    'status' => $status,
    'message' => $message,
];

$contents = nv_fileserver_edit($row, $file_content, $file_id, $file_name, $view_url, $reponse, $current_permission, $back_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';