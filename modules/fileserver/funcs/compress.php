<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);
$action = $nv_Request->get_title('action', 'post', '');

$sql = 'SELECT file_name, file_path, compressed FROM ' . NV_PREFIXLANG . '_fileserver_files WHERE file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

if (!$row) {
    $message = 'File không tồn tại.';
} else {
    $zipFilePath = NV_ROOTDIR . $row['file_path'];
    $extractTo = NV_ROOTDIR . '/uploads/fileserver/' . pathinfo($row['file_name'], PATHINFO_FILENAME);

    $message = '';
    $zip = new PclZip($zipFilePath);
    $list = $zip->listContent();

    if ($action === 'unzip' && $row['compressed'] == 1) {
        if ($zip->extract(PCLZIP_OPT_PATH, $extractTo)) {
            $update_sql = 'UPDATE ' . NV_PREFIXLANG . '_fileserver_files 
                           SET is_folder = 1, compressed = 0, file_name = :new_name, file_path = :new_path 
                           WHERE file_id = :file_id';
            $update_stmt = $db->prepare($update_sql);
            $new_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
            $new_path = '/uploads/fileserver/' . $new_name;
            $update_stmt->execute([
                ':new_name' => $new_name,
                ':new_path' => $new_path,
                ':file_id' => $file_id
            ]);

            @nv_deletefile($zipFilePath);

            $message = 'Giải nén thành công.';
        } else {
            $message = 'Giải nén thất bại.';
        }
    } elseif ($action === 'unzip') {
        $message = 'Tệp không phải là dạng nén hoặc đã được giải nén trước đó.';
    }
}

$xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);

// Hiển thị danh sách file trong file nén
if (!empty($list)) {
    foreach ($list as $file) {
        $xtpl->assign('CONTENT', [
            'FILENAME' => $file['filename'],
            'SIZE' => $file['folder'] ? '-' : nv_convertfromBytes($file['size']),
            'TYPE' => $file['folder'] ? 'fa-folder-o' : 'fa-file-o'
        ]);
        $xtpl->parse('main.file');
    }
} else {
    $message = 'Không thể đọc nội dung file ZIP hoặc file rỗng.';
}

if (!empty($message)) {
    $xtpl->assign('MESSAGE', $message);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
