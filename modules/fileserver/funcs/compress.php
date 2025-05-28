<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['compress'];
$action = $nv_Request->get_title('action', 'post', '');
$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT file_id, file_name, file_path, compressed, alias, lev, uploaded_by FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $lev;
$row = $db->query($sql)->fetch();

if (empty($row)) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name);
}

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];

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

if ($action == 'unzip' && $row['compressed'] != 0) {
    if (!defined('NV_IS_SPADMIN')) {
        $is_group_user = !empty($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
        $compressed_files = explode(',', $row['compressed']);
        if (!empty($compressed_files)) {
            $sql = 'SELECT f.file_name, p.p_group, p.p_other FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f 
                    LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id 
                    WHERE f.file_id IN (' . implode(',', array_fill(0, count($compressed_files), '?')) . ')';
            $stmt = $db->prepare($sql);
            $stmt->execute($compressed_files);
            $unauthorized_files = array_column(array_filter($stmt->fetchAll(), function($file) use ($is_group_user) {
                return ($is_group_user ? $file['p_group'] : $file['p_other']) < 3;
            }), 'file_name');

            if (!empty($unauthorized_files)) {
                nv_jsonOutput([
                    'status' => 'error',
                    'message' => $lang_module['folder_has_restricted_items'] . ': ' . implode(', ', $unauthorized_files)
                ]);
            }
        }
    }

    $zipFilePath = NV_ROOTDIR . $row['file_path'];
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
    if ($zipArchive->open($zipFilePath) === true) {
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

        $zipArchive->extractTo($extractTo);
        $zipArchive->close();

        $file_size_zip = filesize($zipFilePath);
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

        $redirect_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'];
        nv_jsonOutput(['status' => 'success', 'message' => $lang_module['unzip_ok'], 'redirect' => $redirect_url]);
    }

    $status = $lang_module['error'];
    $message = $lang_module['unzip_false'];
}

if (!empty($row['compressed'])) {
    $fileIds = explode(',', $row['compressed']);
    if (!empty($fileIds)) {
        $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id IN (' . implode(',', array_fill(0, count($fileIds), '?')) . ')';
        $stmt = $db->prepare($sql);
        $stmt->execute($fileIds);
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if(empty($list)){
    $status = $lang_module['error'];
    $message = $lang_module['download_to_view'];
}

$tree = buildTree($list);
$tree_html = displayTree($tree);
$reponse = [
    'status' => $status,
    'message' => $message,
];
$current_permission = get_user_permission($lev, $row['uploaded_by']);

$contents = nv_fileserver_compress($row, $list, $reponse, $tree_html,$current_permission);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
