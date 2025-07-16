<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

global $global_config, $module_name, $module_info, $module_file, $lang_module, $client_info, $db, $user_info, $module_config;

if (file_exists(NV_ROOTDIR . '/themes/' . $module_info['template'] . '/modules/' . $module_info['module_theme'] . '/block_content.tpl')) {
    $block_theme = $module_info['template'];
} else {
    $block_theme = 'default';
}
$xtpl = new XTemplate('block_content.tpl', NV_ROOTDIR . '/themes/' . $block_theme . '/modules/' . $module_file);
$xtpl->assign('NV_BASE_SITEURL', NV_BASE_SITEURL);
$xtpl->assign('TEMPLATE', $block_theme);
$xtpl->assign('LANG', $lang_module);

$sql_all = 'SELECT f.*, p.p_group, p.p_other 
    FROM ' . NV_PREFIXLANG . '_' . $module_name . '_files f
    LEFT JOIN ' . NV_PREFIXLANG . '_' . $module_name . '_permissions p ON f.file_id = p.file_id 
    WHERE f.status = 1';
$result_all = $db->query($sql_all)->fetchAll(PDO::FETCH_ASSOC);

$admin_groups = explode(',', $module_config[$module_name]['group_admin_fileserver']);
$user_groups = [];
if (isset($user_info['in_groups'])) {
    $user_groups = is_array($user_info['in_groups']) ? $user_info['in_groups'] : array_map('intval', explode(',', $user_info['in_groups']));
}
$is_group_user = !empty(array_intersect($user_groups, $admin_groups));

$filtered = array_filter($result_all, function ($item) use ($is_group_user) {
    if (defined('NV_IS_SPADMIN')) {
        return true;
    }
    if ($is_group_user) {
        return isset($item['p_group']) && $item['p_group'] >= 2;
    } else {
        return isset($item['p_other']) && $item['p_other'] == 2;
    }
});

require_once NV_ROOTDIR . '/modules/' . $module_file . '/functions.php';
$tree = buildTree($filtered);
$tree_html = displayAllTree($tree, 0, true);

$xtpl->assign('TREE', $tree_html);

$xtpl->parse('main');
$content = $xtpl->text('main');
