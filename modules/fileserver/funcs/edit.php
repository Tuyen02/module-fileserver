<?php
if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

$page_title = $lang_module['edit'];

$use_elastic = $module_config['fileserver']['use_elastic'];

$page = $nv_Request->get_int('page', 'get', 1);

$sql = 'SELECT file_name, file_path, lev, alias FROM ' . NV_PREFIXLANG . '_' . $module_data . '_files WHERE file_id = ' . $file_id;
$result = $db->query($sql);
$row = $result->fetch();

if (empty($row)) {
    nv_redirect_location(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA);
}

$array_mod_title[] = [
    'catid' => 0,
    'title' => $row['file_name'],
    'link' => nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'])
];

$view_url = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $module_info['alias']['main'] . '&lev=' . $row['lev'];

$file_name = $row['file_name'];
$file_path = $row['file_path'];
$full_path = NV_ROOTDIR . $file_path;
$file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

$file_content = '';
if (file_exists($full_path)) {
    if ($file_extension == 'pdf') {
        $file_content = $file_path;
    } elseif (in_array($file_extension, ['doc', 'docx'])) {
        $zip = new ZipArchive;
        if ($zip->open($full_path) == true) {
            if (($index = $zip->locateName('word/document.xml')) != false) {
                $data = $zip->getFromIndex($index);
                $xml = new SimpleXMLElement($data);
                $file_content = strip_tags($xml->asXML());
            }
            $zip->close();
        }
    } else {
        $file_content = file_get_contents($full_path);
    }
}

$status = '';
$message = '';

if (!defined('NV_IS_SPADMIN')) {
    $is_group_user = isset($user_info['in_groups']) && is_array($user_info['in_groups']) && !empty(array_intersect($user_info['in_groups'], $config_value_array));
    
    if (!$is_group_user) {
        $status = $lang_module['error'];
        $message = $lang_module['not_permission_to_edit'];
    } else {
        $sql_per = 'SELECT p_group FROM ' . NV_PREFIXLANG . '_' . $module_data . '_permissions WHERE file_id = ' . $file_id;
        $result_per = $db->query($sql_per);
        $row_per = $result_per->fetch();

        if (empty($row_per)) {
            $status = $lang_module['error'];
            $message = $lang_module['file_not_found'];
        } elseif ($row_per['p_group'] < 3) {
            $status = $lang_module['error'];
            $message = $lang_module['not_permission_to_edit'];
        }
    }
}

if (empty($status) && $nv_Request->get_int('file_id', 'post') > 0) {
    if ($file_extension == 'pdf') {
        $file_path = $row['file_path'];
    } elseif (in_array($file_extension, ['doc', 'docx'])) {
        $file_content = $nv_Request->get_string('file_content', 'post');
        $zip = new ZipArchive;
        if ($zip->open($full_path) == true) {
            if (($index = $zip->locateName('word/document.xml')) != false) {
                $xml = new SimpleXMLElement('<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"/>');
                $body = $xml->addChild('w:body');
                $body->addChild('w:p', htmlspecialchars($file_content));
                $zip->addFromString('word/document.xml', $xml->asXML());
            }
            $zip->close();
        }
    } else {
        $file_content = $nv_Request->get_string('file_content', 'post');
        file_put_contents($full_path, $file_content);
    }

    $file_size = filesize($full_path);

    $sql = 'UPDATE ' . NV_PREFIXLANG . '_' . $module_data . '_files SET updated_at = :updated_at, file_size = :file_size, elastic = :elastic WHERE file_id = :file_id';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':updated_at', NV_CURRENTTIME, PDO::PARAM_INT);
    $stmt->bindValue(':file_size', $file_size, PDO::PARAM_INT);
    $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
    $stmt->bindValue(':elastic', 0, PDO::PARAM_INT);

    if ($stmt->execute()) {
        updateLog($row['lev']);
        nv_insert_logs(NV_LANG_DATA, $module_name, 'edit','File id: ' . $file_id, $user_info['userid']);

        if ($row['lev'] > 0) {
            updateParentFolderSize($row['lev']);
        }

        $status = $lang_module['success'];
        $message = $lang_module['update_ok'];
    }
}

$contents = nv_fileserver_edit($row, $file_content, $file_id, $file_name, $view_url, $status, $message);

include NV_ROOTDIR . '/includes/header.php';
echo nv_site_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
