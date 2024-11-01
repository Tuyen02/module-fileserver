<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

if ($nv_Request->isset_request("file_id", "get") && $nv_Request->isset_request("new_name", "post")) {
    $file_id = $nv_Request->get_int("file_id", "get", 0);
    $new_name = $nv_Request->get_title("new_name", "post", '');

    if ($file_id > 0 && !empty($new_name)) {
        $stmt = $db->prepare("UPDATE " . NV_PREFIXLANG . "_fileserver_files SET file_name = :new_name WHERE file_id = :file_id");
        $stmt->bindParam(':new_name', $new_name, PDO::PARAM_STR);
        $stmt->bindParam(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->execute();
    }
}

header("Location: " . NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main');
exit();