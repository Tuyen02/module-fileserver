<?php

/**
 * NukeViet Content Management System
 * @version 4.x
 * @author VINADES.,JSC <contact@vinades.vn>
 * @copyright (C) 2009-2021 VINADES.,JSC. All rights reserved
 * @license GNU/GPL version 2 or any later version
 * @see https://github.com/nukeviet The NukeViet CMS GitHub project
 */

if (!defined('NV_ADMIN')) {
    exit('Stop!!!');
}

if (defined('NV_IS_SPADMIN')) {
    $submenu['main'] = $lang_module['choose_group'];
    $submenu['export'] = $lang_module['export'];
    $submenu['import'] = $lang_module['import'];
    $submenu['recycle_bin'] = $lang_module['recycle_bin'];
    $submenu['config'] = $lang_module['config'];
}