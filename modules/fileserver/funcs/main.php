<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}
$perpage = 10;
$page = $nv_Request->get_int("page", "get", 1);

$search_term = $nv_Request->get_title('search', 'get', '');
$search_type = $nv_Request->get_title('search_type', 'get', 'all');

$lev = $nv_Request->get_int("lev", "get,post", 0);
$base_dir = '/uploads/fileserver';
$full_dir = NV_ROOTDIR . $base_dir;
$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$page_url = $base_url;

if (in_array($config_value = get_config_value(), $user_info['in_groups'])) {
    $arr_per = array_column($db->query("SELECT p_group, file_id FROM `nv4_vi_fileserver_permissions` WHERE p_group > 1")->fetchAll(), 'p_group', 'file_id');
} else {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$file_ids = array_keys($arr_per);
$file_ids_placeholder = [];

$sql = "SELECT file_id, file_name, file_path, file_size, created_at, is_folder, share, compressed,lev
        FROM " . NV_PREFIXLANG . "_fileserver_files f
        WHERE status = 1 AND lev = :lev";

if (!defined('NV_IS_SPADMIN') && !empty($arr_per)) {
    foreach ($file_ids as $index => $file_id) {
        $file_ids_placeholder[":file_id_$index"] = $file_id;
    }

    if (!empty($file_ids_placeholder)) {
        $sql .= " AND file_id IN (" . implode(',', array_keys($file_ids_placeholder)) . ")";
    }
}

if (!empty($search_term)) {
    $sql .= " AND file_name LIKE :search_term";
}

if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    if ($search_type === 'file') {
        $sql .= " AND is_folder = 0";
    } elseif ($search_type === 'folder') {
        $sql .= " AND is_folder = 1";
    }
}

$total_sql = "SELECT COUNT(*) FROM " . NV_PREFIXLANG . "_fileserver_files f WHERE status = 1 AND lev = :lev";
$total_stmt = $db->prepare($total_sql);
$total_stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
$total_stmt->execute();
$total = $total_stmt->fetchColumn();

$sql .= " ORDER BY file_id ASC LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($sql);
$stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perpage, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $perpage, PDO::PARAM_INT);

foreach ($file_ids_placeholder as $param => $file_id) {
    $stmt->bindValue($param, $file_id, PDO::PARAM_INT);
}

if (!empty($search_term)) {
    $stmt->bindValue(':search_term','%'.$search_term. '%', PDO::PARAM_STR);
}
$stmt->execute();
$result = $stmt->fetchAll();


