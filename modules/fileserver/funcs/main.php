<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $module_info['site_title'];
$key_words = $module_info['keywords'];
$description = $module_info['description'];

$perpage = 20;
$page = $nv_Request->get_int('page', 'get', 1);
$search_term = $nv_Request->get_title('search', 'get', '');
$search_type = $nv_Request->get_title('search_type', 'get', 'all');

$base_dir = '/uploads/fileserver';
$tmp_dir = '/data/tmp/';
$back_url = '';
$full_dir = NV_ROOTDIR . $base_dir;
$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name;

$page_url = $base_url;

$breadcrumbs = [];
$current_lev = $lev;

while ($current_lev > 0) {
    $sql1 = 'SELECT file_name, file_path, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $current_lev;
    $result1 = $db->query($sql1);
    $row1 = $result1->fetch();
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

$file_ids = array_keys($arr_per);
$result = [];
$total = 0;


$result = [];
$total = 0;

if ($use_elastic == 1) {
    try {
        $searchParams = [
            'index' => 'fileserver',
            'body' => [
                'from' => ($page - 1) * $perpage,
                'size' => $perpage,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['status' => 1]],
                            ['term' => ['lev' => $lev]]
                        ]
                    ]
                ],
                'sort' => [
                    'file_id' => ['order' => 'asc']
                ]
            ]
        ];

        if (!defined('NV_IS_SPADMIN')) {
            $sql_perm = 'SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE ';
            if (isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array))) {
                $sql_perm .= 'p_group >= 2';
            } else {
                $sql_perm .= 'p_other = 2';
            }
            $allowed_files = $db->query($sql_perm)->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($allowed_files)) {
                $searchParams['body']['query']['bool']['filter'][] = [
                    'terms' => ['file_id' => $allowed_files]
                ];
            } else {
                $result = [];
                $total = 0;
            }
        }

        if (!empty($search_term)) {
            $searchParams['body']['query']['bool']['filter'][] = [
                'wildcard' => ['file_name' => '*' . $search_term . '*']
            ];
        }

        if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
            $is_folder = ($search_type == 'file') ? 0 : 1;
            $searchParams['body']['query']['bool']['filter'][] = [
                'term' => ['is_folder' => $is_folder]
            ];
        }

        $response = $client->search($searchParams);
        $total = $response['hits']['total']['value'];

        $file_ids_from_es = [];
        foreach ($response['hits']['hits'] as $hit) {
            $file_ids_from_es[] = $hit['_source']['file_id'];
        }

        if (!empty($file_ids_from_es)) {
            $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id IN (' . implode(',', array_fill(0, count($file_ids_from_es), '?')) . ') ORDER BY file_id ASC';
            $stmt = $db->prepare($sql);
            foreach ($file_ids_from_es as $i => $file_id) {
                $stmt->bindValue($i + 1, $file_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = [];
        }
    } catch (Exception $e) {
        error_log("Lỗi tìm kiếm Elasticsearch: " . $e->getMessage());
    }
} else {
    try {
        $sql = 'SELECT f.* FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f';
        $count_sql = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f';

        if (!defined('NV_IS_SPADMIN')) {
            $sql .= ' LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id';
            $count_sql .= ' LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id';
        }

        $where = ' WHERE f.status = 1 AND f.lev = ' . $lev;

        if (!defined('NV_IS_SPADMIN')) {
            if (isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array))) {
                $where .= ' AND p.p_group >= 2';
            } else {
                $where .= ' AND p.p_other = 2';
            }
        }

        if (!empty($search_term)) {
            $where .= ' AND f.file_name LIKE ' . $db->quote('%' . $search_term . '%');
        }

        if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
            $is_folder = ($search_type == 'file') ? 0 : 1;
            $where .= ' AND f.is_folder = ' . $is_folder;
        }

        $sql .= $where . ' ORDER BY f.file_id ASC LIMIT ' . (($page - 1) * $perpage) . ', ' . $perpage;
        $count_sql .= $where;

        $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $total = $db->query($count_sql)->fetchColumn();
    } catch (PDOException $e) {
        error_log("Lỗi truy vấn Database: " . $e->getMessage());
    }
}

