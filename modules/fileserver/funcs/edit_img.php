<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['edit_img'];

$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT file_id, file_name, file_path, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();
$page_url = nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias']);

if (empty($row) || $row['is_folder'] == 1) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$breadcrumbs[] = [
        'catid' => $row['lev'],
        'title' => $row['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias']
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
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $_row['alias']
    ];
    $current_lev = $_row['lev'];
}

$breadcrumbs = array_reverse($breadcrumbs);

foreach ($breadcrumbs as $breadcrumb) {
    $array_mod_title[] = $breadcrumb;
}

if (!empty($row)) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

$is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
$is_video = in_array($file_extension, ['mp4', 'webm', 'ogg']);
$is_audio = in_array($file_extension, ['mp3', 'wav', 'ogg']);
$is_powerpoint = in_array($file_extension, ['ppt', 'pptx']);

$row['file_path'] = NV_BASE_SITEURL . ltrim($row['file_path'], '/');
$contents = nv_fileserver_edit_img($row, $is_image, $is_video, $is_audio, $is_powerpoint);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
