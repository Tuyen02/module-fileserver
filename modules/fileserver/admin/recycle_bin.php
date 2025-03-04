<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}

$error = '';
$success = '';

function purgeOldTrashItems()
{
    global $db, $module_data;

    $threshold = NV_CURRENTTIME - (30 * 24 * 60 * 60);
    $sql = 'SELECT file_id, file_path, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE deleted_at < :threshold';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
    $stmt->execute();
    $oldItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deletedFileIds = [];
    foreach ($oldItems as $item) {
        $fullPath = NV_ROOTDIR . $item['file_path'];
        if ($item['is_folder'] == 1 && is_dir($fullPath)) {
            nv_deletefile($fullPath, true);
        } elseif (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindValue(':file_id', $item['file_id'], PDO::PARAM_INT);
        $stmtDelete->execute();

        $deletedFileIds[] = $item['file_id'];
    }

    if (!empty($deletedFileIds)) {
        updateLog(0, 'auto_purge', implode(',', $deletedFileIds));
    }
}

purgeOldTrashItems();

$perpage = 5;
$page = $nv_Request->get_int('page', 'get', 1);
$lev = $nv_Request->get_int('lev', 'get', 0);

$search_term = $nv_Request->get_title('search', 'get', '');
$search_type = $nv_Request->get_title('search_type', 'get', 'all');

$base_dir = '/uploads/fileserver';
$full_dir = NV_ROOTDIR . $base_dir;
$base_url = NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=recycle_bin';
$page_url = $base_url;

$breadcrumbs = [];
$current_lev = $lev;

while ($current_lev > 0) {
    $sql1 = 'SELECT file_name, file_path, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = ' . $current_lev;
    $result1 = $db->query($sql1);
    $row1 = $result1->fetch();
    if ($row1) {
        $breadcrumbs[] = [
            'catid' => $current_lev,
            'title' => $row1['file_name'],
            'link' => $base_url . '&lev=' . $current_lev . '&page=' . $page
        ];
        $current_lev = $row1['lev'];
    } else {
        break;
    }
}

$breadcrumbs = array_reverse($breadcrumbs);
foreach ($breadcrumbs as $breadcrumb) {
    $array_mod_title[] = $breadcrumb;
}

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE status = 0 AND lev = :lev';
$total_sql = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE status = 0 AND lev = :lev';

if (!empty($search_term)) {
    $sql .= ' AND file_name LIKE :search_term';
    $total_sql .= ' AND file_name LIKE :search_term';
}

if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    $is_folder = ($search_type === 'file') ? 0 : 1;
    $sql .= ' AND is_folder = :is_folder';
    $total_sql .= ' AND is_folder = :is_folder';
}

$total_stmt = $db->prepare($total_sql);
$total_stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
if (!empty($search_term)) {
    $total_stmt->bindValue(':search_term', '%' . $search_term . '%', PDO::PARAM_STR);
}
if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    $total_stmt->bindValue(':is_folder', $is_folder, PDO::PARAM_INT);
}
$total_stmt->execute();
$total = $total_stmt->fetchColumn();

$sql .= ' ORDER BY file_id ASC LIMIT :limit OFFSET :offset';
$stmt = $db->prepare($sql);
$stmt->bindValue(':lev', $lev, PDO::PARAM_INT);
$stmt->bindValue(':limit', $perpage, PDO::PARAM_INT);
$stmt->bindValue(':offset', ($page - 1) * $perpage, PDO::PARAM_INT);
if (!empty($search_term)) {
    $stmt->bindValue(':search_term', '%' . $search_term . '%', PDO::PARAM_STR);
}
if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    $stmt->bindValue(':is_folder', $is_folder, PDO::PARAM_INT);
}
$stmt->execute();
$result = $stmt->fetchAll();

if ($lev > 0) {
    $base_dir = $db->query('SELECT file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = ' . $lev)->fetchColumn();
    $full_dir = NV_ROOTDIR . $base_dir;
    $page_url .= '&lev=' . $lev;
}

$action = $nv_Request->get_title('action', 'post', '');
$fileIds = $nv_Request->get_array('files', 'post', []);

