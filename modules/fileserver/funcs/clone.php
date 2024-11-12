<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);
$action = $nv_Request->get_string('action', 'post', '');
$target_folder = $nv_Request->get_string('target_folder', 'post', '');

$sql = "SELECT file_name, file_path FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (!$row) {
    die('File not found');
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$current_directory = dirname($file_path);

$directories = [];
foreach (scandir($current_directory) as $folder) {
    if ($folder != '.' && $folder != '..' && is_dir($current_directory . '/' . $folder)) {
        $directories[] = $folder;
    }
}

$message = ''; 

if (!empty($action)) {
    $new_file_path = NV_ROOTDIR . '/' . $target_folder . '/' . basename($file_path);

    if ($action == 'copy') {
        if (copy($file_path, $new_file_path)) {
            $message = 'File copied successfully';
        } else {
            $message = 'Failed to copy file';
        }
    } elseif ($action == 'move') {
        if (rename($file_path, $new_file_path)) {
            $message = 'File moved successfully';
        } else {
            $message = 'Failed to move file';
        }
    }
}

$xtpl = new XTemplate('clone.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);
$xtpl->assign('FILE_NAME', $file_name);
$xtpl->assign('FILE_PATH', $file_path);
$xtpl->assign('MESSAGE', $message);

foreach ($directories as $directory) {
    $xtpl->assign('DIRECTORY', $directory);
    $xtpl->parse('main.directory_option');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
