<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);
$rank = $nv_Request->get_int('rank', 'get', 0);
$action = $nv_Request->get_string('action', 'post', '');
$target_folder = $nv_Request->get_string('target_folder', 'post', '');

$sql = "SELECT file_name, file_path, lev FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (!$row) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone');
}

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone' . '&amp;file_id=' . $file_id;
$page_url = $base_url;
$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['lev'];

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$current_directory = dirname($full_path);
$directories = [];
$lev = $row['lev'];

if ($rank > 0) {
    $lev = $rank;
    $base_url .= '&amp;rank=' . $rank;
}

$sql = "SELECT file_id, file_name, file_path, lev FROM " . NV_PREFIXLANG . "_fileserver_files 
        WHERE lev = :lev AND is_folder = 1 AND status = 1 ORDER BY file_id ASC";
$stmt = $db->prepare($sql);
$stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
$stmt->execute();
$directories = $stmt->fetchAll();
$message = '';


$copy = $nv_Request->get_int('copy', 'get', 0);
$move = $nv_Request->get_int('move', 'get', 0);
if (defined('NV_IS_SPADMIN')) {
    if ($copy == 1) {
        $message = $lang_module['copy_false'];
        $target_folder = $db->query("SELECT file_path, file_id FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $rank)->fetch();
        $target_url = $target_folder['file_path'];
        $lev = $target_folder['file_id'];

        $sqlCheck = "SELECT COUNT(*) FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_name = :file_name AND lev = :lev AND status =1";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindParam(':file_name', $row['file_name']);
        $stmtCheck->bindParam(':lev', $lev);
        $stmtCheck->execute();
        $existingFile = $stmtCheck->fetchColumn();

        if ($existingFile > 0) {
            $message = $lang_module['f_has_exit'];
        } else {
            if (copy(NV_ROOTDIR . '/' . $row['file_path'], NV_ROOTDIR . '/' . $target_url . '/' . $row['file_name'])) {
                $message = $lang_module['copy_ok'];
                $new_file_name = $row['file_name'];
                $new_file_path = $target_url . '/' . $new_file_name;

                $sql_insert = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                               VALUES (:file_name, :file_path, :uploaded_by, 0, :created_at, :lev)";
                $stmt = $db->prepare($sql_insert);
                $stmt->bindParam(':file_name', $new_file_name);
                $stmt->bindParam(':file_path', $new_file_path);
                $stmt->bindParam(':uploaded_by', $user_info['userid']);
                $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindParam(':lev', $lev);
                $stmt->execute();

            }
        }
    }
} else {
    $message = 'Không có quyền thao tác';
}

if (defined('NV_IS_SPADMIN')) {

    if ($move == 1) {
        $message = $lang_module['move_false'];
        $target_folder = $db->query("SELECT file_path, file_id FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $rank)->fetch();
        $target_url = $target_folder['file_path'];
        $lev = $target_folder['file_id'];

        $sqlCheck = "SELECT COUNT(*) FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_name = :file_name AND lev = :lev AND status = 1";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindParam(':file_name', $row['file_name']);
        $stmtCheck->bindParam(':lev', $lev);
        $stmtCheck->execute();
        $existingFile = $stmtCheck->fetchColumn();

        if ($existingFile > 0) {
            $message = $lang_module['f_has_exit'];
        } else {
            if (rename(NV_ROOTDIR . '/' . $row['file_path'], NV_ROOTDIR . '/' . $target_url . '/' . $row['file_name'])) {
                $message = $lang_module['move_false'];
                $new_file_path = $target_url . '/' . $row['file_name'];

                $sql_update = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET file_path = :file_path, lev = :lev WHERE file_id = :file_id";
                $stmt = $db->prepare($sql_update);
                $stmt->bindParam(':file_path', $new_file_path);
                $stmt->bindParam(':lev', $lev);
                $stmt->bindParam(':file_id', $file_id);
                $stmt->execute();
            }
        }
    }
} else {
    $message = 'Không có quyền thao tác';
}



if (empty($directories)) {
    $sql = "SELECT file_id, file_name, file_path FROM " . NV_PREFIXLANG . "_fileserver_files 
            WHERE lev = 0 AND status = 1 AND is_folder = 1 ORDER BY file_name ASC";
    $stmt = $db->query($sql);
    $directories = $stmt->fetchAll();

    $url_previous = $base_url . '&amp;rank=' . 0;
} else {
    $parent_directory = dirname($current_directory);
    $parent_lev = $lev > 0 ? $lev - 1 : 0;

    $url_previous = $base_url . '&amp;rank=' . $parent_lev;
}

$xtpl = new XTemplate('clone.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', $file_name);
$xtpl->assign('FILE_PATH', $file_path);
$xtpl->assign('MESSAGE', $message);
$xtpl->assign('url_previous', $url_previous);
$xtpl->assign('url_view', $view_url);

foreach ($directories as $directory) {
    $directory['url'] = $page_url . '&amp;rank=' . $directory['file_id'];
    $xtpl->assign('DIRECTORY', $directory);
    $xtpl->parse('main.directory_option');
}

if ($message != '') {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$url_copy = $base_url . '&amp;copy=1';
$xtpl->assign('url_copy', $url_copy);

$url_move = $base_url . '&amp;move=1';
$xtpl->assign('url_move', $url_move);

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
