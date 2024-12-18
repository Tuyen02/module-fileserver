<?php

if (!defined('NV_IS_FILE_SITEINFO')) {
    exit('Stop!!!');
}

$lang_siteinfo = nv_get_lang_module($mod);

if(!empty($array_op)){
    preg_match('/^([a-z0-9\_\-]+)\-([0-9]+)$/', $array_op[1], $m);
    $lev = $m[2];
    $file_id = $m[2];
}else{
    $lev = $nv_Request->get_int("lev", "get,post", 0);
}

$_arr_siteinfo = [];
$_arr_siteinfo['number_file'] = $db_slave->query("SELECT total_files FROM " . NV_PREFIXLANG . '_' . $module_data . "_logs WHERE lev = ". $lev)->fetch(PDO::FETCH_ASSOC);

$siteinfo[] = [
    'key' => $lang_siteinfo['total_file'],
    'value' => number_format($_arr_siteinfo['number_file'])
];

