<?php
 
if (! defined('NV_IS_MOD_RSS')) {
    die('Stop!!!');
}
 
$rssarray = array();
if(!empty($array_op)){
    preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
    $lev = $m[2];
    $file_id = $m[2];
}else{
    $lev = $nv_Request->get_int("lev", "get,post", 0);
}

$sql = "SELECT file_id, lev, file_name, alias FROM " . NV_PREFIXLANG . "_fileserver_files WHERE status = 1 ORDER BY file_id DESC";
//$rssarray[] = array('file_id' => 0, 'lev' => 0, 'file_name' => '', 'link' => '');
 
$list = $nv_Cache->db($sql, '', $mod_name);
foreach ($list as $value) {
    $value['link'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=main/' .$value['alias'];
    $rssarray[] = $value;
}