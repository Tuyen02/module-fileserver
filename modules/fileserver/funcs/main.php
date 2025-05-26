<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $module_info['site_title'];
$key_words = $module_info['keywords'];
$description = $module_info['description'];

$perpage = 20;
$page = $nv_Request->get_int('page', 'get', 1);
$generate_page = '';
$search_term = $nv_Request->get_title('search', 'get', '');
$search_type = $nv_Request->get_title('search_type', 'get', 'all');

$back_url = '';
$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name;

$page_url = $base_url;

$status = 'error';
$mess = $lang_module['sys_err'];

$breadcrumbs = [];
$current_lev = $lev;

while ($current_lev > 0) {
    $sql_check = 'SELECT file_name, file_path, lev, alias 
                  FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                  WHERE file_id = ' . $current_lev;
    $result1 = $db->query($sql_check);
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

$error = '';
$success = '';
$admin_info['allow_files_type'][] = 'text';

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    $file_extension = strtolower(pathinfo($_FILES['uploadfile']['name'], PATHINFO_EXTENSION));
    if ($file_extension == 'zip') {
        $error = $lang_module['not_allow_zip'];
    } else {
        if (!defined('NV_IS_SPADMIN')) {
            if (empty($arr_full_per)) {
                $error = $lang_module['not_thing_to_do'];
            } else {
                if (!in_array($lev, $arr_full_per)) {
                    $error = $lang_module['not_thing_to_do'];
                }
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
        $upload_info = $upload->save_file($_FILES['uploadfile'], NV_ROOTDIR . $base_dir, false, $global_config['nv_auto_resize']);
        if ($upload_info['error'] == '') {
            $full_path = $upload_info['name'];
            chmod($full_path, 0777);

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
                $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                        VALUES (' . $file_id . ', 1, 1, ' . NV_CURRENTTIME . ')';
                $db->query($sql_insert);
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['upload_btn'], 'File id: ' . $file_id, $user_info['userid']);
                updateParentFolderSize($lev);
            }
            $success = $lang_module['upload_ok'];
        } else {
            $error = $upload_info['error'];
        }
    }
}

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
        error_log($lang_module['error_elastic_search'] . $e->getMessage());
    }
} else {
    try {
        $sql = 'SELECT f.* FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f';

        if (!defined('NV_IS_SPADMIN')) {
            $sql .= ' LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id';
        }

        $where_conditions = [
            'f.status = 1',
            'f.lev = ' . $lev
        ];

        if (!defined('NV_IS_SPADMIN')) {
            if (isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array))) {
                $where_conditions[] = 'p.p_group >= 2';
            } else {
                $where_conditions[] = 'p.p_other = 2';
            }
        }

        if (!empty($search_term)) {
            $where_conditions[] = 'f.file_name LIKE ' . $db->quote('%' . $search_term . '%');
        }

        if (!empty($search_type) && in_array($search_type, ['file', 'folder'])) {
            $is_folder = ($search_type == 'file') ? 0 : 1;
            $where_conditions[] = 'f.is_folder = ' . $is_folder;
        }

        $where = ' WHERE ' . implode(' AND ', $where_conditions);

        $stmt = $db->query($sql . $where);
        $total = $stmt->rowCount();

        $sql .= $where . ' ORDER BY f.file_id ASC LIMIT ' . (($page - 1) * $perpage) . ', ' . $perpage;
        $result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($lang_module['error_db'] . $e->getMessage());
    }
}

if ($lev > 0) {
    $sql = 'SELECT f.*, p.p_group, p.p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
           LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id 
           WHERE f.file_id = ' . $lev . ' AND f.status = 1';
    $row = $db->query($sql)->fetch();

    if (empty($row)) {
        nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
    }

    if (!defined('NV_IS_SPADMIN')) {
        $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
        $current_permission = $is_group_user ? $row['p_group'] : $row['p_other'];

        if ($current_permission < 1) {
            nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
        }
    }

    $base_dir = $row['file_path'];
    $full_dir = NV_ROOTDIR . $base_dir;
    $page_url .= '&lev=' . $lev;

    $parent_lev = $row['lev'];
    if ($parent_lev > 0) {
        $sql = 'SELECT alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $parent_lev;
        $parent_alias = $db->query($sql)->fetchColumn();
        $back_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
        if ($parent_alias) {
            $back_url .= '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $parent_alias;
        }
    } else {
        $back_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
    }

    $parentFileType = checkIfParentIsFolder($lev);
    if ($parentFileType == 0) {
        nv_jsonOutput([
            'status' => $status,
            'message' => $lang_module['cannot_create_file_in_file'],
            'refresh_captcha' => true
        ]);
    }
}

