<?php
if (!defined('NV_IS_MOD_SEARCH')) {
    exit('Stop!!!');
}

$link = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$logic = '';
$sql = "SELECT * FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_name LIKE " . $db->quote('%' . $key . '%');
$num_items = $db->query($sql)->rowCount();

if ($num_items > 0) {
    $arr_data = $db->query($sql)->fetchAll();

    foreach ($arr_data as $_data) {
        $result_array[] = array(
            'link' => $link,
            'title' => BoldKeywordInStr($_data['file_name'], $key, $logic),
            'content' => BoldKeywordInStr($_data['file_name'], $key, $logic)
        );
    }
}