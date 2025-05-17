<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$status = '';
$message = '';
$rank = $nv_Request->get_int('rank', 'get', 0);
$copy = $nv_Request->get_int('copy', 'get', 0);
$move = $nv_Request->get_int('move', 'get', 0);
$root = $nv_Request->get_int('root', 'get', 0);
$page = $nv_Request->get_int('page', 'get', 1);

$current_permission = get_user_permission($lev, $row);
if (!defined('NV_IS_SPADMIN')) {
    $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
    if ($current_permission < 3 || empty($user_info)) {
        nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
    }
}

$sql = 'SELECT file_name, file_path, file_size, is_folder, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $file_id;
$stmt = $db->prepare($sql);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (empty($row) || $row['is_folder'] == 1) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
$page_url = $base_url;
$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '/' . $row['alias'] . '-' . 'lev=' . $row['lev'];

$breadcrumbs = [];
$current_lev = $lev;
while ($current_lev > 0) {
    $sql_check = 'SELECT file_name, file_path, is_folder, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' .$current_lev;
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute();
    $row1 = $stmt_check->fetch(PDO::FETCH_ASSOC);
    if (empty($row1)) {
        break;
    }
    $op_alias = ($row1['is_folder'] == 1) ? $module_info['alias']['main'] : $op;
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $row1['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op_alias . '/' . $row1['alias']
    ];
    $current_lev = $row1['lev'];
}
$breadcrumbs = array_reverse($breadcrumbs);
$array_mod_title = array_merge($array_mod_title ?? [], $breadcrumbs);

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$lev = $row['lev'];

if ($rank > 0) {
    $lev = $rank;
    $base_url .= '&rank=' . $rank;
} elseif ($root == 1) {
    $lev = 0;
    $base_url .= '&root=1';
}

$folder_tree = buildFolderTree($user_info, $page_url, defined('NV_IS_SPADMIN'), 0);
$has_root_level = !empty($folder_tree);

if ($copy == 1) {
    $status = 'error';
    $message = $lang_module['copy_false'];

    if ($root == 1) {
        $target_url = $base_dir;
        $target_lev = 0;
    } else {
        $sql_target = 'SELECT file_path, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank;
        $stmt_target = $db->prepare($sql_target);
        $stmt_target->execute();
        $target_folder = $stmt_target->fetch(PDO::FETCH_ASSOC);
        if (!$target_folder) {
            $message = $lang_module['target_folder_not_found'];
        }
        $target_url = $target_folder['file_path'];
        $target_lev = $target_folder['file_id'];
    }

    if (!isset($target_lev)) {
        $message = $lang_module['please_select_folder'];
    } else {
        $sql_check = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND file_name = ' . $db->quote($file_name) . ' AND lev = ' . $target_lev;
        $stmt_check = $db->prepare($sql_check);
        $stmt_check->execute();

        if ($stmt_check->fetchColumn() > 0) {
            $message = $lang_module['f_has_exit'];
        } else {
            $new_file_path = $target_url . '/' . $file_name;
            if (copy($full_path, NV_ROOTDIR . $new_file_path)) {
                $status = 'success';
                $message = $lang_module['copy_ok'];

                $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                               VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)';
                $stmt_insert = $db->prepare($sql_insert);
                $stmt_insert->bindParam(':file_name', $file_name, PDO::PARAM_STR);
                $stmt_insert->bindParam(':file_path', $new_file_path, PDO::PARAM_STR);
                $stmt_insert->bindParam(':file_size', $row['file_size'], PDO::PARAM_INT);
                $stmt_insert->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
                $stmt_insert->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt_insert->bindParam(':lev', $target_lev, PDO::PARAM_INT);

                if ($stmt_insert->execute()) {
                    $new_file_id = $db->lastInsertId();
                    updateAlias($new_file_id, $file_name);

                    if ($use_elastic == 1 && !is_null($client)) {
                        $params = [
                            'index' => 'fileserver',
                            'id' => $new_file_id,
                            'body' => [
                                'file_name' => $file_name,
                                'file_path' => $new_file_path,
                                'file_size' => $row['file_size'],
                                'uploaded_by' => $user_info['userid'],
                                'created_at' => NV_CURRENTTIME,
                                'lev' => $target_lev,
                                'is_folder' => 0
                            ]
                        ];
                        try {
                            $response = $client->index($params);
                            $client->indices()->refresh(['index' => 'fileserver']);
                        } catch (Exception $e) {
                            error_log($lang_module['error_elastic_index'] . $e->getMessage());
                        }
                    }

                    $permissions = ($root == 1) ? ['p_group' => 3, 'p_other' => 3] : $db->query('SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $target_lev)->fetch(PDO::FETCH_ASSOC);
                    $sql_perm = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                                 VALUES (:file_id, :p_group, :p_other, :updated_at)';
                    $stmt_perm = $db->prepare($sql_perm);
                    $stmt_perm->bindParam(':file_id', $new_file_id, PDO::PARAM_INT);
                    $stmt_perm->bindParam(':p_group', $permissions['p_group'], PDO::PARAM_INT);
                    $stmt_perm->bindParam(':p_other', $permissions['p_other'], PDO::PARAM_INT);
                    $stmt_perm->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    $stmt_perm->execute();

                    updateLog($target_lev);
                    nv_insert_logs(NV_LANG_DATA, $module_name, 'copy', 'File id: ' . $new_file_id, $user_info['userid']);

                    if ($target_lev > 0) {
                        updateParentFolderSize($target_lev);
                    }
                }
            }
        }
    }
}