if (!empty($action)) {
    $status = 'error';
    $mess = $lang_module['sys_err'];

    if ($action == 'delete') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $checksess = $nv_Request->get_title('checksess', 'get', '');
        if ($fileId <= 0 || $checksess != md5($fileId . NV_CHECK_SESSION)) {
            $mess = $lang_module['checksess_false'];
        } else {
            $deleted = deletePermanently($fileId);
            if ($deleted) {
                $status = 'success';
                updateLog($lev, 'delete_permanent', $fileId);
                $mess = $lang_module['delete_ok'];
            } else {
                $mess = $lang_module['delete_false'];
            }
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'deleteAll') {
        $checksessArray = $nv_Request->get_array('checksess', 'post', []);
        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        $deletedFileIds = [];
        foreach ($fileIds as $index => $fileId) {
            $fileId = (int) $fileId;
            $checksess = isset($checksessArray[$index]) ? $checksessArray[$index] : '';
            if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
                $deleted = deletePermanently($fileId);
                if ($deleted) {
                    $deletedFileIds[] = $fileId;
                }
            }
        }
        if (!empty($deletedFileIds)) {
            $status = 'success';
            updateLog($lev, 'delete_all_permanent', implode(',', $deletedFileIds));
            $mess = $lang_module['delete_ok'];
        } else {
            $mess = $lang_module['delete_false'];
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'restore') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        if ($fileId <= 0) {
            $mess = $lang_module['file_id_false'];
        } else {
            $restored = restoreFileOrFolder($fileId);
            if ($restored) {
                $status = 'success';
                updateLog($lev, 'restore', $fileId);
                $mess = $lang_module['restore_ok'];
            } else {
                $mess = $lang_module['restore_false'];
            }
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'restoreAll') {
        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        $restoredFileIds = [];
        foreach ($fileIds as $fileId) {
            $fileId = (int) $fileId;
            if ($fileId <= 0) {
                continue;
            }
            $restored = restoreFileOrFolder($fileId);
            if ($restored) {
                $restoredFileIds[] = $fileId;
            }
        }
        if (!empty($restoredFileIds)) {
            $status = 'success';
            updateLog($lev, 'restore_all', implode(',', $restoredFileIds));
            $mess = $lang_module['restore_ok'];
        } else {
            $mess = $lang_module['restore_false'];
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    nv_jsonOutput(['status' => 'error', 'message' => $lang_module['action_invalid']]);
}

function deletePermanently($fileId)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false;
    }

    $fullPath = NV_ROOTDIR . $row['file_path'];
    if ($row['is_folder'] == 1 && is_dir($fullPath)) {
        nv_deletefile($fullPath, true);
    } elseif (file_exists($fullPath)) {
        unlink($fullPath);
    }

    $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmtDelete->execute();

    return true;
}

function restoreFileOrFolder($fileId)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $fileId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (empty($row)) {
        return false;
    }

    $oldPath = NV_ROOTDIR . $row['file_path'];
    $newPathBase = str_replace('/data/tmp/trash/', '/uploads/fileserver/', $row['file_path']);
    $newPath = NV_ROOTDIR . $newPathBase;

    if (file_exists($newPath)) {
        $newPathBase = str_replace(NV_ROOTDIR, '', $newPath);
    }

    $parentDir = dirname($newPath);
    if (!file_exists($parentDir)) {
        mkdir($parentDir, 0777, true);
    }

    if (file_exists($oldPath)) {
        if (!rename($oldPath, $newPath)) {
            return false;
        }
    }

    $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                  (file_id, file_name, alias, file_path, file_size, uploaded_by, created_at, updated_at, is_folder, status, lev, view, share, compressed) 
                  VALUES (:file_id, :file_name, :alias, :file_path, :file_size, :uploaded_by, :created_at, :updated_at, :is_folder, 1, :lev, :view, :share, :compressed)';
    $stmtInsert = $db->prepare($sqlInsert);
    $stmtInsert->execute([
        ':file_id' => $row['file_id'],
        ':file_name' => $row['file_name'],
        ':alias' => $row['alias'],
        ':file_path' => $newPathBase,
        ':file_size' => $row['file_size'],
        ':uploaded_by' => $row['uploaded_by'],
        ':created_at' => NV_CURRENTTIME,
        ':updated_at' => 0,
        ':is_folder' => $row['is_folder'],
        ':lev' => $row['lev'],
        ':view' => $row['view'],
        ':share' => $row['share'],
        ':compressed' => $row['compressed']
    ]);

    $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
    $stmtDelete = $db->prepare($sqlDelete);
    $stmtDelete->bindValue(':file_id', $fileId, PDO::PARAM_INT);
    $stmtDelete->execute();

    if ($row['is_folder'] == 1) {
        restoreChildItems($fileId, $newPathBase);
    }

    return true;
}

