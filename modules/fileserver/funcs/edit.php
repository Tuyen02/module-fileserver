<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$allowed_html_tags = (NV_ALLOWED_HTML_TAGS . ', html, title, meta, link, style, script');

$page_title = $lang_module['edit'];

if ($nv_Request->get_int('download', 'get', 0) == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);
} else {
    if (!empty($array_op) && preg_match('/^([a-zA-Z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m)) {
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

$file_extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
if ($file_extension != 'txt') {
    nv_redirect_location($base_url);
}

$current_permission = get_user_permission($file_id, isset($user_info['userid']) ? $user_info['userid'] : 0);
if ($current_permission < 3) {
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
    $file_content = file_get_contents($full_path);
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

    if (empty($status)) {
       if (in_array($file_extension, $allowed_create_extensions)) {
            $file_content = $nv_Request->get_textarea('file_content', '', '');
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
            if (in_array($file_extension, $allowed_create_extensions)) {
                if (file_put_contents($full_path, $file_content) == false) {
                    $status = $lang_module['error'];
                    $message = $lang_module['cannot_save_file'];
                }
            }

            if (empty($status)) {
                $file_size = filesize($full_path);
                $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET updated_at = :updated_at, file_size = :file_size, elastic = 0 WHERE file_id = :file_id';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
                $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
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