if ($lev > 0) {
    $base_dir = $db->query("SELECT file_path FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $lev)->fetchColumn();
    $full_dir = NV_ROOTDIR . $base_dir;
    $page_url .= '&amp;lev=' . $lev;
}

$action = $nv_Request->get_title('action', 'post', '');
$fileIds = $nv_Request->get_array('files', 'post', []);

if (!empty($action)) {

    $status = $lang_module['error'];
    $mess = $lang_module['sys_err'];

    //create
    if ($action == "create") {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_thing_to_do']]);
        }
        $name_f = $nv_Request->get_title("name_f", "post", '');
        $type = $nv_Request->get_int("type", "post", 0); //1 =  folder, 0 file
        if ($lev > 0) {
            $parentFileType = checkIfParentIsFolder($db, $lev);
            if ($type == 0 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_create_file_in_file.']]);
            }

            if ($type == 1 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_create_file_in_file.']]);
            }
        }

        if (!empty($name_f)) {

            $file_path = $base_dir . '/' . $name_f;
            if (file_exists($file_path)) {
                $status = 'error';
                $mess = $lang_module['f_has_exit'];
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

            if ($type == 1) {
                //tao folder
                $check_dir = nv_mkdir($full_dir, $name_f);
                $status = $check_dir[0] == 1 ? 'success' : 'error';
                $mess = $check_dir[1];
            } else {
                $mess = $lang_module['cannot_create_file'];
                //tao file
                $_dir = file_put_contents($full_dir . '/' . $name_f, '');
                if (isset($_dir)) {
                    $status = 'success';
                    $mess = $lang_module['create_ok'];
                }
            }

            if ($status == 'success') {
                $exe = $stmt->execute();
                $file_id = $db->lastInsertId();
                $sql1 = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions (file_id, p_group, p_other, updated_at) 
                    VALUES (:file_id, :p_group, :p_other, :updated_at)";
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $exe = $stmta->execute();
                updateLog($lev);
                $mess = $lang_module['create_ok'];
            }
        }
    }

    if ($action == 'delete') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_thing_to_do']]);
        }
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $checksess = $nv_Request->get_title('checksess', 'get', '');
        if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                $status = 'success';
                updateLog($lev);
                $mess = $lang_module['delete_ok'];
            } else {
                $status = 'error';
                $mess =  $lang_module['delete_false'];
            }
        }
    }

    if ($action == 'deleteAll') { 
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_thing_to_do']]);
        }

        $checksessArray = $nv_Request->get_array('checksess', 'post', []);

        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        foreach ($fileIds as $index => $fileId) {
            $fileId = (int)$fileId;
            $checksess = isset($checksessArray[$index]) ? $checksessArray[$index] : '';

            if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
                $deleted = deleteFileOrFolder($fileId);
                if ($deleted) {
                    updateLog($lev);
                    $mess = $lang_module['delete_ok'];
                } else {
                    $mess_content = $lang_module['delete_false'];
                }
            } else {
                $mess = $lang_module['checksess_invalid'];
            }
        }
    }

    if ($action == 'rename') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_thing_to_do']]);
        }
        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        $newName = trim($nv_Request->get_title('new_name', 'post', ''));

        $sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id =" . $fileId;
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $file = $stmt->fetch();

        $status = 'error';
        $mess = $lang_module['f_has_exit'];
        if ($file) {
            $oldFilePath = $file['file_path'];
            $oldFullPath = NV_ROOTDIR . '/' . $oldFilePath;

            $newFilePath = dirname($oldFilePath) . '/' . $newName;
            $newFullPath = NV_ROOTDIR . '/' . $newFilePath;

            $childCount = $db->query("SELECT COUNT(*) FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = " . $fileId)->fetchColumn();
            if ($file['is_folder'] == 1 && $childCount > 0) {
                //$status = 'error';
                $mess = $lang_module['cannot_rename_file'];
            } else {
                $mess = $lang_module['cannot_rename_file'];
                if (rename($oldFullPath, $newFullPath)) {
                    $mess = $lang_module['cannot_update_db'];
                    $sqlUpdate = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET file_name = :new_name, file_path = :new_path, updated_at = :updated_at WHERE file_id = :file_id";
                    $stmtUpdate = $db->prepare($sqlUpdate);
                    $stmtUpdate->bindParam(':new_name', $newName);
                    $stmtUpdate->bindParam(':new_path', $newFilePath);
                    $stmtUpdate->bindParam(':file_id', $fileId, PDO::PARAM_INT);
                    $stmtUpdate->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    if ($stmtUpdate->execute()) {
                        $status = $lang_module['success'];
                        $mess = $lang_module['rename_ok'];
                    }
                }
            }
        }
    }

    if ($action == 'share') {
        if (!defined('NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_thing_to_do']]);
        }
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $share_option = $nv_Request->get_int('share_option', 'post', 0);

        if ($fileId > 0) {
            $sql = "UPDATE " . NV_PREFIXLANG . "_fileserver_files SET share = :share_option WHERE file_id = :file_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':share_option', $share_option, PDO::PARAM_INT);
            $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $status = $lang_module['success'];
                $mess = $lang_module['share_ok'];
            } else {
                $status = $lang_module['error'];
                $mess = $lang_module['share_false'];
            }
        }
    }

    if ($action == 'compress') {
        if (!defined(constant_name: 'NV_IS_SPADMIN')) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_thing_to_do']]);
        }

        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        $zipFileName = 'compressed_' . NV_CURRENTTIME . '.zip';
        $zipFilePath = $base_dir . '/' . $zipFileName;
        $zipFullPath = NV_ROOTDIR . $zipFilePath;

        $compressResult = compressFiles($fileIds, $zipFullPath);

        if ($compressResult['status'] === 'success') {
            $sqlInsert = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev, compressed) 
                          VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev, 1)";
            $stmtInsert = $db->prepare($sqlInsert);
            $stmtInsert->bindParam(':file_name', $zipFileName, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_path', $zipFilePath, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_size', filesize($zipFullPath), PDO::PARAM_INT);
            $stmtInsert->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
            $stmtInsert->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
            $stmtInsert->execute();


            $mess = $compressResult['message'];
        }
    }
    nv_jsonOutput(['status' => $status, 'message' => $mess]);
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);

    $sql = "SELECT file_path, file_name, is_folder, lev FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch();

    if ($file) {
        $file_path = NV_ROOTDIR . $file['file_path'];
        $file_name = $file['file_name'];
       
        if ($file['is_folder'] == 1) {
            $sqlFiles = "SELECT file_id FROM " . NV_PREFIXLANG . "_fileserver_files WHERE lev = :lev AND status = 1";
            $stmtFiles = $db->prepare($sqlFiles);
            $stmtFiles->bindParam(':lev', $file_id, PDO::PARAM_INT);
            $stmtFiles->execute();
            $filesInFolder = $stmtFiles->fetchAll(PDO::FETCH_COLUMN);

            $zipFileName = $file_name . '_' . NV_CURRENTTIME . '.zip';
            $zipFilePath = '/uploads/fileserver/' . $zipFileName;
            $zipFullPath = NV_ROOTDIR . '/' . $zipFilePath;

            if (empty($filesInFolder)) {
                $zip = new PclZip($zipFullPath);
                $zip->create(['']);  

                $_download = new NukeViet\Files\Download($zipFullPath, NV_ROOTDIR . '/uploads/fileserver/', $zipFileName, true, 0);
                $_download->download_file();
            }
            $compressResult = compressFiles($filesInFolder, $zipFullPath);

            if ($compressResult['status'] == 'success') {
                if (file_exists($zipFullPath)) {
                    $_download = new NukeViet\Files\Download($zipFullPath, NV_ROOTDIR . '/uploads/fileserver/', $zipFileName, true, 0);
                    $_download->download_file();
                }
            }
        } else {
            if (file_exists($file_path)) {
                $_download = new NukeViet\Files\Download($file_path, NV_ROOTDIR . '/uploads/fileserver/', $file_name, true, 0);
                $_download->download_file();

            }
        }
    }
}