if ($lev > 0) {
    $base_dir = $db->query('SELECT file_path FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $lev)->fetchColumn();
    $full_dir = NV_ROOTDIR . $base_dir;
    $page_url .= '&lev=' . $lev;

    $sql = 'SELECT lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $lev;
    $parent = $db->query($sql)->fetch();
    if ($parent) {
        $back_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
        if ($parent['lev'] > 0) {
            $sql = 'SELECT alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $parent['lev'];
            $parent_alias = $db->query($sql)->fetchColumn();
            if ($parent_alias) {
                $back_url .= '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $parent_alias;
            }
        }
    }
}

$action = $nv_Request->get_title('action', 'post', '');
$fileIds = $nv_Request->get_array('files', 'post', []);
$reCaptchaPass = (!empty($global_config['recaptcha_sitekey']) and !empty($global_config['recaptcha_secretkey']) and ($global_config['recaptcha_ver'] == 2 or $global_config['recaptcha_ver'] == 3));

if (!empty($action)) {
    $status = $lang_module['error'];
    $mess = $lang_module['sys_err'];

    if ($action == 'create') {
        $name_f = $nv_Request->get_title('name_f', 'post', '');
        $type = $nv_Request->get_int('type', 'post', 0);

        if ($name_f == '') {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_name_empty']]);
        }

        $fcaptcha = '';
        if ($module_config[$module_name]['captcha_type'] == 'recaptcha' && $reCaptchaPass) {
            $fcaptcha = $nv_Request->get_title('g-recaptcha-response', 'post', '');
            if (empty($fcaptcha) || !nv_capcha_txt($fcaptcha, 'recaptcha')) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_global['securitycodeincorrect1']]);
            }
        } elseif ($module_config[$module_name]['captcha_type'] == 'captcha') {
            $fcaptcha = $nv_Request->get_title('fcode', 'post', '');
            if (empty($fcaptcha) || !nv_capcha_txt($fcaptcha, 'captcha')) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_global['securitycodeincorrect']]);
            }
        }

        $extension = pathinfo($name_f, PATHINFO_EXTENSION);
        if ($type == 0 && ($extension == '' || !in_array($extension, $allowed_extensions))) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_extension_not_allowed']]);
        }

        if ($lev > 0) {
            $parentFileType = checkIfParentIsFolder($db, $lev);
            if ($type == 0 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_create_file_in_file']]);
            }

            if ($type == 1 && $parentFileType == 0) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_create_file_in_file']]);
            }
        }

        $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_name = :file_name AND lev = :lev AND status = 1';
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
        $stmtCheck->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $i = 1;
            $originalName = pathinfo($name_f, PATHINFO_FILENAME);
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            do {
                $name_f = $originalName . '_' . $i . '.' . $extension;
                $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
                $stmtCheck->execute();
                $count = $stmtCheck->fetchColumn();
                $i++;
            } while ($count > 0);
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_name_exist'] . $name_f]);
        }
        $file_path = $base_dir . '/' . $name_f;

        $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                VALUES (:file_name, :file_path, :uploaded_by, :is_folder, :created_at, :lev)';
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':file_name', $name_f, PDO::PARAM_STR);
        $stmt->bindParam(':file_path', $file_path, PDO::PARAM_STR);
        $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
        $stmt->bindParam(':is_folder', $type, PDO::PARAM_INT);
        $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
        $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

        $full_dir = NV_ROOTDIR . $file_path;
        if ($type == 1) {
            if (!file_exists($full_dir)) {
                mkdir($full_dir);
            }
            $status = 'success';
            $mess = $lang_module['create_ok'];
        } else {
            $status = file_put_contents($full_dir, '') !== false ? 'success' : 'error';
            $mess = $status == 'success' ? $lang_module['create_ok'] : $lang_module['cannot_create_file'];
        }

        if ($status == 'success') {
            $exe = $stmt->execute();
            $file_id = $db->lastInsertId();
            updateAlias($file_id, $name_f);
            
            $parent_permissions = getParentPermissions($lev);
            
            $sql1 = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                    VALUES (:file_id, :p_group, :p_other, :updated_at)';
            $stmta = $db->prepare($sql1);
            $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
            $stmta->bindValue(':p_group', $parent_permissions['p_group'], PDO::PARAM_INT);
            $stmta->bindValue(':p_other', $parent_permissions['p_other'], PDO::PARAM_INT);
            $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmta->execute();
            
            updateLog($lev);
            nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id: ' . $file_id, $user_info['userid']);
            updateParentFolderSize($lev);

            nv_jsonOutput(['status' => 'success', 'message' => $lang_module['create_ok'], 'redirect' => $page_url]);
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'delete') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $checksess = $nv_Request->get_title('checksess', 'get', '');
        if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
            if (!defined('NV_IS_SPADMIN')) {
                $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));

                $sql = 'SELECT p_group, p_other, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p 
                        LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_files f ON p.file_id = f.file_id 
                        WHERE p.file_id = ' . $fileId;
                $row = $db->query($sql)->fetch();

                if ($row) {
                    $current_permission = $is_group_user ? $row['p_group'] : $row['p_other'];

                    if (!$is_group_user && $row['p_group'] == 3) {
                        nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_group_only']]);
                    }

                    if ($current_permission < 3) {
                        nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_to_delete']]);
                    }

                    if ($row['is_folder'] == 1) {
                        $check_result = checkChildrenPermissions($fileId);
                        if ($check_result == true ) {
                            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['folder_has_restricted_items']]);
                        }
                    }
                } else {
                    nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_not_found']]);
                }
            }

            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id: ' . $fileId, $user_info['userid']);
                nv_jsonOutput(['status' => 'success', 'message' => $lang_module['delete_ok'], 'redirect' => $page_url]);
            } else {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['delete_false']]);
            }
        }
    }

    if ($action == 'deleteAll') {
        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        $deletedFileIds = [];
        foreach ($fileIds as $fileId) {
            $fileId = (int) $fileId;
            $checksess = md5($fileId . NV_CHECK_SESSION);
            if ($fileId > 0) {
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
            nv_jsonOutput(['status' => 'success', 'message' => $mess, 'redirect' => $page_url]);
        } else {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['delete_false']]);
        }
    }

    if ($action == 'rename') {
        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        $newName = trim($nv_Request->get_title('new_name', 'post', ''));

        $sql = 'SELECT f.*, p.p_group, p.p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
               LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id 
               WHERE f.file_id = ' . $fileId;
        $row = $db->query($sql)->fetch();

        if (!$row) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_not_found']]);
        }

        if (!defined('NV_IS_SPADMIN')) {
            $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
            $current_permission = $is_group_user ? $row['p_group'] : $row['p_other'];

            if (!$is_group_user && $row['p_group'] == 3) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_group_only']]);
            }

            if ($current_permission < 3) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_to_rename']]);
            }

            if ($row['is_folder'] == 1) {
                $check_result = checkChildrenPermissions($fileId);
                if ($check_result == true) {
                    nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_to_rename']]);
                }
            }
        }

        $fileName = $row['file_name'];
        $oldFilePath = $row['file_path'];
        $oldFullPath = NV_ROOTDIR . '/' . $oldFilePath;

        $fileInfo = pathinfo($newName);
        $baseName = $fileInfo['filename'];
        $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';

        $directory = dirname($oldFilePath);
        $newFilePath = $directory . '/' . $newName;
        $newFullPath = NV_ROOTDIR . '/' . $newFilePath;

        if (file_exists($newFullPath)) {
            $counter = 1;
            $suggestedName = $baseName . '_' . $counter . $extension;
            $suggestedFullPath = NV_ROOTDIR . '/' . $directory . '/' . $suggestedName;

            while (file_exists($suggestedFullPath)) {
                $counter++;
                $suggestedName = $baseName . '_' . $counter . $extension;
                $suggestedFullPath = NV_ROOTDIR . '/' . $directory . '/' . $suggestedName;
            }

            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['name_exists_suggest'] . $suggestedName]);
        }

        if (rename($oldFullPath, $newFullPath)) {
            $mess = $lang_module['cannot_update_db'];
            $alias = change_alias($newName . '_' . $fileId);
            $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET file_name = :file_name, alias = :alias, file_path = :new_path, updated_at = :updated_at, elastic = :elastic WHERE file_id = ' . $fileId;
            $stmtUpdate = $db->prepare($sqlUpdate);
            $stmtUpdate->bindParam(':file_name', $newName, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':alias', $alias, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':new_path', $newFilePath, PDO::PARAM_STR);
            $stmtUpdate->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmtUpdate->bindValue(':elastic', 0, PDO::PARAM_INT);
            if ($stmtUpdate->execute()) {
                $status = 'success';
                $mess = $lang_module['rename_ok'];

                if ($row['is_folder'] == 1) {
                    $sqlUpdateChildren = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET file_path = REPLACE(file_path, :old_path, :new_path) WHERE file_path LIKE :like_old_path';
                    $stmtUpdateChildren = $db->prepare($sqlUpdateChildren);
                    $stmtUpdateChildren->bindParam(':old_path', $oldFilePath);
                    $stmtUpdateChildren->bindParam(':new_path', $newFilePath);
                    $stmtUpdateChildren->bindValue(':like_old_path', $oldFilePath . '/%', PDO::PARAM_STR);
                    $stmtUpdateChildren->execute();
                }
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id:' . $fileId . '. Đổi tên : ' . $fileName . '=>' . $newName, $user_info['userid']);
                nv_jsonOutput(['status' => 'success', 'message' => $lang_module['rename_ok'], 'redirect' => $page_url]);
            }
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'check_filename') {
        $name_f = $nv_Request->get_title('zipFileName', 'post', '');
        if ($name_f == '') {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['zip_file_name_empty']]);
        }

        $name_with_zip = $name_f;
        if (pathinfo($name_f, PATHINFO_EXTENSION) != 'zip') {
            $name_with_zip = $name_f . '.zip';
        }

        $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE (file_name = :file_name OR file_name = :file_name_zip) AND lev = :lev AND status = 1';
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
        $stmtCheck->bindParam(':file_name_zip', $name_with_zip, PDO::PARAM_STR);
        $stmtCheck->bindParam(':lev', $lev, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $i = 1;
            $originalName = pathinfo($name_f, PATHINFO_FILENAME);
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            do {
                $name_f = $originalName . '_' . $i . '.' . $extension;
                $name_with_zip = $name_f . '.zip';
                $stmtCheck->bindParam(':file_name', $name_f, PDO::PARAM_STR);
                $stmtCheck->bindParam(':file_name_zip', $name_with_zip, PDO::PARAM_STR);
                $stmtCheck->execute();
                $count = $stmtCheck->fetchColumn();
                $i++;
            } while ($count > 0);
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_name_exist'] . $name_f]);
        } else {
            nv_jsonOutput(['status' => 'success', 'message' => $lang_module['file_name_valid']]);
        }
    }

    if ($action == 'compress') {
        if (!defined('NV_IS_SPADMIN')) {
            $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));

            foreach ($fileIds as $fileId) {
                $sql = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $fileId;
                $permissions = $db->query($sql)->fetch();

                if ($permissions) {
                    $current_permission = $is_group_user ? $permissions['p_group'] : $permissions['p_other'];

                    if (!$is_group_user && $permissions['p_group'] == 3) {
                        nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_group_only']]);
                    }

                    if ($current_permission < 3) {
                        nv_jsonOutput(['status' => 'error', 'message' => $lang_module['not_permission_to_compress']]);
                    }
                } else {
                    nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_not_found']]);
                }
            }
        }

        if (empty($fileIds)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['choose_file_0']]);
        }

        $zipFileName = $nv_Request->get_title('zipFileName', 'post', '');
        if ($zipFileName == '') {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['zip_file_name_empty']]);
        }

        if (pathinfo($zipFileName, PATHINFO_EXTENSION) != 'zip') {
            $zipFileName .= '.zip';
        }

        $zipFilePath = $base_dir . '/' . $zipFileName;
        $zipFullPath = NV_ROOTDIR . $zipFilePath;

        $compressResult = compressFiles($fileIds, $zipFullPath);

        if ($compressResult['status'] == 'success') {
            $file_size = filesize($zipFullPath);
            $allFileIds = $fileIds;
            foreach ($fileIds as $fileId) {
                $allFileIds = array_merge($allFileIds, getAllChildFileIds($fileId));
            }
            $compressed = implode(',', $allFileIds);

            $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev, compressed, elastic) 
                          VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev, :compressed, 0)';
            $stmtInsert = $db->prepare($sqlInsert);
            $stmtInsert->bindParam(':file_name', $zipFileName, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_path', $zipFilePath, PDO::PARAM_STR);
            $stmtInsert->bindParam(':file_size', $file_size, PDO::PARAM_INT);
            $stmtInsert->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_INT);
            $stmtInsert->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmtInsert->bindValue(':lev', $lev, PDO::PARAM_INT);
            $stmtInsert->bindValue(':compressed', $compressed, PDO::PARAM_STR);

            if ($stmtInsert->execute()) {
                $file_id = $db->lastInsertId();
                updateAlias($file_id, $zipFileName);

                $sql1 = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                         VALUES (:file_id, :p_group, :p_other, :updated_at)';
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmta->execute();

                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id:' . $compressed, $user_info['userid']);
                updateParentFolderSize($lev);

                nv_jsonOutput(['status' => 'success', 'message' => $compressResult['message'], 'redirect' => $page_url]);
            } else {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_update_db']]);
            }
        } else {
            nv_jsonOutput(['status' => 'error', 'message' => $compressResult['message']]);
        }
    }

    nv_jsonOutput(['status' => $status, 'message' => $mess]);
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);

    $sql = "SELECT file_path, file_name, is_folder FROM " . NV_PREFIXLANG . '_' . $module_data . "_files WHERE file_id = :file_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->execute();
    $file = $stmt->fetch();

    if ($file) {
        $file_path = NV_ROOTDIR . $file['file_path'];
        $file_name = $file['file_name'];
        $is_folder = $file['is_folder'];
        $zip = '';

        if ($is_folder == 1) {
            $zipFileName = $file_name . '.zip';
            $zipFilePath = $tmp_dir . $zipFileName;
            $zipFullPath = NV_ROOTDIR . $zipFilePath;

            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $zipArchive->addEmptyDir($file_name);

                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file_path), RecursiveIteratorIterator::LEAVES_ONLY);

                foreach ($files as $name => $fileInfo) {
                    if (!$fileInfo->isDir()) {
                        $fileRealPath = $fileInfo->getRealPath();
                        $relativePath = substr($fileRealPath, strlen($file_path) + 1);
                        $zipArchive->addFile($fileRealPath, $file_name . '/' . $relativePath);
                    }
                }
                $zipArchive->close();

                if (file_exists($zipFullPath)) {
                    $zip = $zipFullPath;
                }
            }
        } elseif (pathinfo($file_path, PATHINFO_EXTENSION) == 'zip') {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        } else {
            if (file_exists($file_path)) {
                $zip = $file_path;
            }
        }

        if (!empty($zip) && file_exists($zip)) {
            $downloadPath = ($is_folder == 1) ? $tmp_dir : $base_dir;
            $_download = new NukeViet\Files\Download($zip, NV_ROOTDIR . $downloadPath, basename($zip), true, 0);
            $_download->download_file();

            if (file_exists($zipFullPath)) {
                unlink($zipFullPath);
            }
        }
    }
}