function restoreChildItems($parentId, $parentNewPath)
{
    global $db, $module_data;

    $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE lev = :lev';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':lev', $parentId, PDO::PARAM_INT);
    $stmt->execute();
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($children as $child) {
        $oldChildPath = NV_ROOTDIR . $child['file_path'];
        $relativePath = str_replace('/data/tmp/trash/', '', $child['file_path']);
        $newChildPathBase = $parentNewPath . '/' . basename($relativePath);
        $newChildPath = NV_ROOTDIR . $newChildPathBase;

        if (file_exists($newChildPath)) {
            $newChildPathBase = str_replace(NV_ROOTDIR, '', $newChildPath);
        }

        $childParentDir = dirname($newChildPath);
        if (!file_exists($childParentDir)) {
            mkdir($childParentDir, 0777, true);
        }

        if (file_exists($oldChildPath)) {
            if (!rename($oldChildPath, $newChildPath)) {
                continue;
            }
        }

        $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                      (file_id, file_name, alias, file_path, file_size, uploaded_by, created_at, updated_at, is_folder, status, lev, view, share, compressed) 
                      VALUES (:file_id, :file_name, :alias, :file_path, :file_size, :uploaded_by, :created_at, :updated_at, :is_folder, 1, :lev, :view, :share, :compressed)';
        $stmtInsert = $db->prepare($sqlInsert);
        $stmtInsert->execute([
            ':file_id' => $child['file_id'],
            ':file_name' => $child['file_name'],
            ':alias' => $child['alias'],
            ':file_path' => $newChildPathBase,
            ':file_size' => $child['file_size'],
            ':uploaded_by' => $child['uploaded_by'],
            ':created_at' => NV_CURRENTTIME,
            ':updated_at' => 0,
            ':is_folder' => $child['is_folder'],
            ':lev' => $child['lev'],
            ':view' => $child['view'],
            ':share' => $child['share'],
            ':compressed' => $child['compressed']
        ]);

        $sqlDelete = 'DELETE FROM ' . NV_PREFIXLANG . '_' . $module_data . '_trash WHERE file_id = :file_id';
        $stmtDelete = $db->prepare($sqlDelete);
        $stmtDelete->bindValue(':file_id', $child['file_id'], PDO::PARAM_INT);
        $stmtDelete->execute();

        if ($child['is_folder'] == 1) {
            restoreChildItems($child['file_id'], $newChildPathBase);
        }
    }
}

$selected_all = ($search_type == 'all') ? ' selected' : '';
$selected_file = ($search_type == 'file') ? ' selected' : '';
$selected_folder = ($search_type == 'folder') ? ' selected' : '';
$nv_BotManager->setFollow()->setNoIndex();

$xtpl = new XTemplate('recycle_bin.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FORM_ACTION', $base_url);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('SEARCH_TERM', $search_term);
$xtpl->assign('SELECTED_ALL', $selected_all);
$xtpl->assign('SELECTED_FILE', $selected_file);
$xtpl->assign('SELECTED_FOLDER', $selected_folder);
$xtpl->assign('LEV', $lev);

if ($total > $perpage) {
    $page_url = $base_url . '&lev=' . $lev . '&search=' . $search_term . '&search_type=' . $search_type;
    $generate_page = nv_generate_page($page_url, $total, $perpage, $page);
    $xtpl->assign('GENERATE_PAGE', $generate_page);
    $xtpl->parse('main.generate_page');
}

if ($error) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}
if ($success) {
    $xtpl->assign('SUCCESS', $success);
    $xtpl->parse('main.success');
}

foreach ($result as $row) {
    $row['deleted_at'] = date('d/m/Y', $row['deleted_at']);
    $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
    $row['icon_class'] = getFileIconClass($row);
    $row['url_delete'] = $base_url . '&file_id=' . $row['file_id'] . '&action=delete&checksess=' . $row['checksess'];
    $row['url_restore'] = $base_url . '&file_id=' . $row['file_id'] . '&action=restore'; // Không cần checksess cho restore
    $row['file_size'] = $row['file_size'] ? number_format($row['file_size'] / 1024, 2) . ' KB' : '--';
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.file_row');
}

if ($_SERVER['REQUEST_URI'] != $base_url) {
    $xtpl->assign('BACK', '');
    $xtpl->parse('main.back');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';