$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'text';

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    if (!defined('NV_IS_SPADMIN')) {
        $error = $upload_info[$lang_module['not_thing_to_do']];
    }
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

        $file_id = $db->lastInsertId();
        $sql1 = "INSERT INTO " . NV_PREFIXLANG . "_fileserver_permissions (file_id, p_group, p_other, updated_at) 
            VALUES (:file_id, :p_group, :p_other, :updated_at)";
        $stmta = $db->prepare($sql1);
        $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
        $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
        $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
        $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmta->execute();

        updateLog($lev);

        nv_redirect_location($page_url);
    } else {
        $error = $upload_info['error'];
    }
}
$selected_all = ($search_type == 'all') ? ' selected' : '';
$selected_file = ($search_type == 'file') ? ' selected' : '';
$selected_folder = ($search_type == 'folder') ? ' selected' : '';

foreach($result as $row){
    $sql_logs = "SELECT log_id, total_size, total_files,total_folders FROM " . NV_PREFIXLANG . "_fileserver_logs WHERE lev = :lev";
    $sql_logs = $db->prepare($sql_logs);
    $sql_logs->bindParam(':lev', $row['lev'], PDO::PARAM_INT);
    $sql_logs->execute();
    $logs = $sql_logs->fetch(PDO::FETCH_ASSOC);

       $sql_permissions = "SELECT `p_group`, p_other FROM " . NV_PREFIXLANG . "_fileserver_permissions WHERE file_id = :file_id";
    $stmt_permissions = $db->prepare($sql_permissions);
    $stmt_permissions->bindParam(':file_id', $row['file_id'], PDO::PARAM_INT);
    $stmt_permissions->execute();
    $permissions = $stmt_permissions->fetch(PDO::FETCH_ASSOC);
}

$xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FORM_ACTION', $page_url);
$xtpl->assign('SEARCH_TERM', $search_term);

$xtpl->assign('SELECTED_ALL', $selected_all);
$xtpl->assign('SELECTED_FILE', $selected_file);
$xtpl->assign('SELECTED_FOLDER', $selected_folder);

if ($total > $perpage) {
    $page_url = $base_url.'&amp;lev=' . $lev.'&search='.$search_term.'&search_type='.$search_type;
    $generate_page = nv_generate_page($page_url, $total, $perpage, $page);
    $xtpl->assign('GENERATE_PAGE', $generate_page);
}

if ($error != '') {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}
if ($success != '') {
    $xtpl->assign('success', $success);
    $xtpl->parse('main.success');
}

foreach ($result as $row) { 
    $row['total_size'] = $logs['total_size'] ? number_format($logs['total_size'] / 1024, 2) . ' KB' : '--';
    $row['total_files'] = $logs['total_files'];
    $row['total_folders'] = $logs['total_folders'];

    $row['created_at'] = date("d/m/Y", $row['created_at']);

    $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
    if ($row['compressed'] == 1) {
        $row['icon_class'] = 'fa-file-archive-o';
    } else {
        $row['icon_class'] = $row['is_folder'] ? 'fa-folder-o' : 'fa-file-o';
    }

    if ($permissions) {
        $row['p_group'] = $permissions['p_group'];
        $row['p_other'] = $permissions['p_other'];
        $row['permissions'] = $row['p_group'] . $row['p_other'];
    } else {
        $row['permissions'] = 'N/A';
    }

    $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main&amp;lev=' . $row['file_id'];
    $row['url_perm'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm&amp;file_id=' . $row['file_id'];
    $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit&amp;file_id=' . $row['file_id']. "&page=".$page;
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
        $row['file_size'] = calculateFolderSize( $row['file_id']);
        $xtpl->assign('VIEW', $row['url_view']);
        $xtpl->parse('main.file_row.view');
    } else {
        $xtpl->assign('SHARE', $row['url_share']);
        $xtpl->parse('main.file_row.share');

        $xtpl->assign('VIEW', $row['url_edit']);
        $xtpl->parse('main.file_row.view');

        $xtpl->assign('COPY', $row['url_clone']);
        $xtpl->parse('main.file_row.copy');

        if ($fileInfo == 'txt') {
            $xtpl->assign('EDIT',  $row['url_edit']);
            $xtpl->parse('main.file_row.edit');
        }
    }

    $xtpl->assign('DOWNLOAD', $row['url_download']);
    $xtpl->parse('main.file_row.download');

    $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / 1024, 2) . ' KB' : '--';
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
