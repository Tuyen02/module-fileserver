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

$file_extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));

global $editable_extensions, $allowed_create_extensions;
$viewable_extensions_all = array_merge($editable_extensions, $allowed_create_extensions);
if (!in_array($file_extension, $viewable_extensions_all, true)) {
    nv_redirect_location($base_url);
}

$status = '';
$message = '';

$array_mod_title = build_breadcrumbs($row, $page_url, $base_url);

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

$reponse = [
    'status' => $status,
    'message' => $message,
];

$contents = nv_fileserver_view($row, $file_content, $file_id, $file_name, $view_url, $reponse, $current_permission, $back_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';