if ($move == 1) {
    $status = 'error';
    $message = $lang_module['move_false'];

    try {
        if ($root == 1) {
            $target_url = $base_dir;
            $target_lev = 0;
        } else {
            $sql_target = 'SELECT file_path, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id =' . $rank;
            $stmt_target = $db->prepare($sql_target);
            $stmt_target->execute();
            $target_folder = $stmt_target->fetch(PDO::FETCH_ASSOC);
            if (!$target_folder) {
               $message = $lang_module['target_folder_not_found'];
            }
            $target_url = $target_folder['file_path'];
            $target_lev = $target_folder['file_id'];
        }

        $sql_check = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                      WHERE status = 1 AND file_name = ' . $db->quote($file_name) . ' AND lev = ' . $target_lev . ' AND file_id != ' . $file_id . '';
        $stmt_check = $db->prepare($sql_check);
        $stmt_check->execute();

        if ($stmt_check->fetchColumn() > 0) {
            $message = $lang_module['f_has_exit'];
        }

        $new_file_path = $target_url . '/' . $file_name;
        if (!rename($full_path, NV_ROOTDIR . $new_file_path)) {
            $message = $lang_module['move_false'];
        }

        $db->beginTransaction();
        try {
            $sql_update = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                           SET file_path = :file_path, lev = :lev, elastic = 0, updated_at = :updated_at 
                           WHERE file_id = :file_id';
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->bindParam(':file_path', $new_file_path, PDO::PARAM_STR);
            $stmt_update->bindParam(':lev', $target_lev, PDO::PARAM_INT);
            $stmt_update->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $stmt_update->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt_update->execute();

            if ($use_elastic == 1 && !is_null($client)) {
                $params = [
                    'index' => 'fileserver',
                    'id' => $file_id,
                    'body' => [
                        'doc' => [
                            'file_path' => $new_file_path,
                            'lev' => $target_lev,
                            'updated_at' => NV_CURRENTTIME
                        ]
                    ]
                ];
                $client->update($params);
                $client->indices()->refresh(['index' => 'fileserver']);
            }

            updateLog($target_lev);
            nv_insert_logs(NV_LANG_DATA, $module_name, 'move', 'File id: ' . $file_id, $user_info['userid']);

            if ($target_lev > 0) {
                updateParentFolderSize($target_lev);
            }
            if ($row['lev'] > 0) {
                updateParentFolderSize($row['lev']);
            }

            $db->commit();
            $status = 'success';
            $message = $lang_module['move_ok'];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        $status = 'error';
        $message = $e->getMessage();
    }
}

$selected_folder_path = '';
if ($rank > 0) {
    $sql_target = 'SELECT file_name FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank;
    $stmt_target = $db->prepare($sql_target);
    $stmt_target->execute();
    $target_folder = $stmt_target->fetch(PDO::FETCH_ASSOC);
    if ($target_folder) {
        $selected_folder_path = $target_folder['file_name'];
    }
} elseif ($root == 1) {
    $selected_folder_path = $lang_module['root'];
}

$contents = nv_fileserver_clone($file_id, $file_name, $file_path, $status, $message, $selected_folder_path, $view_url, $folder_tree, $base_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';