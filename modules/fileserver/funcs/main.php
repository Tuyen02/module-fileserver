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

if ($nv_Request->isset_request("action", "post,get")) {
    $file_id = $nv_Request->get_int("file_id", "post,get", 0);
    $checksess = $nv_Request->get_title("checksess", "post,get", 0);

    //delete
    if ($file_id > 0 and $checksess == md5($file_id . NV_CHECK_SESSION)) {
        $db->query("DELETE FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id=" . $file_id);
    }

    //create
    if ($nv_Request->get_title("create_action", "post") == 'create') {
        $name = $nv_Request->get_title("name", "post", '');
        $type = $nv_Request->get_title("type", "post", 'file'); // 'file' hoặc 'folder'
        
        if (!empty($name)) {
            $is_folder = ($type == 'folder') ? 1 : 0;
            $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, is_folder, created_at) 
                    VALUES (:name, :is_folder, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':is_folder', $is_folder, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    //rename
    if ($nv_Request->isset_request("file_id", "get") && $nv_Request->isset_request("new_name", "post")) {
        $file_id = $nv_Request->get_int("file_id", "get", 0);
        $new_name = $nv_Request->get_title("new_name", "post", '');
    
        if ($file_id > 0 && !empty($new_name)) {
            $stmt = $db->prepare("UPDATE " . NV_PREFIXLANG . "_fileserver_files SET file_name = :new_name WHERE file_id = :file_id");
            $stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
            $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    // Thêm kiểm tra cho action rename
    if ($nv_Request->isset_request("rename_action", "post")) {
        $file_id = $nv_Request->get_int("file_id", "post", 0);
        $new_name = $nv_Request->get_title("new_name", "post", '');

        if ($file_id > 0 && !empty($new_name)) {
            $stmt = $db->prepare("UPDATE " . NV_PREFIXLANG . "_fileserver_files SET file_name = :new_name WHERE file_id = :file_id");
            $stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
            $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $stmt->execute();
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


