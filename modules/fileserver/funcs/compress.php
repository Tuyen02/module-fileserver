<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['compress'];

$action = $nv_Request->get_title('action', 'post', '');
$page = $nv_Request->get_int('page', 'get', 1);
$base_dir = '/uploads/fileserver';
$current_permission = get_user_permission($lev, $row);

$sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $lev;
$result = $db->query($sql);
$row = $result->fetch();

$base_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => $base_url
];

$status = '';
$message = '';
$list = [];
$file_size_zip = 0;

if (!$row) {
    $status = $lang_module['error'];
    $message = $lang_module['f_has_exit'];
} else {
    $zipFilePath = NV_ROOTDIR . $row['file_path'];
    $extractTo = NV_ROOTDIR . $base_dir . '/' . pathinfo($row['file_name'], PATHINFO_FILENAME);

    if ($action == 'unzip' && $row['compressed'] != 0) {
        if (!defined('NV_IS_SPADMIN')) {
            $unauthorized_files = [];
            $compressed_files = explode(',', $row['compressed']);

            if (!empty($compressed_files)) {
                $placeholders = implode(',', array_fill(0, count($compressed_files), '?'));
                $sql = 'SELECT f.file_id, f.file_name, f.is_folder, p.p_group, p.p_other 
                        FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files f 
                        LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_data . '_permissions p ON f.file_id = p.file_id 
                        WHERE f.file_id IN (' . $placeholders . ')';
                $stmt = $db->prepare($sql);
                $stmt->execute($compressed_files);
                $files = $stmt->fetchAll();

                foreach ($files as $file) {
                    $current_permission = $is_group_user ? $file['p_group'] : $file['p_other'];
                    if ($current_permission < 3) {
                        $unauthorized_files[] = $file['file_name'];
                    }
                }
            }

            if (!empty($unauthorized_files)) {
                nv_jsonOutput([
                    'status' => 'error',
                    'message' => $lang_module['folder_has_restricted_items'] . ': ' . implode(', ', $unauthorized_files)
                ]);
            }
        }

        $original_name = pathinfo($row['file_name'], PATHINFO_FILENAME);
        $new_name = $original_name;
        $counter = 1;
        $new_path = $base_dir . '/' . $new_name;

        while (file_exists(NV_ROOTDIR . $new_path)) {
            $new_name = $original_name . '(' . $counter . ')';
            $new_path = $base_dir . '/' . $new_name;
            $counter++;
        }

        $extractTo = NV_ROOTDIR . $new_path;

        if (!is_dir($extractTo)) {
            mkdir($extractTo, 777, true);
        }

        $zipArchive = new ZipArchive();
        if ($zipArchive->open($zipFilePath) == TRUE) {
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
                    $baseName = $pathInfo['filename'] . '(' . $counter . ')';
                    $newFileName = $dirName . $baseName . $extension;
                    $counter++;
                }

                if ($fileName !== $newFileName) {
                    $zipArchive->renameName($fileName, $newFileName);
                }

                $processedNames[$newFileName] = true;
            }

            $zipArchive->extractTo($extractTo);
            $zipArchive->close();

            $file_size_zip = filesize($zipFilePath);

            $status = $lang_module['success'];
            $message = $lang_module['unzip_ok'];

            $insert_sql = 'INSERT INTO ' . NV_PREFIXLANG . '_fileserver_files 
                           (file_name, file_path, file_size, is_folder, compressed, created_at) 
                           VALUES (:new_name, :new_path, :file_size, 1, 0, :created_at)';
            $insert_stmt = $db->prepare($insert_sql);
            $insert_stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
            $insert_stmt->bindParam(':new_path', $new_path, PDO::PARAM_STR);
            $insert_stmt->bindParam(':file_size', $file_size_zip, PDO::PARAM_INT);
            $insert_stmt->bindValue(':created_at', NV_CURRENTTIME, PDO::PARAM_INT);
            $insert_stmt->execute();

            $new_id = $db->lastInsertId();
            updateAlias($new_id, $new_name);
            addToDatabase($extractTo, $new_id);

            $permissions = getParentPermissions($lev);
            updatePermissions($new_id, $permissions['p_group'], $permissions['p_other']);

            nv_insert_logs(NV_LANG_DATA, $module_name, $lang_module['unzip'], 'File id: ' . $new_id, $user_info['userid']);

            $redirect_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'];
            nv_jsonOutput(['status' => 'success', 'message' => $message, 'redirect' => $redirect_url]);
        } else {
            $status = $lang_module['error'];
            $message = $lang_module['unzip_false'];
            $compressed = $row['compressed'];
            $fileIds = explode(',', $compressed);

            if (!empty($fileIds)) {
                $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
                $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id IN (' . $placeholders . ')';
                $stmt = $db->prepare($sql);
                $stmt->execute($fileIds);
                $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            nv_jsonOutput(['status' => 'success', 'message' => $message]);
        }
    } else {
        $compressed = $row['compressed'];
        $fileIds = explode(',', $compressed);

        if (!empty($fileIds)) {
            $placeholders = implode(',', array_fill(0, count($fileIds), '?'));
            $sql = 'SELECT * FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id IN (' . $placeholders . ')';
            $stmt = $db->prepare($sql);
            $stmt->execute($fileIds);
            $list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

$tree = buildTree($list);
$tree_html = displayTree($tree);

$contents = nv_fileserver_compress($list, $row['file_id'], $status, $message, $tree_html, $current_permission);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
