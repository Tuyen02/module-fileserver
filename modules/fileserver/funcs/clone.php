<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$status = '';
$message = '';

if (!defined('NV_IS_SPADMIN')) {
    $sql_per = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
    $result_per = $db->query($sql_per);
    $row_per = $result_per->fetch();

    if (empty($row_per) || ($row_per['p_group'] < 3 && $row_per['p_other'] < 3)) {
        $status = $lang_module['error'];
        $message = $lang_module['not_thing_to_do'];
    }
}

$rank = $nv_Request->get_int('rank', 'get', 0);
$copy = $nv_Request->get_int('copy', 'get', 0);
$move = $nv_Request->get_int('move', 'get', 0);
$root = $nv_Request->get_int('root', 'get', 0);
$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (!$row) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=clone');
}

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
$page_url = $base_url;
$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '/' . $row['alias'] . '-' . 'lev=' . $row['lev'];

$breadcrumbs = [];
$current_lev = $lev;

while ($current_lev > 0) {
    $sql1 = 'SELECT file_name, file_path, lev, alias, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $result1 = $db->query($sql1);
    $row1 = $result1->fetch();
    if ($row1['is_folder'] == 1) {
        $op = $module_info['alias']['main'];
    }
    $breadcrumbs[] = [
        'catid' => $current_lev,
        'title' => $row1['file_name'],
        'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row1['alias'] . '&page=' . $page
    ];
    $current_lev = $row1['lev'];
}

$breadcrumbs = array_reverse($breadcrumbs);

foreach ($breadcrumbs as $breadcrumb) {
    $array_mod_title[] = $breadcrumb;
}

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $row['file_path'];
$current_directory = dirname($full_path);
$lev = $row['lev'];

if ($rank > 0) {
    $lev = $rank;
    $base_url .= '&rank=' . $rank;
} elseif ($root == 1) {
    $lev = 0;
    $base_url .= '&root=1';
}

$sql = 'SELECT f.*, p.p_group, p.p_other 
        FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
        LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p 
        ON f.file_id = p.file_id
        WHERE f.lev = ' . $lev . ' 
        AND f.is_folder = 1 
        AND f.status = 1 
        ORDER BY f.file_id ASC';
$result = $db->query($sql);
$all_directories = $result->fetchAll();

function checkPermission($directory, $user_info, $is_admin = false)
{
    if ($is_admin) {
        return true;
    }

    if (isset($directory['userid']) && $directory['userid'] == $user_info['userid']) {
        return true;
    }

    if (isset($user_info['in_groups']) && is_array($user_info['in_groups'])) {
        if (isset($directory['p_group']) && $directory['p_group'] >= 2) {
            return true;
        }
    }

    return false;
}

$directories = [];
$has_root_level = false;

foreach ($all_directories as $dir) {
    if (checkPermission($dir, $user_info, defined('NV_IS_SPADMIN'))) {
        $directories[] = $dir;
        if ($dir['lev'] == 0) {
            $has_root_level = true;
        }
    }
}

if (empty($directories)) {
    $sql = 'SELECT f.*, p.p_group, p.p_other 
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
            LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p 
            ON f.file_id = p.file_id
            WHERE f.lev = 0 
            AND f.is_folder = 1 
            AND f.status = 1 
            ORDER BY f.file_id ASC';
    $result = $db->query($sql);
    $all_root_directories = $result->fetchAll();

    $directories = [];
    foreach ($all_root_directories as $dir) {
        if (checkPermission($dir, $user_info, defined('NV_IS_SPADMIN'))) {
            $directories[] = $dir;
            $has_root_level = true;
        }
    }
}

