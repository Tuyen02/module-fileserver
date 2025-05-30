<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['compress'];
$action = $nv_Request->get_title('action', 'post', '');
$page = $nv_Request->get_int('page', 'get', 1);

$sql_file = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $lev;
$stmt_file = $db->prepare($sql_file);
$stmt_file->execute();
$file_info = $stmt_file->fetch();

$sql_permission = 'SELECT p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $lev;
$stmt_permission = $db->prepare($sql_permission);
$stmt_permission->execute();
$permission_info = $stmt_permission->fetch();

$row = array_merge($file_info, $permission_info ?: ['p_group' => null, 'p_other' => null]);

if (empty($row)) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$current_permission = get_user_permission($lev, $row['uploaded_by']);

if ($current_permission < 3) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

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

$status = '';
$message = '';
$list = [];
$file_size_zip = 0;
$can_unzip = false;

if (empty($row['compressed'])) {
    $status = 'error';
    $message = $lang_module['not_compressed_file'];
} else {
    $fileIds = explode(',', $row['compressed']);
    if (!empty($fileIds)) {
        $placeholders = [];
        for ($i = 0; $i < count($fileIds); $i++) {
            $placeholders[] = '?';
        }
        $placeholders_str = implode(',', $placeholders);

        $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id IN (' . $placeholders_str . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($fileIds);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($list)) {
            $can_unzip = true;
        } else {
            $status = 'error';
            $message = $lang_module['download_to_view'];
        }
    }
}

if ($action == 'unzip' && $can_unzip) {
    $zipFilePath = NV_ROOTDIR . $row['file_path'];
    if (!file_exists($zipFilePath)) {
        nv_jsonOutput([
            'status' => 'error',
            'message' => $lang_module['file_not_found']
        ]);
    } else {
        $file_size = filesize($zipFilePath);
        if ($file_size > 104857600) { // 100MB
            nv_jsonOutput([
                'status' => 'error',
                'message' => $lang_module['file_too_large']
            ]);
        } else {
            if (!defined('NV_IS_SPADMIN')) {
                $compressed_files = explode(',', $row['compressed']);
                if (!empty($compressed_files)) {
                    $compressed_placeholders = [];
                    for ($i = 0; $i < count($compressed_files); $i++) {
                        $compressed_placeholders[] = '?';
                    }
                    $compressed_placeholders_str = implode(',', $compressed_placeholders);

                    $sql_files = 'SELECT file_id, file_name FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                WHERE file_id IN (' . $compressed_placeholders_str . ')';
                    $stmt_files = $db->prepare($sql_files);
                    $stmt_files->execute($compressed_files);
                    $files = $stmt_files->fetchAll(PDO::FETCH_ASSOC);

                    $sql_permissions = 'SELECT file_id, p_group, p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions 
                                    WHERE file_id IN (' . $compressed_placeholders_str . ')';
                    $stmt_permissions = $db->prepare($sql_permissions);
                    $stmt_permissions->execute($compressed_files);
                    $permissions = $stmt_permissions->fetchAll(PDO::FETCH_ASSOC);

                    $result = [];
                    foreach ($files as $file) {
                        $file_id = $file['file_id'];
                        $permission = array_filter($permissions, function ($p) use ($file_id) {
                            return $p['file_id'] == $file_id;
                        });
                        $permission = reset($permission);

                        $result[] = [
                            'file_name' => $file['file_name'],
                            'p_group' => $permission ? $permission['p_group'] : null,
                            'p_other' => $permission ? $permission['p_other'] : null
                        ];
                    }

                    $unauthorized_files = array_column(array_filter($result, function ($file) {
                        return $file['p_other'] < 3;
                    }), 'file_name');

                    if (!empty($unauthorized_files)) {
                        nv_jsonOutput([
                            'status' => 'error',
                            'message' => $lang_module['folder_has_restricted_items'] . ': ' . implode(', ', $unauthorized_files)
                        ]);
                    }
                }
            }

            if (!isset($response)) {
                $parent_dir = dirname($row['file_path']);
                $original_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
                $new_name = $original_name;
                $counter = 1;
                $new_path = $parent_dir . '/' . $new_name;

                while (file_exists(NV_ROOTDIR . $new_path)) {
                    $new_name = $original_name . '(' . $counter++ . ')';
                    $new_path = $parent_dir . '/' . $new_name;
                }

                $extractTo = NV_ROOTDIR . $new_path;
                if (!is_dir($extractTo)) {
                    mkdir($extractTo, 0777, true);
                }

                $zipArchive = new ZipArchive();
                if ($zipArchive->open($zipFilePath) !== true) {
                    nv_jsonOutput([
                        'status' => 'error',
                        'message' => $lang_module['cannot_open_zip']
                    ]);
                } else {
                    $numFiles = $zipArchive->numFiles;
                    $processedNames = [];

                    for ($i = 0; $i < $numFiles; $i++) {
                        $fileName = $zipArchive->getNameIndex($i);
                        $pathInfo = pathinfo($fileName);
                        $dirName = $pathInfo['dirname'] == '.' ? '' : $pathInfo['dirname'] . '/';
                        $baseName = $pathInfo['filename'];
                        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

                        $newFileName = $fileName;
                        $counter = 1;
                        while (isset($processedNames[$dirName . $baseName . $extension])) {
                            $newFileName = $dirName . $baseName . '(' . $counter++ . ')' . $extension;
                        }

                        if ($fileName !== $newFileName) {
                            $zipArchive->renameName($fileName, $newFileName);
                        }
                        $processedNames[$newFileName] = true;
                    }

                    if (!$zipArchive->extractTo($extractTo)) {
                        nv_jsonOutput([
                            'status' => 'error',
                            'message' => $lang_module['extract_failed']
                        ]);
                    } else {
                        $zipArchive->close();

                        $file_size_zip = $file_size;
                        $parent_id = $db->query('SELECT file_id FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_path = ' . $db->quote($parent_dir) . ' AND is_folder = 1 AND status = 1')->fetchColumn() ?: 0;

                        $sql = 'INSERT INTO ' . NV_PREFIXLANG . '_' . $module_data . '_files 
                                (file_name, file_path, file_size, is_folder, compressed, created_at, lev) 
                                VALUES (:new_name, :new_path, :file_size, 1, 0, :created_at, :lev)';
                        $stmt = $db->prepare($sql);
                        $stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
                        $stmt->bindParam(':new_path', $new_path, PDO::PARAM_STR);
                        $stmt->bindParam(':file_size', $file_size_zip, PDO::PARAM_INT);
                        $stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
                        $stmt->bindValue(':lev', $parent_id, PDO::PARAM_INT);
                        $stmt->execute();

                        $new_id = $db->lastInsertId();
                        updateAlias($new_id, $new_name);
                        addToDatabase($extractTo, $new_id);

                        $permissions = getParentPermissions($lev);
                        updatePermissions($new_id, $permissions['p_group'], $permissions['p_other']);

                        nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['unzip'], 'File id: ' . $new_id, $user_info['userid']);

                        nv_jsonOutput([
                            'status' => 'success',
                            'message' => $lang_module['unzip_ok'],
                            'redirect' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main']
                        ]);
                    }
                }
            }
        }
    }
}

$tree = buildTree($list);
$tree_html = displayTree($tree);
$reponse = [
    'status' => $status,
    'message' => $message
];


$contents = nv_fileserver_compress($row, $list, $reponse, $tree_html, $current_permission, $can_unzip);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