$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'text';

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    if (!defined('NV_IS_SPADMIN')) {
        if (empty($arr_full_per)) {
            $error = $lang_module['not_thing_to_do'];
        } else {
            if (!in_array($lev, $arr_full_per)) {
                $error = $lang_module['not_thing_to_do'];
            }
        }
    }

    if (empty($error)) {
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

            $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev) 
                    VALUES (:file_name, :file_path, :file_size, :uploaded_by, 0, :created_at, :lev)';
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':file_name', $file_name, PDO::PARAM_STR);
            $stmt->bindParam(':file_path', $relative_path, PDO::PARAM_STR);
            $stmt->bindParam(':file_size', $file_size, PDO::PARAM_STR);
            $stmt->bindParam(':uploaded_by', $user_info['userid'], PDO::PARAM_STR);
            $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $stmt->bindValue(':lev', $lev, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $file_id = $db->lastInsertId();
                updateAlias($file_id, $file_name);
                $sql1 = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                        VALUES (:file_id, :p_group, :p_other, :updated_at)';
                $stmta = $db->prepare($sql1);
                $stmta->bindParam(':file_id', $file_id, PDO::PARAM_STR);
                $stmta->bindValue(':p_group', '1', PDO::PARAM_INT);
                $stmta->bindValue(':p_other', '1', PDO::PARAM_INT);
                $stmta->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmta->execute();
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $action, 'File id:' . $file_id, $user_info['userid']);
                updateParentFolderSize($lev);
            }
            nv_redirect_location($page_url);
            $success = $lang_module['upload_ok'];
        } else {
            $error = $upload_info['error'];
        }
    }
}

$selected_all = ($search_type == 'all') ? ' selected' : '';
$selected_file = ($search_type == 'file') ? ' selected' : '';
$selected_folder = ($search_type == 'folder') ? ' selected' : '';

if (!empty($result)) {
    foreach ($result as $row) {
        $sql_logs = 'SELECT *
                FROM ' . NV_PREFIXLANG . '_' . $module_data . '_stats 
                WHERE lev = ' . $row['lev'] . ' 
                ORDER BY log_time DESC 
                LIMIT 1';
        $logs = $db->query($sql_logs)->fetch(PDO::FETCH_ASSOC);

        $sql_permissions = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $row['file_id'];
        $permissions = $db->query($sql_permissions)->fetch(PDO::FETCH_ASSOC);
    }
} else {
    $logs = [];
    $permissions = [];
}
$nv_BotManager->setFollow()->setNoIndex();
$contents = nv_fileserver_main($op, $result, $page_url, $error, $success, $permissions, $selected_all, $selected_file, $selected_folder, $total, $perpage, $base_url, $lev, $search_term, $search_type, $page, $logs, $reCaptchaPass, $back_url);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
