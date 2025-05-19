<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}

$page_title = $lang_module['recycle_bin'];

$error = '';
$success = '';

$perpage = 20;
$page = $nv_Request->get_int('page', 'get', 1);
$generate_page = '';
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
    $sql1 = 'SELECT file_name, file_path, lev, alias, deleted_at FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 0 AND file_id = ' . $current_lev;
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

$sql = 'SELECT f.* FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f WHERE f.status = 0';

if ($lev > 0) {
    $sql .= ' AND f.lev = ' . $lev;
}

if (!empty($search_term)) {
    if (!empty($search_type) && $search_type == 'file') {
        $sql .= ' AND f.file_name LIKE :search_term AND f.is_folder = 0 AND NOT EXISTS (
            SELECT 1 FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files p 
            WHERE p.file_id = f.lev 
            AND p.status = 0 
            AND p.deleted_at = f.deleted_at
        )';
    } else {
        $sql .= ' AND f.file_name LIKE :search_term';
    }
}

if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    $is_folder = ($search_type == 'file') ? 0 : 1;
    $sql .= ' AND f.is_folder = :is_folder';
}

$stmt = $db->prepare($sql);
if (!empty($search_term)) {
    $quoted_search = $db->quote('%' . $search_term . '%');
    $stmt->bindValue(':search_term', trim($quoted_search, "'"), PDO::PARAM_STR);
}
if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
    $stmt->bindValue(':is_folder', $is_folder, PDO::PARAM_INT);
}
$stmt->execute();
$all_items = $stmt->fetchAll();

$root_items = array_filter($all_items, function ($item) {
    return $item['lev'] == 0;
});

$root_by_deleted_at = [];
foreach ($root_items as $item) {
    $root_by_deleted_at[$item['deleted_at']][] = $item['file_id'];
}

$other_items = array_filter($all_items, function ($item) {
    return $item['lev'] > 0;
});

$display_items = $root_items;
foreach ($other_items as $item) {
    $show = true;
    $current = $item;
    while ($current['lev'] > 0) {
        $parent = null;
        foreach ($all_items as $potential_parent) {
            if ($potential_parent['file_id'] == $current['lev']) {
                $parent = $potential_parent;
                break;
            }
        }
        if ($parent) {
            if ($parent['status'] == 0) {
                $show = false;
                break;
            }
            if ($parent['lev'] == 0 && $parent['deleted_at'] == $item['deleted_at']) {
                $show = false;
                break;
            }
            if ($parent['deleted_at'] == $item['deleted_at']) {
                $show = false;
                break;
            }
            $current = $parent;
        } else {
            break;
        }
    }
    if ($show) {
        $display_items[] = $item;
    }
}

usort($display_items, function ($a, $b) {
    return $b['deleted_at'] - $a['deleted_at'];
});

$total = count($display_items);
$display_items = array_slice($display_items, ($page - 1) * $perpage, $perpage);

if ($lev > 0) {
    $base_dir = $db->query('SELECT file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 0 AND file_id = ' . $lev)->fetchColumn();
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
            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                $status = 'success';
                updateLog($lev);
                $mess = $lang_module['delete_ok'];
                nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id: ' . $fileId, $admin_info['userid']);
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
                $deleted = deleteFileOrFolder($fileId);
                if ($deleted) {
                    $deletedFileIds[] = $fileId;
                }
            }
        }
        if (!empty($deletedFileIds)) {
            $status = 'success';
            updateLog($lev);
            nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id: ' . implode(',', $deletedFileIds), $admin_info['userid']);
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
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id: ' . $fileId, $admin_info['userid']);
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
            updateLog($lev);
            nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id: ' . implode(',', $restoredFileIds), $admin_info['userid']);
            $mess = $lang_module['restore_ok'];
        } else {
            $mess = $lang_module['restore_false'];
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    nv_jsonOutput(['status' => 'error', 'message' => $lang_module['action_invalid']]);
}

$selected_all = ($search_type == 'all') ? ' selected' : '';
$selected_file = ($search_type == 'file') ? ' selected' : '';
$selected_folder = ($search_type == 'folder') ? ' selected' : '';
$nv_BotManager->setFollow()->setNoIndex();

if ($total > $perpage) {
    $page_url = $base_url . '&lev=' . $lev . '&search=' . $search_term . '&search_type=' . $search_type;
    $generate_page = nv_generate_page($page_url, $total, $perpage, $page);
}

$xtpl = new XTemplate('recycle_bin.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('FORM_ACTION', $base_url);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('SEARCH_TERM', $search_term);
$xtpl->assign('SELECTED_ALL', $selected_all);
$xtpl->assign('SELECTED_FILE', $selected_file);
$xtpl->assign('SELECTED_FOLDER', $selected_folder);
$xtpl->assign('LEV', $lev);

$xtpl->assign('GENERATE_PAGE', $generate_page);
$xtpl->parse('main.generate_page');


if (!empty($display_items)) {
    $xtpl->parse('main.has_items');
    foreach ($display_items as $row) {
        $row['deleted_at'] = date('d/m/Y H:i:s', $row['created_at']);
        $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
        $row['icon_class'] = getFileIconClass($row);
        $row['url_delete'] = $base_url . '&file_id=' . $row['file_id'] . '&action=delete&checksess=' . $row['checksess'];
        $row['url_restore'] = $base_url . '&file_id=' . $row['file_id'] . '&action=restore';
        $row['file_size'] = nv_convertfromBytes($row['file_size']);
        if ($row['is_folder']) {
            $row['url_view'] = $base_url . '&lev=' . $row['file_id'];
        } else {
            $row['url_view'] = '';
        }
        $xtpl->assign('ROW', $row);
        $xtpl->parse('main.file_row');
    }
} else {
    $xtpl->parse('main.no_data');
}

if ($error) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}
if ($success) {
    $xtpl->assign('SUCCESS', $success);
    $xtpl->parse('main.success');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
