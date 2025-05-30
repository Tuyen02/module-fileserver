<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$rank = $nv_Request->get_int('rank', 'get', 0);
$copy = $nv_Request->get_int('copy', 'get', 0);
$move = $nv_Request->get_int('move', 'get', 0);
$root = $nv_Request->get_int('root', 'get', 0);
$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT file_id, file_name, file_path, file_size, is_folder, lev, alias, uploaded_by FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE is_folder != 1 AND file_id = ' . $file_id;
$row = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

if (empty($row)) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

if (!defined('NV_IS_SPADMIN')) {
    $is_group_user = !empty($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
    $current_permission = get_user_permission($lev, $row['uploaded_by']);
    if ($current_permission < 3 || empty($user_info)) {
        nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
    }
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$lev = $row['lev'];

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
$page_url = $base_url;
$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '/' . $row['alias'] . '-lev=' . $lev;

$selected_folder_path = '';
$status = 'error';
$message = '';
$reponse = [];
$action_note = '';
$target_url = '';

$breadcrumbs[] = [
    'catid' => $row['lev'],
    'title' => $row['file_name'],
    'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias']
];
$current_lev = $row['lev'];

while ($current_lev > 0) {
    $sql = 'SELECT file_name, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $_row = $db->query($sql)->fetch();
    if (empty($_row)) {
        break;
    }
    $op = $_row['is_folder'] == 1 ? $module_info['alias']['main'] : $op;
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $_row['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $_row['alias']
    ];
    $current_lev = $_row['lev'];
}
$breadcrumbs = array_reverse($breadcrumbs);
$array_mod_title = array_merge(isset($array_mod_title) ? $array_mod_title : [], $breadcrumbs);
$folder_tree = buildFolderTree($user_info, $page_url, 0);

if ($rank > 0) {
    $lev = $rank;
    $base_url .= '&rank=' . $rank;
    $sql = 'SELECT file_name, file_path, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank;
    $target_folder = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    $selected_folder_path = $target_folder['file_name'];
    if (!empty($target_folder)) {
        $target_url = $target_folder['file_path'];
        $target_lev = $target_folder['file_id'];
        $permissions = $db->query('SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $target_lev)
            ->fetch(PDO::FETCH_ASSOC);
    } 
} elseif ($root == 1) {
    $lev = 0;
    $base_url .= '&root=1';
    $selected_folder_path = $lang_module['root'];
    $target_url = $base_dir;
    $target_lev = 0;
    $permissions = ['p_group' => 3, 'p_other' => 3];
}

if (($move == 1 || $copy == 1) && $message == '') {
    if (empty($target_lev)) {
        $message = $lang_module['target_folder_not_found'];
    }
    $new_file_path = $target_url . '/' . $file_name;

    if (file_exists(NV_ROOTDIR . $new_file_path)) {
        if (md5_file($full_path) === md5_file(NV_ROOTDIR . $new_file_path)) {
            $message = $lang_module['no_changes'];
        } else {
            unlink(NV_ROOTDIR . $new_file_path);
        }
    }

    if ($move == 1 && $message == '') {
        if (!file_exists($full_path)) {
            $message = $lang_module['file_not_found'];
        }
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
                    $action_note = 'Replace file_id: ' . $file_id . ' to ' . $rank;

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
                    $action_note = 'Move file_id: ' . $file_id;
                }

                if ($use_elastic == 1 && $client != null) {
                    try {
                        $client->update([
                            'index' => 'fileserver',
                            'id' => $file_id,
                            'body' => [
                                'doc' => [
                                    'file_path' => $new_file_path,
                                    'lev' => $target_lev,
                                    'updated_at' => NV_CURRENTTIME
                                ]
                            ]
                        ]);
                        $client->indices()
                            ->refresh(['index' => 'fileserver']);
                    } catch (Exception $e) {
                        error_log($lang_module['error_elastic_index'] . $e->getMessage());
                    }
                }


                nv_insert_logs(NV_LANG_DATA, $module_name, 'move', $action_note, $user_info['userid']);
                if ($target_lev > 0) {
                    updateParentFolderSize($target_lev);
                }
                if ($row['lev'] > 0) {
                    updateParentFolderSize($row['lev']);
                }
                updateStat($target_lev);

                $db->commit();
                $status = 'success';
                $message = $lang_module['move_ok'];
            } catch (Exception $e) {
                $db->rollBack();
                $message = $e->getMessage();
            }
        }
    } else {
        if ($message == '' && copy($full_path, NV_ROOTDIR . $new_file_path)) {
            $check_fileid = $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                        WHERE status = 1 AND file_name = ' . $db->quote($file_name) . ' AND lev = ' . $target_lev)->fetchColumn();

            if ($check_fileid > 0) {
                $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                            SET file_size = :file_size, elastic = 0, updated_at = ' . NV_CURRENTTIME . ' 
                            WHERE file_id = ' . $check_fileid;
                $stmt = $db->prepare($sql);
                $action_note = 'Replace file_id: ' . $file_id . ' to ' . $rank;
            } else {
                $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                        VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)';
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
                $stmt->bindParam(':file_path', $new_file_path, PDO::PARAM_STR);
                $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
                $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt->bindParam(':lev', $target_lev, PDO::PARAM_INT);
            }

            $stmt->bindParam(':file_size', $row['file_size'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                $action_note = 'Copy file_id: ' . $file_id . ' to ' . $check_fileid;
                $new_file_id = $db->lastInsertId();
                updateAlias($new_file_id, $file_name);

                if ($use_elastic == 1 && $client != null) {
                    try {
                        $client->index([
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
                        ]);
                        $client->indices()
                            ->refresh(['index' => 'fileserver']);
                    } catch (Exception $e) {
                        error_log($lang_module['error_elastic_index'] . $e->getMessage());
                    }
                }

                $check_permission = $db->query('SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $new_file_id)->fetchColumn();
                if ($check_permission == 0) {
                    $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at)
                                VALUES (:file_id, :p_group, :p_other, :updated_at)';
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':file_id', $new_file_id, PDO::PARAM_INT);
                    $stmt->bindParam(':p_group', $permissions['p_group'], PDO::PARAM_INT);
                    $stmt->bindParam(':p_other', $permissions['p_other'], PDO::PARAM_INT);
                    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                    $stmt->execute();
                }

                nv_insert_logs(NV_LANG_DATA, $module_name, 'copy', $action_note, $user_info['userid']);
                if ($target_lev > 0) {
                    updateParentFolderSize($target_lev);
                }
                updateStat($target_lev);

                $status = 'success';
                $message = $lang_module['copy_ok'];
            }
        }
    }
}

$reponse = [
    'status' => $status,
    'message' => $message,
];

$contents = nv_fileserver_clone($row, $reponse, $selected_folder_path, $view_url, $folder_tree, $base_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
