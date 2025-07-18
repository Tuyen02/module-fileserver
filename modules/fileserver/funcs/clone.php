<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$rank = $nv_Request->get_int('rank', 'post', 0);
$root = $nv_Request->get_int('root', 'post', 0);
$action = $nv_Request->get_string('action', 'post', '');
$copy = ($action == 'copy') ? 1 : 0;
$move = ($action == 'move') ? 1 : 0;

$sql = 'SELECT file_id, file_name, file_path, file_size, is_folder, lev, alias, uploaded_by FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE is_folder != 1 AND file_id = ' . $file_id;
$row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name;

if (empty($row)) {
    nv_redirect_location($base_url);
}

if (!defined('NV_IS_SPADMIN')) {
    $is_group_user = !empty($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
    $current_permission = get_user_permission($lev, $row['uploaded_by']);
    if ($current_permission < 3 || empty($user_info)) {
        nv_redirect_location($base_url);
    }
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$lev = $row['lev'];

$page_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
$view_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '/' . $row['alias'] . '-lev=' . $lev;

$selected_folder_path = '';
$status = 'error';
$message = '';
$action_note = '';
$target_url = '';

$array_mod_title = build_breadcrumbs($row, $page_url, $base_url);

$folder_tree = buildFolderTree($user_info, $page_url, 0);

$target_lev = 0;
$target_url = '';
$selected_folder_path = '';
$permissions = ['p_group' => 3, 'p_other' => 3];

if ($root == 1) {
    $lev = 0;
    $base_url .= '&root=1';
    $selected_folder_path = $lang_module['root'];
    $target_url = $base_dir;
    $alias = '';
} elseif ($rank > 0) {
    $lev = $rank;
    $base_url .= '&rank=' . $rank;
    $sql = 'SELECT file_name, file_path, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank;
    $target_folder = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    if (!empty($target_folder)) {
        $selected_folder_path = $target_folder['file_name'];
        $target_url = $target_folder['file_path'];
        $target_lev = $target_folder['file_id'];
        $permissions = $db->query('SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $target_lev)
            ->fetch(PDO::FETCH_ASSOC);
    }
    $alias = change_alias($selected_folder_path . '_' . $target_lev);
}

if (($move == 1 || $copy == 1)) {
    if (empty($target_url)) {
        $message = $lang_module['target_folder_not_found'];
        nv_jsonOutput(['status' => 'error', 'message' => $message]);
    }

    $new_file_path = $target_url . '/' . $file_name;

    if (!file_exists($full_path)) {
        $message = $lang_module['file_not_found'];
        nv_jsonOutput(['status' => 'error', 'message' => $message]);
    }

    if (realpath($full_path) == realpath(NV_ROOTDIR . $new_file_path)) {
        $message = $lang_module['file_already_in_location'];
        nv_jsonOutput(['status' => 'error', 'message' => $message]);
    }

    if (file_exists(NV_ROOTDIR . $new_file_path)) {
        $overwrite = $nv_Request->get_int('overwrite', 'post', 0);
        if ($overwrite == 0) {
            nv_jsonOutput([
                'status' => 'warning',
                'message' => $lang_module['file_exists_confirm'],
                'requires_overwrite_confirmation' => true
            ]);            
        }
        if (!unlink(NV_ROOTDIR . $new_file_path)) {
            $message = $lang_module['error_delete_file'] . ': ' . $new_file_path;
            nv_jsonOutput(['status' => 'error', 'message' => $message]);
        }
    }

    if ($move == 1) {
        if (rename($full_path, NV_ROOTDIR . $new_file_path)) {
            $db->beginTransaction();
            try {
                $check_fileid = $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                    WHERE status = 1 AND file_name = ' . $db->quote($file_name) . ' AND lev = ' . $target_lev)->fetchColumn();

                if ($check_fileid > 0) {
                    $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                    SET file_size = :file_size, elastic = 0, updated_at = :updated_at 
                                    WHERE file_id = ' . $check_fileid;
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':file_size', $row['file_size'], PDO::PARAM_INT);
                    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    $stmt->execute();
                    $action_note = 'Replace file_id: ' . $file_id . ' to ' . $check_fileid;

                    $db->exec('UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET status = 0 WHERE file_id = ' . $file_id);
                    $file_id = $check_fileid;
                } else {
                    $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                    SET file_path = :file_path, lev = :lev, elastic = 0, updated_at = :updated_at 
                                    WHERE file_id = :file_id';
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':file_path', $new_file_path, PDO::PARAM_STR);
                    $stmt->bindParam(':lev', $target_lev, PDO::PARAM_INT);
                    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
                    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    $stmt->execute();
                    $action_note = 'Move file_id: ' . $file_id . ' to ' . $target_lev;
                }

                if ($use_elastic == 1 && $client != null) {
                    //
                }

                nv_insert_logs(NV_LANG_DATA, $module_name, 'move', $action_note, $user_info['userid']);
                if ($row['lev'] > 0) {
                    updateParentFolderSize($row['lev']);
                }

                $db->commit();
                $status = 'success';
                $message = $lang_module['move_ok'];
            } catch (Exception $e) {
                $db->rollBack();
                $message = $e->getMessage();
                nv_jsonOutput(['status' => 'error', 'message' => $message]);
            }
        } else {
            $message = $lang_module['error_move_file'];
            nv_jsonOutput(['status' => 'error', 'message' => $message]);            
        }
    } else { 
        $file_info = pathinfo($file_name);
        $base_name = $file_info['filename'];
        $extension = isset($file_info['extension']) ? $file_info['extension'] : '';

        $actual_new_file_path = $new_file_path;
        $file_name_for_db = $file_name;

        if (realpath($full_path) === realpath(NV_ROOTDIR . $new_file_path) || file_exists(NV_ROOTDIR . $new_file_path)) {
            $suggested_file_name = suggestNewName($target_lev, $base_name, $extension, 0);
            $actual_new_file_path = $target_url . '/' . $suggested_file_name;
            $file_name_for_db = $suggested_file_name;
        }

        if (copy($full_path, NV_ROOTDIR . $actual_new_file_path)) {
            $check_fileid = $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                        WHERE status = 1 AND file_name = ' . $db->quote($file_name_for_db) . ' AND lev = ' . $target_lev)->fetchColumn();

            if ($check_fileid > 0) {
                $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                            SET file_size = :file_size, elastic = 0, updated_at = ' . NV_CURRENTTIME . ' 
                            WHERE file_id = ' . $check_fileid;
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':file_size', $row['file_size'], PDO::PARAM_INT);
                $stmt->execute();
                $action_note = 'Replace file_id: ' . $file_id . ' to ' . $check_fileid;
            } else {
                $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                        VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)';
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':file_name', $file_name_for_db, PDO::PARAM_STR);
                $stmt->bindParam(':file_path', $actual_new_file_path, PDO::PARAM_STR);
                $stmt->bindParam(':file_size', $row['file_size'], PDO::PARAM_INT);
                $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
                $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindParam(':lev', $target_lev, PDO::PARAM_INT);
                $stmt->execute();
                $new_file_id_inserted = $db->lastInsertId();
                updateAlias($new_file_id_inserted, $file_name_for_db);
                $action_note = 'Copy file_id: ' . $file_id . ' to ' . $new_file_id_inserted;

                if ($use_elastic == 1 && $client != null) {
                    //
                }

                $check_permission = $db->query('SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $new_file_id_inserted)->fetchColumn();
                if ($check_permission == 0) {
                    $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at)
                                VALUES (:file_id, :p_group, :p_other, :updated_at)';
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':file_id', $new_file_id_inserted, PDO::PARAM_INT);
                    $stmt->bindParam(':p_group', $permissions['p_group'], PDO::PARAM_INT);
                    $stmt->bindParam(':p_other', $permissions['p_other'], PDO::PARAM_INT);
                    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    $stmt->execute();
                }
            }
            nv_insert_logs(NV_LANG_DATA, $module_name, 'copy', $action_note, $user_info['userid']);
            $message = $lang_module['copy_ok'];
        } else {
            $message = $lang_module['error_copy_file'];
            nv_jsonOutput(['status' => 'error', 'message' => $message]);
        }
    }
    if ($target_lev > 0) {
        updateParentFolderSize($target_lev);
    }
    updateStat($target_lev);

    $redirect_url = '';
    if ($root == 1) {
        $redirect_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'];
    } else {
        $sql = 'SELECT alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $target_lev;
        $target_alias = $db->query($sql)->fetchColumn();
        if (!empty($target_alias)) {
            $redirect_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '/' . $target_alias;
        } else {
            $redirect_url = $base_url . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'];
        }
    }
    nv_jsonOutput(['status' => 'success', 'message' => $message, 'redirect' => $redirect_url]);
}

$contents = nv_fileserver_clone($row, $selected_folder_path, $view_url, $folder_tree, $base_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';