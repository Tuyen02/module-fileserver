<?php

/**
 * NukeViet Content Management System
 *
 * @version       4.x
 * @author        VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license       GNU/GPL version 2 or any later version
 * @see           https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
if (!defined('NV_IS_USER')) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$lev = $nv_Request->get_int("lev", "get,post", 0);
$dir = NV_ROOTDIR . '/uploads/fileserver';
$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url;

$sql = "SELECT f.file_id, f.file_name, f.file_path, f.file_size, f.created_at, f.is_folder, u.username AS uploaded_by
        FROM " . NV_PREFIXLANG . "_fileserver_files f
        LEFT JOIN " . NV_USERS_GLOBALTABLE . " u ON f.uploaded_by = u.userid WHERE f.status = 1 AND lev = " . $lev . "
        ORDER BY f.is_folder DESC, f.file_id ASC";
$result = $db->query($sql);

if ($lev > 0) {
    $dir = $db->query("SELECT file_path FROM  " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $lev)->fetchColumn();
    $page_url .= '&amp;lev=' . $lev;
}

$action = $nv_Request->get_title('action', 'post', '');
if (!empty($action)) {
    $status = 'error';
    $mess = 'Lỗi hệ thống';

    //create
    if ($action == "create") {
        $name_f = $nv_Request->get_title("name_f", "post", '');
        $type = $nv_Request->get_int("type", "post", 0); //1 =  folder, 0 file

        if ($lev > 0) {
            $parentFileType = checkIfParentIsFolder($db, $lev);

            if ($type == 0 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => 'Không thể tạo file con trong file.']);
                exit();
            }

            if ($type == 1 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => 'Không thể tạo folder con trong file.']);
                exit();
            }
        }

        if (!empty($name_f)) {

            $file_path = $dir . '/' . $name_f;
//            if (file_exists($file_path)) {
//                $status = 'error';
//                $mess = 'File hoặc folder đã tồn tại. Bạn có muốn tiếp tục không?';
//                $i = 1;
//                while (file_exists($file_path)) {
//                    $name_f = pathinfo($name_f, PATHINFO_FILENAME) . "-$i";
//                    $file_path = $dir . '/' . $name_f;
//                    $i++;
//                }
//            }
            $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                    VALUES (:file_name, :file_path, :uploaded_by, :is_folder, :created_at, :lev)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $name_f, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
            $stmt->bindParam(':is_folder', $type, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

            if ($type == 1) {
                //tao folder
                $check_dir = nv_mkdir($dir, $name_f);
                $status = $check_dir[0] == 1 ? 'success' : 'error';
                $mess = $check_dir[1];
            } else {
                $mess = 'Lỗi không tạo được file';
                //tao file
                $_dir = file_put_contents($file_path, '');
                if (isset($_dir)) {
                    $status = 'success';
                    $mess = 'Tạo file ' . $name_f . ' thành công';
                }
            }
            if ($status == 'success') {
                $exe = $stmt->execute();
            }
        }
    }

    if ($action == 'delete') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $checksess = $nv_Request->get_title('checksess', 'get', '');
        if ($fileId > 0 && $checksess == md5($fileId. NV_CHECK_SESSION)) {
            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                $status = 'success';
                $mess = 'Xóa thành công.';
            }else{
                $mess =  'Xóa thất bại.';
            }
        }
    }

    if ($action === 'rename') {
        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        $newName = trim($nv_Request->get_title('new_name', 'post', ''));

        $sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id =" . $fileId;
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $file = $stmt->fetch();

        $mess = 'File không tồn tại.';
        if ($file) {
            $oldFilePath = $file['file_path'];
            $newFilePath = dirname($oldFilePath) . '/' . $newName;
            $mess = 'Không thể đổi tên file.';

            if (rename($oldFilePath, $newFilePath)) {
                $mess = 'Không thể cập nhật cơ sở dữ liệu.';
                $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET file_name = :new_name, file_path = :new_path, updated_at = :updated_at WHERE file_id = :file_id";
                $stmtUpdate = $db->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':new_name', $newName);
                $stmtUpdate->bindParam(':new_path', $newFilePath);
                $stmtUpdate->bindParam(':file_id', $fileId, PDO::PARAM_INT);
                $stmtUpdate->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                if ($stmtUpdate->execute()) {
                    $status = 'success';
                    $mess = 'Đổi tên thành công.';
                }
                // if ($stmtUpdate->execute()) {
                //     // Cập nhật file path của các thư mục con và cháu
                //     $sqlUpdateChildren = "UPDATE " . NV_PREFIXLANG . "_fileserver_files 
                //                           SET file_path = REPLACE(file_path, :old_path, :new_path), 
                //                               updated_at = :updated_at 
                //                           WHERE lev > :parent_lev AND file_path LIKE :old_path_like";
                //     $stmtUpdateChildren = $db->prepare($sqlUpdateChildren);
                //     $oldPathLike = $oldFilePath . '/%';  // Đảm bảo chỉ thay đổi các thư mục con
                //     $stmtUpdateChildren->bindParam(':old_path', $oldFilePath);
                //     $stmtUpdateChildren->bindParam(':new_path', $newFilePath);
                //     $stmtUpdateChildren->bindParam(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                //     $stmtUpdateChildren->bindValue(':parent_lev', $file['lev'], PDO::PARAM_INT);
                //     $stmtUpdateChildren->bindParam(':old_path_like', $oldPathLike);
    
                //     if ($stmtUpdateChildren->execute()) {
                //         $status = 'success';
                //         $mess = 'Đổi tên thành công và các thư mục con đã được cập nhật.';
                //     } else {
                //         $mess = 'Không thể cập nhật các thư mục con.';
                //     }
                // }
            }
        }
    }
    nv_jsonOutput(['status' => $status, 'message' => $mess]);
}

$download = $nv_Request->get_int('download', 'get', '');
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);
    $sql = "SELECT file_path, file_name FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch();
    
    if ($file) {
        $file_path = $file['file_path'];
        $file_name = $file['file_name'];

        if (file_exists($file_path)) {
            $_download = new NukeViet\Files\Download($file_path,$dir, $file_name, true, 0);
            $_download->download_file();
        }
    }
}

$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'text';
if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    $upload = new NukeViet\Files\Upload(
        $admin_info['allow_files_type'],
        $global_config['forbid_extensions'],
        $global_config['forbid_mimes'],
        NV_UPLOAD_MAX_FILESIZE,
        NV_MAX_WIDTH,
        NV_MAX_HEIGHT
    );
    $upload->setLanguage($lang_global);

    $upload_info = $upload->save_file($_FILES['uploadfile'], $dir, false, $global_config['nv_auto_resize']);

    if ($upload_info['error'] == '') {
        $file_name = $upload_info['basename'];
        $file_path = $upload_info['name'];
        $file_type = $upload_info['mime'];
        $file_size = $upload_info['size'];

        $lev = $nv_Request->get_int("lev", "get,post", 0);

        $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->bindParam(':file_size', $file_size, PDO::PARAM_STR);
        $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
        $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
        $stmt->execute();
        nv_redirect_location($page_url);
    } else {
        $error = $upload_info['error'];
    }
}

$xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FORM_ACTION', $page_url);

if ($error != '') {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}
if ($success != '') {
    $xtpl->assign('success', $success);
    $xtpl->parse('main.success');
}

while ($row = $result->fetch()) {
    $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / (1024 * 1024), 2) . ' MB' : '--';
    $row['created_at'] = date("d/m/Y", $row['created_at']);
    $row['icon_class'] = $row['is_folder'] ? 'fa-folder-o' : 'fa-file-o';
    $row['uploaded_by'] = $row['uploaded_by'] ?? 'Unknown';
    $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['file_id'];
    $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit&amp;file_id=' . $row['file_id'];
    $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&action=delete&checksess=" . md5($row['file_id'] . NV_CHECK_SESSION);
    $row['url_download'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&download=1";
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
