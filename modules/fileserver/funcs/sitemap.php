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

$url = [];
$cacheFile = NV_LANG_DATA . '_sitemap_' . NV_CACHE_PREFIX . '.cache';
$cacheTTL = 7200;

if (($cache = $nv_Cache->getItem($module_name, $cacheFile, $cacheTTL)) != false) {
    $url = unserialize($cache);
} else {
    $db_slave->sqlreset()
        ->select('file_id, lev, create_at, alias')
        ->from(NV_PREFIXLANG . '_' . $module_data . '_files')
        ->where('status=1')
        ->order($order_articles_by . ' DESC')
        ->limit(1000);
    $result = $db_slave->query($db_slave->sql());

    $url = [];

    while (list($file_id, $lev_i, $create_at, $alias) = $result->fetch(3)) {
        $catalias = $global_array_cat[$lev_i]['alias'];
        $url[] = [
            'link' => NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $catalias . '/' . $alias . '-' . $file_id . $global_config['rewrite_exturl'],
            'publtime' => $create_at,
            'changefreq' => 'daily',
            'priority' => '0.8'
        ];
    }

    $cache = serialize($url);
    $nv_Cache->setItem($module_name, $cacheFile, $cache, $cacheTTL);
}

nv_xmlSitemap_generate($url);
exit();
