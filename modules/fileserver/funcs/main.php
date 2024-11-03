<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$dir = NV_ROOTDIR . '/themes/default/images/fileserver';

$action = $nv_Request->get_title('action', 'post', '');
if (!empty($action)) {

    $status = 'error';
    $mess = 'Lỗi hệ thống';

    //create
    if ($action == "create") {

        $name_f = $nv_Request->get_title("name_f", "post", '');
        $type = $nv_Request->get_int("type", "post", 0); //1 =  folder, 0 file

        if (!empty($name_f)) {
            $db->beginTransaction();

            $file_path = $dir . '/' . $name_f;

            $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, uploaded_by, is_folder, created_at) 
                    VALUES (:file_name, :file_path, :uploaded_by, :is_folder, :created_at)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $name_f, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
            $stmt->bindParam(':is_folder', $type, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $exe = $stmt->execute();

            if ($type == 1) {
                //tao folder
                $check_dir = nv_mkdir($dir, $name_f);
                $status = $check_dir[0] == 1 ? 'success' : 'error';
                $mess = $check_dir[1];
            } else {
                //tao file
                $_dir = file_put_contents($file_path, '');
                if (isset($_dir)) {
                    $status = 'success';
                    $mess = 'Tạo file ' . $name_f . ' thành công';
                } else {
                    $status = 'error';
                    $mess = 'Lỗi không tạo được file';
                }
            }
            if ($status == 'success') {
                $db->commit();
            } else {
                $db->rollBack();
            }
            nv_jsonOutput([
                'mess' => $mess
            ]);
        }
    }

    if ($action == 'delete') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        if ($fileId > 0) {
            $deleted = deleteFileOrFolderById($fileId);
            if ($deleted) {
                nv_jsonOutput(['success' => true]);
            } else {
                nv_jsonOutput(['success' => false, 'message' => 'Xóa thất bại.']);
            }
        } else {
            nv_jsonOutput(['success' => false, 'message' => 'ID không hợp lệ.']);
        }
    }
}

// Truy vấn cơ sở dữ liệu để lấy danh sách file
$sql = "SELECT f.file_id, f.file_name, f.file_path, f.file_size, f.created_at, f.is_folder, u.username AS uploaded_by
        FROM " . NV_PREFIXLANG . "_fileserver_files f
        LEFT JOIN " . NV_USERS_GLOBALTABLE . " u ON f.uploaded_by = u.userid";
$result = $db->query($sql);

// Khởi tạo XTemplate
$xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);

// Xử lý từng file trong kết quả truy vấn
while ($row = $result->fetch()) {
    $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / (1024 * 1024), 2) . ' MB' : '--';
    $row['created_at'] = date("d-m-Y", strtotime($row['created_at']));
    $row['icon_class'] = $row['is_folder'] ? 'fa-folder-o' : 'fa-file-o';
    $row['uploaded_by'] = $row['uploaded_by'] ?? 'Unknown';
    $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&action=delete&checksess=" . md5($row['file_id'] . NV_CHECK_SESSION);
    // Gán dữ liệu vào block file_row
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

// Render và hiển thị nội dung
$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';


