<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['edit_img'];

$sql = 'SELECT file_id, file_name, file_path, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE is_folder = 0 AND status = 1 AND file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

if (empty($row)) {
    nv_redirect_location($base_url);
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

$file_extension = pathinfo($row['file_name'], PATHINFO_EXTENSION);

$arr_check_type = [
    'jpg' => 'img',
    'jpeg' => 'img',
    'png' => 'img',
    'gif' => 'img',
    'bmp' => 'img',
    'webp' => 'img',

    'mp4' => 'video',
    'webm' => 'video',
    'ogg' => 'video',

    'mp3' => 'audio',
    'wav' => 'audio',

    'ppt' => 'powerpoint',
    'pptx' => 'powerpoint',
];

$row['file_path'] = NV_BASE_SITEURL . ltrim($row['file_path'], '/');

$file_type = $arr_check_type[$file_extension];

$contents = nv_fileserver_edit_img($row, $file_type);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
