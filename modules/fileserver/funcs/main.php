<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$search_term = $nv_Request->get_title('search', 'get', '');
$search_type = $nv_Request->get_title('search_type', 'get', 'all');

$lev = $nv_Request->get_int("lev", "get,post", 0);
$base_dir = '/uploads/fileserver';
$full_dir = NV_ROOTDIR . $base_dir;
$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url;

$sql = "SELECT f.file_id, f.file_name, f.file_path, f.file_size, f.created_at, f.is_folder, f.share, f.compressed ,p.owner, p.group, p.other, u.username AS uploaded_by
        FROM " . NV_PREFIXLANG . "_fileserver_files f
        LEFT JOIN " . NV_USERS_GLOBALTABLE . " u ON f.uploaded_by = u.userid
        JOIN ". NV_PREFIXLANG . "_fileserver_permissions p ON f.file_id = p.file_id
        WHERE f.status = 1 AND lev = :lev";

if (!empty($search_term)) {
    $sql .= " AND f.file_name LIKE :search_term";
}

if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    if ($search_type === 'file') {
        $sql .= " AND f.is_folder = 0";
    } elseif ($search_type === 'folder') {
        $sql .= " AND f.is_folder = 1";
    }
}

$sql .= " ORDER BY f.is_folder DESC, f.file_id ASC";
$stmt = $db->prepare($sql);
$stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
if (!empty($search_term)) {
    $stmt->bindValue(':search_term', '%' . $search_term . '%', PDO::PARAM_STR);
}
$stmt->execute();
$result = $stmt->fetchAll();

if ($lev > 0) {
    $base_dir = $db->query("SELECT file_path FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $lev)->fetchColumn();
    $full_dir = NV_ROOTDIR . $base_dir;
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

            $file_path = $base_dir . '/' . $name_f;
            if (file_exists($file_path)) {
                $status = 'error';
                $mess = 'File hoặc folder đã tồn tại. Bạn có muốn tiếp tục không?';
                $i = 1;
                while (file_exists($file_path)) {
                    $name_f = pathinfo($name_f, PATHINFO_FILENAME) . "-$i";
                    $file_path = $dir . '/' . $name_f;
                    $i++;
                }
            }
            $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                    VALUES (:file_name, :file_path, :uploaded_by, :is_folder, :created_at, :lev)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $name_f, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
            $stmt->bindParam(':is_folder', $type, PDO::PARAM_INT);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

            // $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions 
            // (file_id, user_id, owner, `group`, other, update_at) 
            // VALUES (:file_id, :user_id, 1, 3, 3, :update_at)";
            // $stmt = $db->prepare($sql);
            // $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
            // $stmt->bindParam(':user_id', $uploadedBy, PDO::PARAM_INT);
            // $stmt->bindParam(':update_at', $currentTime, PDO::PARAM_INT);

            if ($type == 1) {
                //tao folder
                $check_dir = nv_mkdir($full_dir, $name_f);
                $status = $check_dir[0] == 1 ? 'success' : 'error';
                $mess = $check_dir[1];
            } else {
                $mess = 'Lỗi không tạo được file';
                //tao file
                $_dir = file_put_contents($full_dir . '/' . $name_f, '');
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
        if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                $status = 'success';
                $mess = 'Xóa thành công.';
            } else {
                $mess =  'Xóa thất bại.';
            }
        }
    }

    if ($action == 'rename') {
        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        $newName = trim($nv_Request->get_title('new_name', 'post', ''));

        $sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id =" . $fileId;
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $file = $stmt->fetch();

        $mess = 'File không tồn tại.';
        if ($file) {
            $oldFilePath = $file['file_path'];
            $oldFullPath = NV_ROOTDIR . '/' . $oldFilePath;

            $newFilePath = dirname($oldFilePath) . '/' . $newName;
            $newFullPath = NV_ROOTDIR . '/' . $newFilePath;

            $childCount = $db->query("SELECT COUNT(*) FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = " . $fileId)->fetchColumn();
            if ($file['is_folder'] == 1 && $childCount > 0) {
                $mess = 'Không thể đổi tên folder vì nó chứa file con.';
            } else {
                $mess = 'Không thể đổi tên file.';
                if (rename($oldFullPath, $newFullPath)) {
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
                }
            }
        }
    }

    if ($action == 'share') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $share_option = $nv_Request->get_int('share_option', 'post', 0);

        if ($fileId > 0) {
            $sql = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET share = :share_option WHERE file_id = :file_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':share_option', $share_option, PDO::PARAM_INT);
            $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $status = 'success';
                $mess = 'Cập nhật trạng thái chia sẻ thành công.';
            } else {
                $status = 'error';
                $mess = 'Không thể cập nhật trạng thái chia sẻ.';
            }
        }
    }

    if ($action == 'compress') {
        $files = $nv_Request->get_array('files', 'post', []);
        if (empty($files)) {
            $status = 'error';
            $mess = 'Không có file nào được chọn';
        }

        $zipFileName = 'compressed_' . NV_CURRENTTIME . '.zip';
        $zipFilePath = $base_dir . '/' . $zipFileName;
        $zipFullPath = NV_ROOTDIR . $zipFilePath;

        $compressResult = compressFiles($files, $zipFullPath);
        if ($compressResult['status'] === 'success') {
            $sqlInsert = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, uploaded_by, is_folder, created_at, lev, compressed) 
                          VALUES (:file_name, :file_path, :uploaded_by, 0, :created_at, :lev, 1)";
            $stmtInsert = $db->prepare($sqlInsert);
            $stmtInsert->bindParam(':file_name', $zipFileName, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_path', $zipFilePath, PDO::PARAM_STR);
            $stmtInsert->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
            $stmtInsert->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
            $stmtInsert->execute();

            $mess = $compressResult['message'];
        }
    }

    nv_jsonOutput(['status' => $status, 'message' => $mess]);
}

