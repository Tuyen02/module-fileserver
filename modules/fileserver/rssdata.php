<?php
 
if (! defined('NV_IS_MOD_RSS')) {
    die('Stop!!!');
}

$sql = "SELECT file_id, lev, file_name as title, alias FROM " . NV_PREFIXLANG . "_fileserver_files WHERE status = 1 AND lev = 0 ORDER BY file_id DESC";
//$rssarray[] = array('file_id' => 0, 'lev' => 0, 'file_name' => '', 'link' => '');
 
$list = $nv_Cache->db($sql, '', $mod_name);
foreach ($list as $value) {
    $value['link'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $site_mods['fileserver']['module_data'] . '&amp;' . NV_OP_VARIABLE . '=main/' .$value['alias'];
    $rssarray[] = $value;
}