if ($copy == 1) {
    $status = 'error';
    $message = $lang_module['copy_false'];

    if ($root == 1) {
        $target_url = '/uploads/fileserver';
        $target_lev = 0;
    } else {
        $target_folder = $db->query('SELECT file_path, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank)->fetch();
        if (!$target_folder) {
            $status = 'error';
            $message = $lang_module['target_folder_not_found'];
            $contents = nv_fileserver_clone($row, $file_id, $file_name, $file_path, $status, $message, $selected_folder_path, $view_url, $directories, $page_url, $base_url, $has_root_level);
            include NV_ROOTDIR . '/includes/header.php';
            echo nv_site_theme($contents);
            include NV_ROOTDIR . '/includes/footer.php';
            exit();
        }
        $target_url = $target_folder['file_path'];
        $target_lev = $target_folder['file_id'];
    }

    $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_name = :file_name AND lev = :lev AND status = 1';
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->bindParam(':file_name', $row['file_name']);
    $stmtCheck->bindParam(':lev', $target_lev);
    $stmtCheck->execute();
    $existingFile = $stmtCheck->fetchColumn();

    if ($existingFile > 0) {
        $status = $lang_module['error'];
        $message = $lang_module['f_has_exit'];
    } else {
        $new_file_name = $row['file_name'];
        $new_file_path = $target_url . '/' . $new_file_name;
        if (copy(NV_ROOTDIR . $row['file_path'], NV_ROOTDIR . $new_file_path)) {
            $status = 'success';
            $message = $lang_module['copy_ok'];

            $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                           VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)';
            $stmt = $db->prepare($sql_insert);
            $stmt->bindParam(':file_name', $new_file_name, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $new_file_path, PDO::PARAM_STR);
            $stmt->bindParam(':file_size', $row['file_size'], PDO::PARAM_INT);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt->bindParam(':lev', $target_lev, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $new_file_id = $db->lastInsertId();
                updateAlias($new_file_id, $new_file_name);

                if ($use_elastic == 1 && !is_null($client)) {
                    $params = [
                        'index' => 'fileserver',
                        'id' => $new_file_id,
                        'body' => [
                            'file_name' => $new_file_name,
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
                        error_log($lang_module['elastic_index_response'] . json_encode($response));
                        $client->indices()->refresh(['index' => 'fileserver']);
                    } catch (Exception $e) {
                        error_log($lang_module['error_elastic_index'] . $e->getMessage());
                    }
                }

                if ($root == 1) {
                    $permissions = ['p_group' => 3, 'p_other' => 3];
                } else {
                    $sql_permissions = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = :folder_id';
                    $stmt_permissions = $db->prepare($sql_permissions);
                    $stmt_permissions->bindParam(':folder_id', $target_lev);
                    $stmt_permissions->execute();
                    $permissions = $stmt_permissions->fetch();
                }

                $sql_insert_permissions = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                                          VALUES (:file_id, :p_group, :p_other, :updated_at)';
                $stmt_permissions_insert = $db->prepare($sql_insert_permissions);
                $stmt_permissions_insert->bindParam(':file_id', $new_file_id);
                $stmt_permissions_insert->bindParam(':p_group', $permissions['p_group']);
                $stmt_permissions_insert->bindParam(':p_other', $permissions['p_other']);
                $stmt_permissions_insert->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt_permissions_insert->execute();

                updateLog($target_lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, 'copy', 'File id: ' . $new_file_id, $user_info['userid']);

                if ($target_lev > 0) {
                    updateParentFolderSize($target_lev);
                }
            }
        }
    }
}

if ($move == 1) {
    $status = 'error';
    $message = $lang_module['move_false'];

    if ($root == 1) {
        $target_url = '/uploads/fileserver';
        $target_lev = 0;
    } else {
        $target_folder = $db->query('SELECT file_path, file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank)->fetch();
        if (!$target_folder) {
            $status = 'error';
            $message = $lang_module['target_folder_not_found'];
            $contents = nv_fileserver_clone($row, $file_id, $file_name, $file_path, $status, $message, $selected_folder_path, $view_url, $directories, $page_url, $base_url, $has_root_level);
            include NV_ROOTDIR . '/includes/header.php';
            echo nv_site_theme($contents);
            include NV_ROOTDIR . '/includes/footer.php';
            exit();
        }
        $target_url = $target_folder['file_path'];
        $target_lev = $target_folder['file_id'];
    }

    $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_name = :file_name AND lev = :lev AND status = 1';
    $stmtCheck = $db->prepare($sqlCheck);
    $stmtCheck->bindParam(':file_name', $row['file_name']);
    $stmtCheck->bindParam(':lev', $target_lev);
    $stmtCheck->execute();
    $existingFile = $stmtCheck->fetchColumn();

    if ($existingFile > 0) {
        $status = $lang_module['error'];
        $message = $lang_module['f_has_exit'];
    } else {
        $new_file_path = $target_url . '/' . $row['file_name'];
        if (rename(NV_ROOTDIR . $row['file_path'], NV_ROOTDIR . $new_file_path)) {
            $status = 'success';
            $message = $lang_module['move_ok'];

            $sql_update = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                           SET file_path = :file_path, lev = :lev, elastic = :elastic, updated_at = :updated_at 
                           WHERE file_id = :file_id';
            $stmt = $db->prepare($sql_update);
            $stmt->bindParam(':file_path', $new_file_path, PDO::PARAM_STR);
            $stmt->bindParam(':lev', $target_lev, PDO::PARAM_INT);
            $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $stmt->bindValue(':elastic', 0, PDO::PARAM_INT);
            $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);

            if ($stmt->execute()) {
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
                    try {
                        $response = $client->update($params);
                        error_log("Elasticsearch update response: " . json_encode($response));
                        $client->indices()->refresh(['index' => 'fileserver']);
                    } catch (Exception $e) {
                        error_log($lang_module['error_update_elastic'] . $e->getMessage());
                    }
                }

                if ($root == 1) {
                    $permissions = ['p_group' => 3, 'p_other' => 3];
                } else {
                    $sql_permissions = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = :folder_id';
                    $stmt_permissions = $db->prepare($sql_permissions);
                    $stmt_permissions->bindParam(':folder_id', $target_lev);
                    $stmt_permissions->execute();
                    $permissions = $stmt_permissions->fetch();
                }

                $sql_insert_permissions = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                                          VALUES (:file_id, :p_group, :p_other, :updated_at)';
                $stmt_permissions_insert = $db->prepare($sql_insert_permissions);
                $stmt_permissions_insert->bindParam(':file_id', $file_id);
                $stmt_permissions_insert->bindParam(':p_group', $permissions['p_group']);
                $stmt_permissions_insert->bindParam(':p_other', $permissions['p_other']);
                $stmt_permissions_insert->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmt_permissions_insert->execute();

                updateLog($target_lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, 'move', 'File id: ' . $file_id, $user_info['userid']);

                if ($target_lev > 0) {
                    updateParentFolderSize($target_lev);
                }
            }
        }
    }
}

$selected_folder_path = '';
if ($rank > 0) {
    $target_folder = $db->query('SELECT file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $rank)->fetch();
    if ($target_folder) {
        $selected_folder_path = $target_folder['file_path'];
    }
} elseif ($root == 1) {
    $selected_folder_path = '/uploads/fileserver/';
}

$contents = nv_fileserver_clone($row, $file_id, $file_name, $file_path, $status, $message, $selected_folder_path, $view_url, $directories, $page_url, $base_url, $has_root_level);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
