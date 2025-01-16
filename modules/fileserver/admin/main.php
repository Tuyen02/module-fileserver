<?php

if (!defined('NV_IS_FILE_ADMIN')) {
    exit('Stop!!!');
}
$page_title = 'FILE SERVER';

$sql = "SELECT d.group_id, title FROM nv4_users_groups AS g LEFT JOIN nv4_users_groups_detail d 
        ON g.group_id = d.group_id AND d.lang = '" . NV_LANG_DATA . "' 
        WHERE g.idsite = " . $global_config['idsite'] . " OR (g.idsite = 0 AND g.group_id > 3 AND g.siteus = 1) 
        ORDER BY g.idsite, g.weight ASC";
$result = $db->query($sql);
$post = [];
$mess = '';
$post['group_ids'] = $nv_Request->get_array('group_ids', 'post', []);

if ($nv_Request->isset_request('submit', 'post')) {
    if (empty($post['group_ids'])) {
        $mess = 'Chưa chọn nhóm nào.';
    } else {
        $group_ids_str = implode(',', $post['group_ids']);
        $config_name = 'group_admin_fileserver';
        $lang = 'vi';
        $sql = "INSERT INTO nv4_config(lang, module, config_name, config_value) 
                VALUES (:lang, :module, :config_name, :config_value)
                ON DUPLICATE KEY UPDATE 
                        config_value = :config_value_update";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':lang', $lang, PDO::PARAM_STR);
        $stmt->bindParam(':module', $module_name, PDO::PARAM_STR);
        $stmt->bindParam(':config_name', $config_name, PDO::PARAM_STR);
        $stmt->bindParam(':config_value', $group_ids_str, PDO::PARAM_STR);
        $stmt->bindParam(':config_value_update', $group_ids_str, PDO::PARAM_STR);
        if ($exe = $stmt->execute()) {
            $mess = 'Cập nhật thành công.';
        }else{
            $mess = 'Cập nhật thất bại.';
        }
        
    }
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

foreach ($result as $row) {
    $xtpl->assign('ROW', $row);
    $xtpl->parse('main.loop');
}

if ($mess != '') {
    $xtpl->assign('MESSAGE', $mess);
    $xtpl->parse('main.message');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';
