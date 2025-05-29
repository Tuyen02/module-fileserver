<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

function nv_fileserver_main($result, $page_url, $error, $success, $permissions, $selected, $base_url, $lev, $search_term, $logs, $back_url, $generate_page)
{
    global $module_file, $global_config, $lang_module, $module_name, $module_config, $lang_global, $editable_extensions, $viewable_extensions, $op;

    $xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FORM_ACTION', $base_url);
    $xtpl->assign('PAGE_URL', $page_url);
    $xtpl->assign('SEARCH_TERM', $search_term);
    $xtpl->assign('SELECTED_ALL', $selected['all']);
    $xtpl->assign('SELECTED_FILE', $selected['file']);
    $xtpl->assign('SELECTED_FOLDER', $selected['folder']);
    $xtpl->assign('GENERATE_PAGE', $generate_page);

    if (!empty($back_url)) {
        $xtpl->assign('BACK_URL', $back_url);
        $xtpl->parse('main.back');
    }
    if ($error) {
        $xtpl->assign('ERROR', $error);
        $xtpl->parse('main.error');
    }
    if ($success) {
        $xtpl->assign('SUCCESS', $success);
        $xtpl->parse('main.success');
    }

    $show_create_buttons = defined('NV_IS_SPADMIN');
    if ($show_create_buttons) {
        $xtpl->parse('main.can_create');
    }

    if (empty($result) && $lev == 0) {
        $xtpl->parse('main.no_data');
    } else {
        foreach ($result as $row) {
            if ($logs) {
                $row['total_size'] = isset($logs[$row['lev']]['total_size']) ? nv_convertfromBytes($logs[$row['lev']]['total_size']) : '0 B';
                $row['total_files'] = isset($logs[$row['lev']]['total_files']) ? $logs[$row['lev']]['total_files'] : 0;
                $row['total_folders'] = isset($logs[$row['lev']]['total_folders']) ? $logs[$row['lev']]['total_folders'] : 0;
            }
            $row['created_at'] = date('d/m/Y H:i:s', $row['created_at']);
            $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
            $row['icon_class'] = getFileIconClass($row);

            if ($permissions) {
                $row['p_group'] = isset($permissions[$row['file_id']]['p_group']) ? $permissions[$row['file_id']]['p_group'] : 1;
                $row['p_other'] = isset($permissions[$row['file_id']]['p_other']) ? $permissions[$row['file_id']]['p_other'] : 1;
            }

            $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
            $row['url_perm'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm/' . $row['alias'];
            $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit/' . $row['alias'];
            $row['url_edit_img'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit_img/' . $row['alias'];
            $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&amp;file_id=' . $row['file_id'] . '&action=delete&checksess=' . md5($row['file_id'] . NV_CHECK_SESSION);
            $row['url_download'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&amp;file_id=' . $row['file_id'] . '&download=1&token=' . md5($row['file_id'] . NV_CHECK_SESSION . $global_config['sitekey']);
            $row['url_clone'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone/' . $row['alias'];
            $row['url_rename'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=rename/' . $row['alias'];
            $row['url_compress'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress/' . $row['alias'];
            $row['url_share'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share/' . $row['alias'];

            $current_permission = get_user_permission($row['file_id'], $row);
            $row['file_size'] = nv_convertfromBytes($row['file_size']);
            $xtpl->assign('ROW', $row);

            $fileInfo = pathinfo($row['file_name'], PATHINFO_EXTENSION);
            $xtpl->assign('DOWNLOAD', $row['url_download']);
            $xtpl->parse('main.file_row.download');

            $view_urls = [];
            $is_zip_uncompressed = (strtolower($fileInfo) == 'zip' && empty($row['compressed']));

            if (defined('NV_IS_SPADMIN') || $current_permission == 3) {
                $xtpl->parse('main.file_row.delete');
                $xtpl->parse('main.file_row.rename');
                if (defined('NV_IS_SPADMIN'))
                    $xtpl->parse('main.file_row.share');
                if (!$row['is_folder']) {
                    $view_urls[] = $row['url_edit'];
                    if ($row['compressed'] || $is_zip_uncompressed) {
                        $view_urls[] = $row['url_compress'];
                    }
                    if (in_array($fileInfo, $editable_extensions)) {
                        $xtpl->assign('EDIT', $row['url_edit']);
                        $xtpl->parse('main.file_row.edit');
                    } elseif (in_array($fileInfo, $viewable_extensions)) {
                        $view_urls[] = $row['url_edit_img'];
                    }
                    $xtpl->assign('COPY', $row['url_clone']);
                    $xtpl->parse('main.file_row.copy');
                } else {
                    $view_urls[] = $row['url_view'];
                }
            } elseif ($current_permission == 2) {
                if (!$row['is_folder']) {
                    $view_urls[] = $row['url_edit'];
                    if ($row['compressed'] || $is_zip_uncompressed) {
                        $view_urls[] = $row['url_compress'];
                    }
                    if (in_array($fileInfo, $editable_extensions)) {
                        $xtpl->assign('EDIT', $row['url_edit']);
                    } elseif (in_array($fileInfo, $viewable_extensions)) {
                        $view_urls[] = $row['url_edit_img'];
                    }
                } else {
                    $view_urls[] = $row['url_view'];
                }
            }

            foreach ($view_urls as $view_url) {
                $xtpl->assign('VIEW', $view_url);
                $xtpl->parse('main.file_row.view');
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

    if (!empty($module_config[$module_name]['use_captcha'])) {
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

function nv_fileserver_clone($row, $reponse, $selected_folder_path, $view_url, $folder_tree, $base_url)
{
    global $module_file, $global_config, $lang_module;
    $xtpl = new XTemplate('clone.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $row['file_id']);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);
    $xtpl->assign('MESSAGE', $reponse['message']);
    $xtpl->assign('SELECTED_FOLDER_PATH', $selected_folder_path);
    $xtpl->assign('url_view', $view_url);
    $xtpl->assign('TREE_HTML', renderFolderTree($folder_tree));

    if ($reponse['message']) {
        $xtpl->assign('MESSAGE_CLASS', ($reponse['status'] == 'success') ? 'alert-success' : 'alert-danger');
        $xtpl->parse('main.message');
    }

    $xtpl->assign('url_copy', $base_url . '&copy=1');
    $xtpl->assign('url_move', $base_url . '&move=1');
    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_compress($row, $list, $reponse, $tree_html, $current_permission, $can_unzip)
{
    global $module_file, $global_config, $lang_module;
    $xtpl = new XTemplate('compress.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_NAME', $row['file_name']);

    if ($reponse['message']) {
        $xtpl->assign('MESSAGE_CLASS', ($reponse['status'] == 'success') ? 'alert-success' : 'alert-danger');
        $xtpl->assign('MESSAGE', $reponse['message']);
        $xtpl->parse('main.message');
    }

    if (!empty($list)) {
        $xtpl->assign('TREE_HTML', $tree_html);
        $xtpl->parse('main.tree_html');
    } else {
        $xtpl->parse('main.no_content');
    }

    if ($can_unzip && (defined('NV_IS_SPADMIN') || $current_permission == 3)) {
        $xtpl->parse('main.can_unzip');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_edit_img($row, $file_type)
{
    global $module_file, $global_config, $lang_module;
    $xtpl = new XTemplate('edit_img.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $row);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);
    if ($file_type['is_audio']) {
        $xtpl->parse('main.audio');
    } elseif ($file_type['is_video']) {
        $xtpl->parse('main.video');
    } elseif ($file_type['is_image']) {
        $xtpl->parse('main.img');
    } elseif ($file_type['is_powerpoint']) {
        $xtpl->parse('main.powerpoint');
    }
    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_edit($file_content, $file_id, $file_name, $view_url, $reponse, $current_permission)
{
    global $module_file, $global_config, $lang_module, $file_types, $allowed_extensions;

    $xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', htmlspecialchars($file_content));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('url_view', $view_url);

    $can_edit = ($current_permission >= 3 || defined('NV_IS_SPADMIN'));
    $xtpl->assign('DISABLE_CLASS', $can_edit ? '' : 'readonly-editor');
    $xtpl->assign('DISABLE_ATTR', $can_edit ? '' : 'readonly');
    $xtpl->assign('READONLY', $can_edit ? 'false' : 'true');

    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

    if ($can_edit && in_array($file_extension, $allowed_extensions)) {
        $xtpl->parse('main.can_save');
    } else {
        $xtpl->parse('main.cannt_save');
    }

    if (!empty($reponse['message'])) {
        $xtpl->assign('MESSAGE_CLASS', ($reponse['status'] == 'success') ? 'alert-success' : 'alert-danger');
        $xtpl->assign('MESSAGE', $reponse['message']);
        $xtpl->parse('main.message');
    }

    foreach ($file_types as $type => $extensions) {
        if (in_array($file_extension, $extensions)) {
            $xtpl->assign($type, '');
            $xtpl->parse('main.' . $type);
            break;
        }
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}

function nv_fileserver_perm($row, $perm, $reponse, $back_url)
{
    global $module_file, $global_config, $lang_module;
    $xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);
    if ($back_url) {
        $xtpl->assign('BACK_URL', $back_url);
        $xtpl->parse('main.back');
    }
    $xtpl->assign('GROUP_LEVEL_1', $perm['p_group'] == 1 ? 'selected' : '');
    $xtpl->assign('GROUP_LEVEL_2', $perm['p_group'] == 2 ? 'selected' : '');
    $xtpl->assign('GROUP_LEVEL_3', $perm['p_group'] == 3 ? 'selected' : '');
    $xtpl->assign('OTHER_LEVEL_1', $perm['p_other'] == 1 ? 'selected' : '');
    $xtpl->assign('OTHER_LEVEL_2', $perm['p_other'] == 2 ? 'selected' : '');
    if ($reponse['status']) {
        $xtpl->assign('MESSAGE_CLASS', $reponse['status'] == 'success' ? 'alert-success' : 'alert-danger');
        $xtpl->assign('MESSAGE', $reponse['message']);
        $xtpl->parse('main.message');
    }
    $xtpl->parse('main');
    return $xtpl->text('main');
}
