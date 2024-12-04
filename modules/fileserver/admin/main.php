<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}
$page_title = 'FILE SERVER';

$post = [];
$mess = '';

if ($nv_Request->isset_request("submit", "post")) {
    $post["config_value"] = $nv_Request->get_title('config_value', "post", '');

    if ($post['config_value'] == '') {
        $mess = "Chua nhap";
    }
    $config_name = 'group_admin_fileserver';
    $lang = 'vi';
    $sql = "INSERT INTO nv4_config(lang, module,config_name,config_value) 
                VALUES (:lang, :module, :config_name,:config_value)
                ON DUPLICATE KEY UPDATE 
                        config_value = :config_value_update";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":lang", $lang, PDO::PARAM_STR);
    $stmt->bindParam(":module", $module_name, PDO::PARAM_STR);
    $stmt->bindParam(":config_name", $config_name, PDO::PARAM_STR);
    $stmt->bindParam(":config_value", $post['config_value'], PDO::PARAM_STR);
    $stmt->bindParam(":config_value_update", $post['config_value'], PDO::PARAM_STR);
    $exe = $stmt->execute();
    $mess = "Update oke";
}

$xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('NV_LANG_VARIABLE', NV_LANG_VARIABLE);
$xtpl->assign('NV_LANG_DATA', NV_LANG_DATA);
$xtpl->assign('NV_BASE_ADMINURL', NV_BASE_ADMINURL);
$xtpl->assign('NV_NAME_VARIABLE', NV_NAME_VARIABLE);
$xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('OP', $op);
$xtpl->assign('POST', $post);

if ($mess != '') {
    $xtpl->assign('MESSAGE', $mess);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
