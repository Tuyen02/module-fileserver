<?php

if (!defined('NV_IS_MOD_FILESERVER'))
    die('Stop!!!');

$page_title = $lang_module['rss'];
$channel = array();
$items = array();

$channel['title'] = $module_info['custom_title'];
$channel['link'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name;
$channel['description'] = !empty($module_info['description']) ? $module_info['description'] : $global_config['site_description'];

$db->sqlreset()->select('file_id, file_name, alias')->order('file_id DESC')->limit(30);

$where = 'status = 1';

if (isset($array_op[1])) {
    $_catid = 0;
    foreach ($global_array_cat as $cat) {
        if ($cat['alias'] == $array_op[1]) {
            $_catid = $cat['lev'];
            break;
        }
    }

    if (!empty($_catid)) {
        $where .= ' AND lev LIKE \'%,' . $_catid . ',%\'';
    } else {
        header('location:' . nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op, true));
        die();
    }
}

$db->from(NV_PREFIXLANG . '_' . $module_data . '_files')->where($where);

if ($module_info['rss'] == 0) {
    $result = $db->query($db->sql());
    while (list($file_id, $file_name, $alias) = $result->fetch(3)) {

        $items[] = array(
            'title' => $file_name,
            'link' => NV_MY_DOMAIN . NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $alias
        );
    }
}

nv_rss_generate($channel, $items);
die();