$action = $nv_Request->get_title('action', 'post', '');
$fileIds = $nv_Request->get_array('files', 'post', []);

if (!empty($action)) {
    if ($action == 'create') {
        $name_f = nv_EncString($nv_Request->get_title('name_f', 'post', ''));
        $type = $nv_Request->get_int('type', 'post', 0);

        if ($name_f == '') {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_empty'], 'refresh_captcha' => true]);
        }

        if ($module_config[$module_name]['use_captcha'] == 1) {
            $fcaptcha = $nv_Request->get_title('fcode', 'post', '');
            if (empty($fcaptcha) || !nv_capcha_txt($fcaptcha, 'captcha')) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_global['securitycodeincorrect'], 'refresh_captcha' => true]);
            }
        }

        if ($type == 0) {
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            $filename = pathinfo($name_f, PATHINFO_FILENAME);
            if ($extension == '' || !in_array($extension, $allowed_extensions)) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_extension_not_allowed'], 'refresh_captcha' => true]);
            }

            if ($filename == '') {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_invalid'], 'refresh_captcha' => true]);
            }
        }

        if ($lev > 0) {
            $parentFileType = checkIfParentIsFolder($lev);
            if ($parentFileType == 0) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['cannot_create_file_in_file'], 'refresh_captcha' => true]);
            }
        }

        $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND is_folder = ' . $type . ' AND file_name = ' . $db->quote($name_f) . ' AND lev = ' . $lev;
        $count = $db->query($sqlCheck)->fetchColumn();

        if ($count > 0) {
            $i = 1;
            $originalName = pathinfo($name_f, PATHINFO_FILENAME);
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            do {
                $suggestedName = $originalName . '_' . $i;
                if ($extension) {
                    $suggestedName .= '.' . $extension;
                }
                $count = $db->query($sqlCheck)->fetchColumn();
                $i++;
            } while ($count > 0);
            nv_jsonOutput(['status' => $status, 'message' => sprintf($lang_module['file_name_exists_suggest'], $name_f, $suggestedName), 'refresh_captcha' => true]);
        }
        $file_path = $base_dir . '/' . $name_f;

        $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files (file_name, file_path, uploaded_by, is_folder, created_at, lev) 
                VALUES (' . $db->quote($name_f) . ', ' . $db->quote($file_path) . ', ' . $user_info['userid'] . ', ' . $type . ', ' . NV_CURRENTTIME . ', ' . $lev . ')';

        $file_dir = NV_ROOTDIR . $file_path;
        if ($type == 1) {
            if (!file_exists($file_dir)) {
                mkdir($file_dir);
            }
            $status = 'success';
        } else {
            $status = file_put_contents($file_dir, '') !== false ? 'success' : 'error';
            $mess = $status == 'success' ? $lang_module['create_ok'] : $lang_module['cannot_create_file'];
        }

        if ($status == 'success') {
            $exe = $db->query($sql);
            $file_id = $db->lastInsertId();
            updateAlias($file_id, $name_f);

            $parent_permissions = getParentPermissions($lev);

            $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions (file_id, p_group, p_other, updated_at) 
                    VALUES (' . $file_id . ', 1, 1, ' . NV_CURRENTTIME . ')';
            $db->query($sql_insert);

            updateLog($lev);
            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['create_btn'], 'File id: ' . $file_id, $user_info['userid']);
            updateParentFolderSize($lev);

            $type_text = $type == 1 ? $lang_module['folder'] : $lang_module['file'];
            nv_jsonOutput(['status' => 'success', 'message' => sprintf($lang_module['create_ok_detail'], $type_text, $name_f), 'redirect' => $page_url]);
        }
        nv_jsonOutput(['status' => $status, 'message' => $mess]);
    }

    if ($action == 'delete') {
        $fileId = $nv_Request->get_int('file_id', 'post', 0);
        $checksess = $nv_Request->get_title('checksess', 'get', '');
        if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
            $sql = 'SELECT f.*, p.p_group, p.p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
                   LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id 
                   WHERE f.file_id = ' . $fileId;
            $row = $db->query($sql)->fetch();

            if (!checkPermission($row, $user_info)) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_permission_to_delete']]);
            }

            $deleted = deleteFileOrFolder($fileId);
            if ($deleted) {
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['delete_btn'], 'File id: ' . $fileId, $user_info['userid']);
                nv_jsonOutput(['status' => 'success', 'message' => $lang_module['delete_ok'], 'redirect' => $page_url]);
            }

            nv_jsonOutput(['status' => $status, 'message' => $lang_module['delete_false']]);
        }
    }

    if ($action == 'deleteAll') {
        if (empty($fileIds)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['choose_file_0']]);
        }

        $deletedFileIds = [];
        foreach ($fileIds as $fileId) {
            $fileId = (int) $fileId;
            $checksess = md5($fileId . NV_CHECK_SESSION);
            if ($fileId > 0 && $checksess == md5($fileId . NV_CHECK_SESSION)) {
                $deleted = deleteFileOrFolder($fileId);
                if ($deleted) {
                    $deletedFileIds[] = $fileId;
                }
            }
        }

        if (!empty($deletedFileIds)) {
            updateLog($lev);
            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['deleteAll_btn'], 'File id: ' . implode(',', $deletedFileIds), $user_info['userid']);
            nv_jsonOutput(['status' => 'success', 'message' => $lang_module['delete_ok'], 'redirect' => $page_url]);
        } else {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['delete_false']]);
        }
    }

    if ($action == 'rename') {
        $fileId = intval($nv_Request->get_int('file_id', 'post', 0));
        if ($fileId == 0) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_empty']]);
        }

        $newName = nv_EncString(trim($nv_Request->get_title('new_name', 'post', '')));

        if ($newName == '' || !isValidFileName($newName)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_invalid']]);
        }

        $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $fileId;
        $row = $db->query($sql)->fetch();

        if (empty($row)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_not_found']]);
        }

        $sql_perm = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $fileId;
        $perm = $db->query($sql_perm)->fetch();

        $row['p_group'] = $row['p_other'] = 1;
        if ($perm) {
            $row['p_group'] = $perm['p_group'];
            $row['p_other'] = $perm['p_other'];
        }

        $fileName = $row['file_name'];
        $oldFilePath = $row['file_path'];
        $oldFullPath = NV_ROOTDIR . '/' . $oldFilePath;

        if ($newName === $fileName) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['no_changes_made']]);
        }

        if (!defined('NV_IS_SPADMIN')) {
            $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
            $current_permission = $is_group_user ? $row['p_group'] : $row['p_other'];

            if (!$is_group_user && $row['p_group'] == 3) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_permission_group_only']]);
            }

            if ($current_permission < 3) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_permission_to_rename']]);
            }

            if ($row['is_folder'] == 1) {
                $check_result = checkChildrenPermissions($fileId);
                if ($check_result == true) {
                    nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_permission_to_rename']]);
                }
            }
        }

        if ($row['is_folder'] == 0) {
            $originalExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newExtension = pathinfo($newName, PATHINFO_EXTENSION);

            if (!empty($originalExtension) && $originalExtension != $newExtension) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['cannot_change_extension']]);
            }

            if (pathinfo($newName, PATHINFO_FILENAME) == '') {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_invalid']]);
            }
        }

        $directory = dirname($oldFilePath);
        $newFilePath = $directory . '/' . $newName;
        $newFullPath = NV_ROOTDIR . '/' . $newFilePath;

        $sql_check = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                     WHERE status = 1 AND file_path = ' . $db->quote($newFilePath) . ' AND file_id != ' . $fileId;
        $exists = $db->query($sql_check)->fetchColumn();

        if ($exists) {
            $counter = 1;
            $baseName = pathinfo($newName, PATHINFO_FILENAME);
            $extension = pathinfo($newName, PATHINFO_EXTENSION);
            $suggestedName = $baseName . '_' . $counter . '.' . $extension;
            $suggestedFullPath = NV_ROOTDIR . '/' . $directory . '/' . $suggestedName;

            while (file_exists($suggestedFullPath)) {
                $counter++;
                $suggestedName = $baseName . '_' . $counter . '.' . $extension;
                $suggestedFullPath = NV_ROOTDIR . '/' . $directory . '/' . $suggestedName;
            }

            nv_jsonOutput(['status' => $status, 'message' => $lang_module['name_exists_suggest'] . $suggestedName]);
        }

        if (rename($oldFullPath, $newFullPath)) {
            $alias = change_alias($newName . '_' . $fileId);
            $sqlUpdate = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                         SET file_name = ' . $db->quote($newName) . ', 
                             alias = ' . $db->quote($alias) . ', 
                             file_path = ' . $db->quote($newFilePath) . ', 
                             updated_at = ' . NV_CURRENTTIME . ', 
                             elastic = 0 
                         WHERE file_id = ' . $fileId;
            if ($db->query($sqlUpdate)) {
                if ($row['is_folder'] == 1) {
                    $sqlUpdateChildren = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                        SET file_path = REPLACE(file_path, ' . $db->quote($oldFilePath) . ', ' . $db->quote($newFilePath) . ') 
                                        WHERE file_path LIKE ' . $db->quote($oldFilePath . '/%');
                    $db->query($sqlUpdateChildren);
                }
                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['rename_btn'], 'File id: ' . $fileId . '. Đổi tên : ' . $fileName . '=>' . $newName, $user_info['userid']);
                nv_jsonOutput(['status' => 'success', 'message' => $lang_module['rename_ok'], 'redirect' => $page_url]);
            }
        }
        nv_jsonOutput(['status' => $status, 'message' => $lang_module['cannot_update_db']]);
    }

    if ($action == 'compress') {
        if (empty($fileIds)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['choose_file_0']]);
        }

        $zipFileName = nv_EncString($nv_Request->get_title('zipFileName', 'post', ''));
        if ($zipFileName == '' || !isValidFileName($zipFileName)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_invalid']]);
        }

        if (pathinfo($zipFileName, PATHINFO_EXTENSION) != 'zip') {
            $zipFileName .= '.zip';
        }
        if (!defined('NV_IS_SPADMIN')) {
            $is_group_user = isset($user_info['in_groups']) &&
                is_array($user_info['in_groups']) &&
                !empty(array_intersect($user_info['in_groups'], $config_value_array));

            $sql = 'SELECT file_id, p_group, p_other 
                    FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                    WHERE file_id IN (' . implode(',', array_map('intval', $fileIds)) . ')';
            $permissions = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

            $permissions_map = array_column($permissions, null, 'file_id');

            foreach ($fileIds as $fileId) {
                if (!isset($permissions_map[$fileId])) {
                    nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_not_found']]);
                }

                $perm = $permissions_map[$fileId];
                $current_permission = $is_group_user ? $perm['p_group'] : $perm['p_other'];

                if (!$is_group_user && $perm['p_group'] == 3) {
                    nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_permission_group_only']]);
                }

                if ($current_permission < 3) {
                    nv_jsonOutput(['status' => $status, 'message' => $lang_module['not_permission_to_compress']]);
                }
            }
        }

        $zipFilePath = $base_dir . '/' . $zipFileName;
        $zipFullPath = NV_ROOTDIR . $zipFilePath;

        if (!empty($fileIds)) {
            $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
            $sql = 'SELECT f.*, p.p_group, p.p_other 
                    FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
                    LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id
                    WHERE f.file_id IN (' . $placeholders . ') 
                    AND f.status = 1
                    ORDER BY f.lev ASC, f.file_id ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute($fileIds);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $compressResult = compressFiles($fileIds, $zipFullPath);

        if ($compressResult['status'] == 'success') {
            $file_size = filesize($zipFullPath);
            if ($file_size === false) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_get_file_size']]);
            }

            $allFileIds = [];
            foreach ($fileIds as $fileId) {
                getAllFileIds($fileId, $allFileIds);
            }
            $allFileIds = array_unique($allFileIds);
            $compressed = implode(',', $allFileIds);

            $sqlInsert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                         (file_name, file_path, file_size, uploaded_by, is_folder, created_at, lev, compressed, elastic) 
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

                $sql_insert = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                             (file_id, p_group, p_other, updated_at) 
                             VALUES (:file_id, 1, 1, :updated_at)';
                $stmtPerm = $db->prepare($sql_insert);
                $stmtPerm->bindValue(':file_id', $file_id, PDO::PARAM_INT);
                $stmtPerm->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
                $stmtPerm->execute();

                updateLog($lev);
                nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['zip_btn'], 'File id: ' . $compressed, $user_info['userid']);
                updateParentFolderSize($lev);

                nv_jsonOutput(['status' => 'success', 'message' => $compressResult['message'], 'redirect' => $page_url]);
            } else {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['cannot_update_db']]);
            }
        } else {
            nv_jsonOutput(['status' => 'error', 'message' => $compressResult['message']]);
        }
    }

    if ($action == 'check_filename') {
        $name_f = nv_EncString($nv_Request->get_title('name_f', 'post', ''));
        $type = $nv_Request->get_int('type', 'post', 0);

        if ($name_f == '' || !isValidFileName($name_f)) {
            nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_name_invalid']]);
        }

        $message = $lang_module['folder_name_valid'];
        if ($type == 0) {
            $message = $lang_module['file_name_valid'];
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            $filename = pathinfo($name_f, PATHINFO_FILENAME);

            if ($extension == '' || !in_array($extension, $allowed_extensions)) {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_extension_not_allowed']]);
            }

            if ($filename == '') {
                nv_jsonOutput(['status' => 'error', 'message' => $lang_module['file_name_invalid']]);
            }
        }

        $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND is_folder = ' . $type . ' AND file_name = ' . $db->quote($name_f) . ' AND lev = ' . $lev;
        $count = $db->query($sqlCheck)->fetchColumn();

        if ($count > 0) {
            $baseName = pathinfo($name_f, PATHINFO_FILENAME);
            $extension = pathinfo($name_f, PATHINFO_EXTENSION);
            $suggestedName = suggestNewName($lev, $baseName, $extension, $type);
            nv_jsonOutput(['status' => 'error', 'message' => sprintf($lang_module['file_name_exists_suggest'], $name_f, $suggestedName)]);
        }
        nv_jsonOutput(['status' => 'success', 'message' => $message]);
    }

    if ($action == 'check_rename') {
        $new_name = nv_EncString($nv_Request->get_title('new_name', 'post', ''));
        $file_id = $nv_Request->get_int('file_id', 'post', 0);

        if ($file_id == 0) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_not_found']]);
        }
        if ($new_name == '' || !isValidFileName($new_name)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_invalid']]);
        }

        $sql = 'SELECT f.*, p.p_group, p.p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f
               LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id 
               WHERE f.file_id = ' . $file_id;
        $row = $db->query($sql)->fetch();

        if (empty($row)) {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_not_found']]);
        }

        $message = $lang_module['folder_name_valid'];
        if ($row['is_folder'] == 0) {
            $message = $lang_module['file_name_valid'];
            $extension = pathinfo($new_name, PATHINFO_EXTENSION);
            $filename = pathinfo($new_name, PATHINFO_FILENAME);
            $originalExtension = pathinfo($row['file_name'], PATHINFO_EXTENSION);
            if ($extension == '' || !in_array($extension, $allowed_extensions)) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_extension_not_allowed']]);
            }
            if ($filename == '') {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['file_name_invalid']]);
            }
            if (!empty($originalExtension) && $originalExtension != $extension) {
                nv_jsonOutput(['status' => $status, 'message' => $lang_module['cannot_change_extension']]);
            }
        }

        $fileName = $row['file_name'];
        $oldFilePath = $row['file_path'];
        $directory = dirname($oldFilePath);
        $newFilePath = $directory . '/' . $new_name;

        $sql_check = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                     WHERE status = 1 AND file_path = ' . $db->quote($newFilePath) . ' AND file_id != ' . $file_id;
        $exists = $db->query($sql_check)->fetchColumn();

        if ($exists) {
            $baseName = pathinfo($new_name, PATHINFO_FILENAME);
            $extension = pathinfo($new_name, PATHINFO_EXTENSION);
            $suggestedName = suggestNewName($row['lev'], $baseName, $extension, $row['is_folder']);
            nv_jsonOutput(['status' => $status, 'message' => sprintf($lang_module['file_name_exists_suggest'], $new_name, $suggestedName)]);
        }
        nv_jsonOutput(['status' => 'success', 'message' => $message]);
    }

    if ($action == 'check_zip_name') {
        $zipFileName = nv_EncString($nv_Request->get_title('zipFileName', 'post', ''));
        if ($zipFileName == '') {
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['zip_file_name_empty']]);
        }

        $name_with_zip = $zipFileName;
        if (pathinfo($zipFileName, PATHINFO_EXTENSION) != 'zip') {
            $name_with_zip = $zipFileName . '.zip';
        }

        $sqlCheck = 'SELECT COUNT(*) FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND is_folder = 0 AND (file_name = ' . $db->quote($zipFileName) . ' OR file_name = ' . $db->quote($name_with_zip) . ') AND lev = ' . $lev;
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            $baseName = pathinfo($zipFileName, PATHINFO_FILENAME);
            $extension = pathinfo($zipFileName, PATHINFO_EXTENSION);
            $suggestedName = suggestNewName($lev, $baseName, $extension, 0);
            nv_jsonOutput(['status' => $status, 'message' => $lang_module['zip_name_exists'] . $suggestedName]);
        } else {
            nv_jsonOutput(['status' => 'success', 'message' => $lang_module['zip_name_valid']]);
        }
    }
    nv_jsonOutput(['status' => $status, 'message' => $mess]);
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_id = $nv_Request->get_int('file_id', 'get', 0);
    $token = $nv_Request->get_title('token', 'get', '');

    if ($file_id == 0 || empty($token) || $token != md5($file_id . NV_CHECK_SESSION . $global_config['sitekey'])) {
        nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
    }

    $sql = 'SELECT file_path, file_name, is_folder FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE status = 1 AND file_id = ' . $file_id;
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $file = $stmt->fetch();

    if (!empty($file)) {
        if (!defined('NV_IS_SPADMIN')) {
            $sql = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions
                    WHERE file_id = ' . $file_id;
            $row = $db->query($sql)->fetch();

            $is_group_user = false;
            if (defined('NV_IS_USER') && isset($user_info['in_groups']) && !empty($module_config[$module_name]['group_admin_fileserver'])) {
                $admin_groups = explode(',', $module_config[$module_name]['group_admin_fileserver']);
                $is_group_user = !empty(array_intersect($user_info['in_groups'], $admin_groups));
            }

            $current_permission = $is_group_user ? $row['p_group'] : $row['p_other'];
            if ($current_permission < 2) {
                nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
            }
        }

        $file_path = NV_ROOTDIR . $file['file_path'];
        $file_name = $file['file_name'];
        $is_folder = $file['is_folder'];
        $zip = '';

        if ($is_folder == 1) {
            $zipFileName = $file_name . '.zip';
            $zipFilePath = $tmp_dir . $zipFileName;
            $zipFullPath = NV_ROOTDIR . $zipFilePath;

            $all_items = getAllFilesAndFolders($file_id, $file_path);
            $allowed_files = [];

            foreach ($all_items as $item) {
                if (defined('NV_IS_SPADMIN')) {
                    $allowed_files[] = $item;
                } else {
                    $is_group_user = false;
                    if (defined('NV_IS_USER') && isset($user_info['in_groups']) && !empty($module_config[$module_name]['group_admin_fileserver'])) {
                        $admin_groups = explode(',', $module_config[$module_name]['group_admin_fileserver']);
                        $is_group_user = !empty(array_intersect($user_info['in_groups'], $admin_groups));
                    }

                    $current_permission = $is_group_user ? $item['p_group'] : $item['p_other'];
                    if ($current_permission >= 2) {
                        $allowed_files[] = $item;
                    }
                }
            }

            $zipArchive = new ZipArchive();
            if ($zipArchive->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) == TRUE) {
                $zipArchive->addEmptyDir($file_name);
                chmod($zipFullPath, 0777);

                foreach ($allowed_files as $allowed_file) {
                    $full_path = NV_ROOTDIR . $allowed_file['file_path'];
                    if (file_exists($full_path)) {
                        if ($allowed_file['is_folder']) {
                            $relative_path = substr($allowed_file['file_path'], strlen($file['file_path']));
                            $zipArchive->addEmptyDir($file_name . $relative_path);
                        } else {
                            $relative_path = substr($allowed_file['file_path'], strlen($file['file_path']));
                            $zipArchive->addFile($full_path, $file_name . $relative_path);
                        }
                    }
                }

                $zipArchive->close();

                if (file_exists($zipFullPath)) {
                    $zip = $zipFullPath;
                }
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
            exit();
        } else {
            nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
        }
    }
}