$download = $nv_Request->get_int('download', 'get', '');
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);
    if (!checkPermissions($file_id, 'download', $user_info['userid'])) {
        $mess = 'Bạn không có quyền tải xuống file này.';
    } else {
        // Thực hiện tải xuống file
        $sql = "SELECT file_path, file_name FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->execute();
        $file = $stmt->fetch();

        if ($file) {
            $file_path = NV_ROOTDIR . $file['file_path'];
            $file_name = $file['file_name'];
            if (file_exists($file_path)) {
                $_download = new NukeViet\Files\Download($file_path, $full_dir, $file_name, true, 0);
                $_download->download_file();
            }
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

    $upload_info = $upload->save_file($_FILES['uploadfile'], $full_dir, false, $global_config['nv_auto_resize']);

    if ($upload_info['error'] == '') {
        $full_path = $upload_info['name'];

        $relative_path = str_replace(NV_ROOTDIR, '', $full_path);

        $file_name = $upload_info['basename'];
        $file_size = $upload_info['size'];

        $lev = $nv_Request->get_int("lev", "get,post", 0);

        $sql = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $relative_path, PDO::PARAM_STR);
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
$xtpl->assign('SEARCH_TERM', $search_term);

if ($error != '') {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}
if ($success != '') {
    $xtpl->assign('success', $success);
    $xtpl->parse('main.success');
}

foreach ($result as $row) {
    $row['created_at'] = date("d/m/Y", $row['created_at']);
    if ($row['compressed'] == 1) {
        $row['icon_class'] = 'fa-file-archive-o';
    } else {
        $row['icon_class'] = $row['is_folder'] ? 'fa-folder-o' : 'fa-file-o';
    }
    $row['permissions'] = $row['owner'].$row['group'].$row['other'];
    $row['uploaded_by'] = $row['uploaded_by'] ?? 'Unknown';
    $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['file_id'];
    $row['url_perm'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm&amp;file_id=' . $row['file_id'];
    $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit&amp;file_id=' . $row['file_id'];
    $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&action=delete&checksess=" . md5($row['file_id'] . NV_CHECK_SESSION);
    $row['url_download'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;file_id=' . $row['file_id'] . "&download=1";
    $row['url_clone'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone&amp;file_id=' . $row['file_id'];
    $row['url_rename'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=rename&amp;file_id=' . $row['file_id'];
    $url_share = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share&amp;file_id=' . $row['file_id'];
    $row['url_compress'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress&amp;file_id=' . $row['file_id'];
    $row['url_share'] = $url_share;

    $fileInfo = pathinfo($row['file_name'], PATHINFO_EXTENSION);
    if ($row['compressed'] == 1) {
        $xtpl->assign('VIEW', $row['url_compress']);
        $xtpl->parse('main.file_row.view');
    } else 
    if ($row['is_folder'] == 1) {
        $row['file_size'] = 'Folder';
        $xtpl->assign('VIEW', $row['url_view']);
        $xtpl->parse('main.file_row.view');
    } else {
        $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / (1024 * 1024), 2) . ' MB' : '--';
        $xtpl->assign('SHARE', $row['url_share']);
        $xtpl->parse('main.file_row.share');

        $xtpl->assign('VIEW', $row['url_edit']);
        $xtpl->parse('main.file_row.view');

        $xtpl->assign('DOWNLOAD', $row['url_download']);
        $xtpl->parse('main.file_row.download');

        if ($fileInfo == 'txt') {
            $xtpl->assign('EDIT',  $row['url_edit']);
            $xtpl->parse('main.file_row.edit');
        }
    }

    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
