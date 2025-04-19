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

function nv_fileserver_main($op, $result, $page_url, $error, $success, $permissions, $selected_all, $selected_file, $selected_folder, $total, $perpage, $base_url, $lev, $search_term, $search_type, $page, $logs, $reCaptchaPass, $back_url)
{
    global $module_file, $global_config, $lang_module, $module_name, $module_config, $lang_global, $user_info, $module_data, $db, $back_url;

    $xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FORM_ACTION', $base_url);
    $xtpl->assign('PAGE_URL', $page_url);
    $xtpl->assign('SEARCH_TERM', $search_term);

    $xtpl->assign('SELECTED_ALL', $selected_all);
    $xtpl->assign('SELECTED_FILE', $selected_file);
    $xtpl->assign('SELECTED_FOLDER', $selected_folder);

    if (!empty($back_url)) {
        $xtpl->assign('BACK_URL', $back_url);
        $xtpl->parse('main.back');
    }

    if ($total > $perpage) {
        $page_url = $base_url . '&lev=' . $lev . '&search=' . $search_term . '&search_type=' . $search_type;
        $generate_page = nv_generate_page($page_url, $total, $perpage, $page);
        $xtpl->assign('GENERATE_PAGE', $generate_page);
    }

    if ($error != '') {
        $xtpl->assign('ERROR', $error);
        $xtpl->parse('main.error');
    }
    if ($success != '') {
        $xtpl->assign('success', $success);
        $xtpl->parse('main.success');
    }

    $show_create_buttons = false;
    if (defined('NV_IS_SPADMIN')) {
        $show_create_buttons = true;
    }

    if ($show_create_buttons) {
        $xtpl->parse('main.can_create');
    }

    if (empty($result) && $lev == 0) {
        $xtpl->parse('main.no_data');
    } else {
        foreach ($result as $row) {
            if (!empty($logs)) {
                $row['total_size'] = nv_convertfromBytes($logs['total_size']);
                $row['total_files'] = $logs['total_files'];
                $row['total_folders'] = $logs['total_folders'];
            }

            $row['created_at'] = date('d/m/Y H:i:s', $row['created_at']);
            $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
            $row['icon_class'] = getFileIconClass($row);

            if ($permissions) {
                $row['p_group'] = $permissions['p_group'];
                $row['p_other'] = $permissions['p_other'];
            }

            $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'] . '&page=' . $page;
            $row['url_perm'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm/' . $row['alias'] . '&page=' . $page;
            $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit/' . $row['alias'] . '&page=' . $page;
            $row['url_edit_img'] = nv_url_rewrite(NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit_img/' . $row['alias'] . '&page=' . $page);
            $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&amp;file_id=' . $row['file_id'] . '&action=delete&checksess=' . md5($row['file_id'] . NV_CHECK_SESSION);
            $row['url_download'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&amp;file_id=' . $row['file_id'] . '&download=1';
            $row['url_clone'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone/' . $row['alias'];
            $row['url_rename'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=rename/' . $row['alias'];
            $url_share = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share/' . $row['alias'];
            $row['url_compress'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress/' . $row['alias'];
            $row['url_share'] = $url_share;

            $current_permission = get_user_permission($row['file_id'], $row);

            $row['file_size'] = nv_convertfromBytes($row['file_size']);
            $xtpl->assign('ROW', $row);

            $fileInfo = pathinfo($row['file_name'], PATHINFO_EXTENSION);

            $xtpl->assign('DOWNLOAD', $row['url_download']);
            $xtpl->parse('main.file_row.download');

            if (defined('NV_IS_SPADMIN')) {
                $xtpl->parse('main.file_row.delete');
                $xtpl->parse('main.file_row.rename');
                $xtpl->parse('main.file_row.share');

                if ($row['is_folder'] == 0) {
                    $xtpl->assign('VIEW', $row['url_edit']);
                    $xtpl->parse('main.file_row.view');

                    if ($row['compressed'] != 0) {
                        $xtpl->assign('VIEW', $row['url_compress']);
                        $xtpl->parse('main.file_row.view');
                    }

                    if ($fileInfo == 'txt' || $fileInfo == 'php' || $fileInfo == 'html' || $fileInfo == 'css' || $fileInfo == 'js' || $fileInfo == 'json' || $fileInfo == 'xml' || $fileInfo == 'sql' || $fileInfo == 'doc' || $fileInfo == 'docx' || $fileInfo == 'xls' || $fileInfo == 'xlsx') {
                        $xtpl->assign('EDIT', $row['url_edit']);
                        $xtpl->parse('main.file_row.edit');
                    } else if ($fileInfo == 'png' || $fileInfo == 'jpg' || $fileInfo == 'jpeg' || $fileInfo == 'gif' || $fileInfo == 'mp3' || $fileInfo == 'mp4') {
                        $xtpl->assign('VIEW', $row['url_edit_img']);
                        $xtpl->parse('main.file_row.view');
                    }

                    $xtpl->assign('COPY', $row['url_clone']);
                    $xtpl->parse('main.file_row.copy');
                } else {
                    $xtpl->assign('VIEW', $row['url_view']);
                    $xtpl->parse('main.file_row.view');
                }
            } else {
                if ($current_permission == 3) {
                    $xtpl->parse('main.file_row.delete');
                    $xtpl->parse('main.file_row.rename');

                    if ($row['is_folder'] == 0) {
                        $xtpl->assign('VIEW', $row['url_edit']);
                        $xtpl->parse('main.file_row.view');

                        if ($row['compressed'] != 0) {
                            $xtpl->assign('VIEW', $row['url_compress']);
                            $xtpl->parse('main.file_row.view');
                        }

                        if ($fileInfo == 'txt' || $fileInfo == 'php' || $fileInfo == 'html' || $fileInfo == 'css' || $fileInfo == 'js' || $fileInfo == 'json' || $fileInfo == 'xml' || $fileInfo == 'sql' || $fileInfo == 'doc' || $fileInfo == 'docx' || $fileInfo == 'xls' || $fileInfo == 'xlsx') {
                            $xtpl->assign('EDIT', $row['url_edit']);
                            $xtpl->parse('main.file_row.edit');
                        } else if ($fileInfo == 'png' || $fileInfo == 'jpg' || $fileInfo == 'jpeg' || $fileInfo == 'gif' || $fileInfo == 'mp3' || $fileInfo == 'mp4') {
                            $xtpl->assign('VIEW', $row['url_edit_img']);
                            $xtpl->parse('main.file_row.view');
                        }

                        $xtpl->assign('COPY', $row['url_clone']);
                        $xtpl->parse('main.file_row.copy');
                    } else {
                        $xtpl->assign('VIEW', $row['url_view']);
                        $xtpl->parse('main.file_row.view');
                    }
                } else if ($current_permission == 2) {
                    if ($row['is_folder'] == 0) {
                        $xtpl->assign('VIEW', $row['url_edit']);
                        $xtpl->parse('main.file_row.view');

                        if ($row['compressed'] != 0) {
                            $xtpl->assign('VIEW', $row['url_compress']);
                            $xtpl->parse('main.file_row.view');
                        }

                        if ($fileInfo == 'txt' || $fileInfo == 'php' || $fileInfo == 'html' || $fileInfo == 'css' || $fileInfo == 'js' || $fileInfo == 'json' || $fileInfo == 'xml' || $fileInfo == 'sql' || $fileInfo == 'pdf' || $fileInfo == 'doc' || $fileInfo == 'docx' || $fileInfo == 'xls' || $fileInfo == 'xlsx') {
                            $xtpl->assign('VIEW', $row['url_edit']);
                            $xtpl->parse('main.file_row.view');
                        } else if ($fileInfo == 'png' || $fileInfo == 'jpg' || $fileInfo == 'jpeg' || $fileInfo == 'gif' || $fileInfo == 'mp3' || $fileInfo == 'mp4') {
                            $xtpl->assign('VIEW', $row['url_edit_img']);
                            $xtpl->parse('main.file_row.view');
                        }
                    } else {
                        $xtpl->assign('VIEW', $row['url_view']);
                        $xtpl->parse('main.file_row.view');
                    }
                }
            }

            $xtpl->parse('main.file_row');
        }

        if (defined('NV_IS_SPADMIN')) {
            $xtpl->parse('main.stats');
        }
        if ($show_create_buttons) {
            $xtpl->parse('main.can_compress');
            $xtpl->parse('main.can_delete_all');
        }
    }

    if ($module_config[$module_name]['captcha_type'] == 'recaptcha' and $reCaptchaPass and $global_config['recaptcha_ver'] == 3) {
        $xtpl->parse('main.recaptcha3');
    } elseif ($module_config[$module_name]['captcha_type'] == 'recaptcha' and $reCaptchaPass and $global_config['recaptcha_ver'] == 2) {
        $xtpl->assign('RECAPTCHA_ELEMENT', 'recaptcha' . nv_genpass(8));
        $xtpl->assign('N_CAPTCHA', $lang_global['securitycode1']);
        $xtpl->parse('main.recaptcha');
    } elseif ($module_config[$module_name]['captcha_type'] == 'captcha') {
        $xtpl->assign('GFX_WIDTH', NV_GFX_WIDTH);
        $xtpl->assign('GFX_HEIGHT', NV_GFX_HEIGHT);
        $xtpl->assign('NV_BASE_SITEURL', NV_BASE_SITEURL);
        $xtpl->assign('CAPTCHA_REFRESH', $lang_global['captcharefresh']);
        $xtpl->assign('NV_GFX_NUM', NV_GFX_NUM);
        $xtpl->parse('main.captcha');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_clone($row, $file_id, $file_name, $file_path, $status, $message, $selected_folder_path, $view_url, $directories, $page_url, $base_url, $has_root_level)
{
    global $module_file, $global_config, $lang_module;

    $xtpl = new XTemplate('clone.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('FILE_PATH', $file_path);
    $xtpl->assign('MESSAGE', $message);
    $xtpl->assign('SELECTED_FOLDER_PATH', $selected_folder_path);

    $xtpl->assign('url_view', $view_url);

    if (!empty($selected_folder_path)) {
        $xtpl->assign('BACK', '');
        $xtpl->parse('main.back');
    }

    foreach ($directories as $directory) {
        $directory['url'] = $page_url . '&rank=' . $directory['file_id'];
        $xtpl->assign('DIRECTORY', $directory);
        $xtpl->parse('main.directory_option');
    }

    if ($has_root_level) {
        $xtpl->assign('ROOT_URL', $page_url . '&root=1');
        $xtpl->parse('main.root_option');
    }

    if (!empty($message)) {
        $message_class = ($status == 'success') ? 'alert-success' : 'alert-danger';
        $xtpl->assign('MESSAGE_CLASS', $message_class);
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $url_copy = $base_url . '&copy=1';
    $xtpl->assign('url_copy', $url_copy);

    $url_move = $base_url . '&move=1';
    $xtpl->assign('url_move', $url_move);

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_compress($file_id, $list, $status, $message, $tree_html, $current_permission)
{
    global $module_file, $global_config, $lang_module;

    $xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $file_id);

    if ($current_permission = 3 || defined('NV_IS_SPADMIN')) {
        $xtpl->parse('main.can_unzip');
    }


    if (!empty($list)) {
        $xtpl->assign('TREE_HTML', $tree_html);
    }

    if (!empty($message)) {
        $message_class = ($status == 'success') ? 'alert-success' : 'alert-danger';
        $xtpl->assign('MESSAGE_CLASS', $message_class);
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');

    return $xtpl->text('main');
}

function nv_fileserver_edit_img($row, $file_id, $file_extension)
{
    global $module_file, $global_config, $lang_module;

    $xtpl = new XTemplate('edit_img.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);

    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);

    if ($file_extension == 'mp3') {
        $xtpl->assign('audio', '');
        $xtpl->parse('main.audio');
    } else if ($file_extension == 'mp4') {
        $xtpl->assign('video', '');
        $xtpl->parse('main.video');
    } else if ($file_extension == 'jpg' || $file_extension == 'jpeg' || $file_extension == 'png' || $file_extension == 'gif') {
        $xtpl->assign('IMG', '');
        $xtpl->parse('main.img');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_edit($row, $file_content, $file_id, $file_name, $view_url, $status, $message, $back_url, $current_permission)
{
    global $module_file, $global_config, $lang_module, $module_name, $user_info, $module_data, $db, $module_config;

    $xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('url_view', $view_url);

    if (!empty($back_url)) {
        $xtpl->assign('BACK_URL', $back_url);
        $xtpl->parse('main.back');
    }

    $current_permission = get_user_permission($file_id, $row);
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

    if ($current_permission < 3 && !defined('NV_IS_SPADMIN')) {
        $xtpl->assign('DISABLE_CLASS', 'readonly-editor');
        $xtpl->assign('DISABLE_ATTR', 'readonly');
        $xtpl->assign('READONLY', 'true');
    } else {
        $xtpl->assign('DISABLE_CLASS', '');
        $xtpl->assign('DISABLE_ATTR', '');
        $xtpl->assign('READONLY', 'false');
    }

    if (($current_permission >= 3 || defined('NV_IS_SPADMIN')) && $file_extension != 'pdf') {
        $xtpl->parse('main.can_save');
    }

    if (!empty($message)) {
        $message_class = ($status == 'success') ? 'alert-success' : 'alert-danger';
        $xtpl->assign('MESSAGE_CLASS', $message_class);
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    if (in_array($file_extension, ['txt', 'php', 'html', 'css', 'js', 'json', 'xml', 'sql'])) {
        $xtpl->assign('text', '');
        $xtpl->parse('main.text');
    } elseif ($file_extension == 'pdf') {
        $xtpl->assign('pdf', '');
        $xtpl->parse('main.pdf');
    } elseif (in_array($file_extension, ['doc', 'docx'])) {
        $xtpl->assign('docx', '');
        $xtpl->parse('main.docx');
    } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
        $xtpl->assign('excel', '');
        $xtpl->parse('main.excel');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_perm($row, $file_id, $group_level, $other_level, $status, $message, $back_url)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);
    $xtpl->assign('FILE_ID', $file_id);

    if (!empty($back_url)) {
        $xtpl->assign('BACK_URL', $back_url);
        $xtpl->parse('main.back');
    }

    $xtpl->assign('GROUP_LEVEL_1', $group_level == 1 ? 'selected' : '');
    $xtpl->assign('GROUP_LEVEL_2', $group_level == 2 ? 'selected' : '');
    $xtpl->assign('GROUP_LEVEL_3', $group_level == 3 ? 'selected' : '');

    $xtpl->assign('OTHER_LEVEL_1', $other_level == 1 ? 'selected' : '');
    $xtpl->assign('OTHER_LEVEL_2', $other_level == 2 ? 'selected' : '');

    if ($status) {
        $xtpl->assign('MESSAGE_CLASS', $status == 'success' ? 'alert-success' : 'alert-danger');
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_share($row, $file_content, $file_id, $file_name, $view, $view_url, $message)
{
    global $module_file, $global_config, $lang_module, $module_name;

    $xtpl = new XTemplate('share.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('VIEW', $view);
    $xtpl->assign('url_view', $view_url);

    if ($message != '') {
        $xtpl->assign('MESSAGE', $message);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main');

    return $xtpl->text('main');
}
