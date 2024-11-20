<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$file_id = $nv_Request->get_int('file_id', 'get', 0);

$sql = 'SELECT file_name, file_path FROM ' . NV_PREFIXLANG . '_fileserver_files WHERE file_id = :file_id';
$stmt = $db->prepare($sql);
$stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();
$zipFilePath = NV_ROOTDIR . $row['file_path'];

$zip = new PclZip($zipFilePath);
$list = $zip->listContent();
if (empty($list)) {
    die('Không thể đọc nội dung file ZIP.');
}

$xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FILE_ID', $file_id);

foreach ($list as $file) {
    $xtpl->assign('CONTENT', [
        'FILENAME' => $file['filename'],
        'SIZE' => $file['folder'] ? '-' : nv_convertfromBytes($file['size']),
        'TYPE' => $file['folder'] ? 'Thư mục' : 'File'
    ]);
    $xtpl->parse('main.file');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