$selected = [
    'all' => ($search_type == 'all') ? ' selected' : '',
    'file' => ($search_type == 'file') ? ' selected' : '',
    'folder' => ($search_type == 'folder') ? ' selected' : ''
];

if (!empty($result)) {
    $file_ids = array_column($result, 'file_id');

    $sql_logs = 'SELECT *
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_stats 
            WHERE lev IN (' . implode(',', array_unique(array_column($result, 'lev'))) . ') 
            ORDER BY log_time DESC';
    $logs_result = $db->query($sql_logs)->fetchAll(PDO::FETCH_ASSOC);

    $logs = [];
    foreach ($logs_result as $log) {
        $logs[$log['lev']] = [
            'total_size' => isset($log['total_size']) ? $log['total_size'] : 0,
            'total_files' => isset($log['total_files']) ? $log['total_files'] : 0,
            'total_folders' => isset($log['total_folders']) ? $log['total_folders'] : 0,
            'log_time' => isset($log['log_time']) ? $log['log_time'] : NV_CURRENTTIME
        ];
    }

    foreach ($result as $row) {
        if (!isset($logs[$row['lev']])) {
            $logs[$row['lev']] = [
                'total_size' => 0,
                'total_files' => 0,
                'total_folders' => 0,
                'log_time' => NV_CURRENTTIME
            ];
        }
    }

    $sql_permissions = 'SELECT file_id, p_group, p_other 
            FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
            WHERE file_id IN (' . implode(',', $file_ids) . ')';
    $permissions_result = $db->query($sql_permissions)->fetchAll(PDO::FETCH_ASSOC);

    $permissions = [];
    foreach ($permissions_result as $perm) {
        $permissions[$perm['file_id']] = [
            'p_group' => isset($perm['p_group']) ? $perm['p_group'] : 1,
            'p_other' => isset($perm['p_other']) ? $perm['p_other'] : 1
        ];
    }

    foreach ($file_ids as $file_id) {
        if (!isset($permissions[$file_id])) {
            $permissions[$file_id] = [
                'p_group' => 1,
                'p_other' => 1
            ];
        }
    }
} else {
    $logs = [];
    $permissions = [];
}

if ($total > $perpage) {
    $page_url = $base_url . '&lev=' . $lev . '&search=' . $search_term . '&search_type=' . $search_type;
    $generate_page = nv_generate_page($page_url, $total, $perpage, $page);
}

$nv_BotManager->setFollow()->setNoIndex();
$contents = nv_fileserver_main($result, $page_url, $error, $success, $permissions, $selected, $base_url, $lev, $search_term, $logs, $back_url, $generate_page);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
