<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE')) {
    exit('Stop!!!');
}

$module_version = [
    'name' => 'FileServer',
    'modfuncs' => 'main,edit,clone,share,compress,perm,edit_img',
    'is_sysmod' => 0,
    'virtual' => 1,
    'version' => '4.5.06',
    'date' => '31/10/2024',
    'author' => 'tuyen',
    'note' => '',
    'uploads_dir' => [
        $module_upload
    ]
];
