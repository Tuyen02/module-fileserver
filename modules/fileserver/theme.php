<?php

if (!defined('NV_IS_MOD_FILESERVER')) {
    exit('Stop!!!');
}

function nv_fileserver_main($result, $page_url, $error, $success, $permissions, $selected, $base_url, $lev, $search_term, $logs, $back_url, $generate_page, $tree_html)
{
    global $module_file, $global_config, $lang_module, $module_name, $module_config, $lang_global, $editable_extensions, $viewable_extensions, $op, $user_info, $allowed_create_extensions;

    $xtpl = new XTemplate('main.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FORM_ACTION', $base_url);
    $xtpl->assign('PAGE_URL', $page_url);
    $xtpl->assign('SEARCH_TERM', $search_term);
    $xtpl->assign('SELECTED_ALL', $selected['all']);
    $xtpl->assign('SELECTED_FILE', $selected['file']);
    $xtpl->assign('SELECTED_FOLDER', $selected['folder']);
    $xtpl->assign('GENERATE_PAGE', $generate_page);
    $xtpl->assign('TREE', $tree_html);

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

    if (!empty($result)) {
        foreach ($result as $row) {
            $row['total_size'] = isset($logs[$row['lev']]['total_size']) ? nv_convertfromBytes($logs[$row['lev']]['total_size']) : '0 B';
            $row['total_files'] = isset($logs[$row['lev']]['total_files']) ? $logs[$row['lev']]['total_files'] : 0;
            $row['total_folders'] = isset($logs[$row['lev']]['total_folders']) ? $logs[$row['lev']]['total_folders'] : 0;
            $row['created_at'] = date('d/m/Y H:i:s', $row['created_at']);
            $row['checksess'] = md5($row['file_id'] . NV_CHECK_SESSION);
            $row['icon_class'] = getFileIconClass($row);

            if ($permissions) {
                $row['p_group'] = isset($permissions[$row['file_id']]['p_group']) ? $permissions[$row['file_id']]['p_group'] : 1;
                $row['p_other'] = isset($permissions[$row['file_id']]['p_other']) ? $permissions[$row['file_id']]['p_other'] : 1;
            }

            $row['url'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '/' . $row['alias'];
            $row['url_perm'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=perm/' . $row['alias'];
            $row['url_edit'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit/' . $row['alias'];
            $row['url_view'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=view/' . $row['alias'];
            $row['url_edit_img'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=edit_img/' . $row['alias'];
            $row['url_delete'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&amp;file_id=' . $row['file_id'] . '&action=delete&checksess=' . md5($row['file_id'] . NV_CHECK_SESSION);
            $row['url_download'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=' . $op . '&amp;file_id=' . $row['file_id'] . '&download=1&token=' . md5($row['file_id'] . NV_CHECK_SESSION . $global_config['sitekey']);
            $row['url_clone'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=clone/' . $row['alias'];
            $row['url_rename'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=rename/' . $row['alias'];
            $row['url_compress'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=compress/' . $row['alias'];
            $row['url_share'] = NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name . '&amp;' . NV_OP_VARIABLE . '=share/' . $row['alias'];

            $current_permission = get_user_permission($row['file_id'], isset($user_info['userid']) ? $user_info['userid'] : 0);
            $row['file_size'] = nv_convertfromBytes($row['file_size']);
            $xtpl->assign('ROW', $row);

            $fileInfo = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
            $xtpl->assign('DOWNLOAD', $row['url_download']);
            $xtpl->parse('main.has_data_content.file_row.download');
            $view_href = $row['url'];

            if (!$row['is_folder']) {
                if ($fileInfo === 'txt') {
                    $view_href = $row['url_edit'];
                } elseif (in_array($fileInfo, $editable_extensions)) {
                    $view_href = $row['url_view'];
                } else {
                    $view_href = $row['url'];
                }
            }
            $preview_link_attributes = '';

            $is_zip_uncompressed = (strtolower($fileInfo) == 'zip' && empty($row['compressed']));

            if (defined('NV_IS_SPADMIN') || $current_permission == 3) {
                $xtpl->parse('main.has_data_content.file_row.delete');
                $xtpl->parse('main.has_data_content.file_row.rename');
                if (defined('NV_IS_SPADMIN'))
                    $xtpl->parse('main.has_data_content.file_row.share');
                if (!$row['is_folder']) {
                    if (in_array($fileInfo, $editable_extensions)) {
                        $view_href = $row['url_view']; 
                    }

                    if (in_array($fileInfo, $allowed_create_extensions)) {
                        $xtpl->assign('EDIT', $row['url_edit']);
                        $xtpl->parse('main.has_data_content.file_row.edit');
                        $view_href = $row['url_edit']; 
                    }

                    if ($row['compressed'] || $is_zip_uncompressed) {
                        $xtpl->assign('VIEW', $row['url_compress']);
                        $view_href = $row['url_compress'];
                        $xtpl->parse('main.has_data_content.file_row.compress_btn');
                    }
                    
                    if (in_array($fileInfo, $viewable_extensions)) {
                        $file_type = '';
                        if (in_array(strtolower($fileInfo), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                            $file_type = 'img';
                        } elseif (in_array(strtolower($fileInfo), ['mp4', 'webm', 'ogg'])) {
                            $file_type = 'video';
                        } elseif (in_array(strtolower($fileInfo), ['mp3', 'wav', 'ogg'])) {
                            $file_type = 'audio';
                        } elseif (in_array(strtolower($fileInfo), ['ppt', 'pptx'])) {
                            $file_type = 'powerpoint';
                        }
                        
                        if ($file_type) {
                            $xtpl->assign('FILE_TYPE', $file_type);
                            $xtpl->assign('FILE_PATH', NV_MY_DOMAIN . NV_BASE_SITEURL . ltrim($row['file_path'], '/'));
                            $xtpl->parse('main.has_data_content.file_row.preview');
                            $view_href = 'javascript:void(0);';
                            $preview_link_attributes = 'onclick="togglePreview(event, this)" data-filetype="' . $file_type . '" data-filepath="' . NV_MY_DOMAIN . NV_BASE_SITEURL . ltrim($row['file_path'], '/') . '"';
                        }
                    }
                    $xtpl->assign('COPY', $row['url_clone']);
                    $xtpl->parse('main.has_data_content.file_row.copy');
                } 
            } elseif ($current_permission == 2) {
                if (!$row['is_folder']) {
                    if (in_array($fileInfo, $editable_extensions)) {
                        $view_href = $row['url_view'];
                    } elseif(in_array($fileInfo, $allowed_create_extensions)){
                        $view_href = $row['url_view'];
                    } elseif (in_array($fileInfo, $viewable_extensions)) {
                        $file_type = '';
                        if (in_array(strtolower($fileInfo), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                            $file_type = 'img';
                        } elseif (in_array(strtolower($fileInfo), ['mp4', 'webm', 'ogg'])) {
                            $file_type = 'video';
                        } elseif (in_array(strtolower($fileInfo), ['mp3', 'wav', 'ogg'])) {
                            $file_type = 'audio';
                        } elseif (in_array(strtolower($fileInfo), ['ppt', 'pptx'])) {
                            $file_type = 'powerpoint';
                        }
                        if ($file_type) {
                            $xtpl->assign('FILE_TYPE', $file_type);
                            $xtpl->assign('FILE_PATH', NV_MY_DOMAIN . NV_BASE_SITEURL . ltrim($row['file_path'], '/'));
                            $xtpl->parse('main.has_data_content.file_row.preview');
                            $view_href = 'javascript:void(0);';
                            $preview_link_attributes = 'onclick="togglePreview(event, this)" data-filetype="' . $file_type . '" data-filepath="' . NV_MY_DOMAIN . NV_BASE_SITEURL . ltrim($row['file_path'], '/') . '"';
                        }
                    } else {
                        $view_href = $row['url'];
                    }
                    if ($row['compressed'] || $is_zip_uncompressed) {
                        $xtpl->assign('VIEW', $row['url_compress']);
                        $view_href = $row['url_compress'];
                        $xtpl->parse('main.has_data_content.file_row.compress_btn');
                    }
                }
            }

            $xtpl->assign('VIEW', $view_href);
            $xtpl->assign('PREVIEW_LINK_ATTRIBUTES', $preview_link_attributes);
            $xtpl->parse('main.has_data_content.file_row.view');
            $xtpl->parse('main.has_data_content.file_row');
        }
        if (defined('NV_IS_SPADMIN')) {
            $xtpl->parse('main.has_data_content.stats');
        }
        if ($show_create_buttons) {
            $xtpl->parse('main.has_data_content.can_compress');
            $xtpl->parse('main.has_data_content.can_delete_all');
        }
        $xtpl->parse('main.has_data_content');
    } else {
        $xtpl->parse('main.no_search_result');
    }

    if (!empty($module_config[$module_name]['captcha_type'])) {
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

function nv_fileserver_clone($row, $selected_folder_path, $view_url, $folder_tree, $base_url)
{
    global $module_file, $global_config, $lang_module;
    $xtpl = new XTemplate('clone.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_ID', $row['file_id']);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);
    $xtpl->assign('SELECTED_FOLDER_PATH', $selected_folder_path);
    $xtpl->assign('url_view', $view_url);
    $xtpl->assign('TREE_HTML', renderFolderTree($folder_tree));
    $xtpl->assign('BASE_URL', $base_url);

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

    if (!empty($tree_html)) {
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
function nv_fileserver_edit($row, $file_content, $file_id, $file_name, $view_url, $reponse, $current_permission, $back_url)
{
    global $module_file, $global_config, $lang_module, $allowed_create_extensions, $module_name, $op;

    $xtpl = new XTemplate('edit.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', $file_content);
    $xtpl->assign('FILE_URL',  NV_MY_DOMAIN . NV_BASE_SITEURL . ltrim($row['file_path'], '/'));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('url_view', $view_url);
    $xtpl->assign('BACK_URL', $back_url);
    $xtpl->assign('BASE_URL', NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name);
    $xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
    $xtpl->assign('OP', $op);
    $xtpl->assign('TOKEN', md5($file_id . NV_CHECK_SESSION . $global_config['sitekey']));

    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $can_edit = ($current_permission >= 3 || defined('NV_IS_SPADMIN')) && 
                (in_array($file_extension, $allowed_create_extensions));

    $xtpl->assign('DISABLE_CLASS', $can_edit ? '' : 'readonly-editor');
    $xtpl->assign('DISABLE_ATTR', $can_edit ? '' : 'readonly');
    $xtpl->assign('READONLY', $can_edit ? 'false' : 'true');

    if ($can_edit) {
        $xtpl->parse('main.can_save');
    } else {
        $xtpl->parse('main.cannt_save');
    }

    if (!empty($reponse['message'])) {
        $xtpl->assign('MESSAGE_CLASS', ($reponse['status'] == 'success') ? 'alert-success' : 'alert-danger');
        $xtpl->assign('MESSAGE', $reponse['message']);
        $xtpl->parse('main.message');
    }

    $xtpl->parse('main.text');

    if (!empty($back_url)) {
        $xtpl->parse('main.back');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}
function nv_fileserver_view($row, $file_content, $file_id, $file_name, $view_url, $reponse, $current_permission, $back_url)
{
    global $module_file, $global_config, $lang_module, $module_name, $op;

    $xtpl = new XTemplate('view.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_CONTENT', $file_content);
    $xtpl->assign('FILE_URL',  NV_MY_DOMAIN . NV_BASE_SITEURL . ltrim($row['file_path'], '/'));
    $xtpl->assign('FILE_ID', $file_id);
    $xtpl->assign('FILE_NAME', $file_name);
    $xtpl->assign('url_view', $view_url);
    $xtpl->assign('BACK_URL', $back_url);
    $xtpl->assign('BASE_URL', NV_BASE_SITEURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&amp;' . NV_NAME_VARIABLE . '=' . $module_name);
    $xtpl->assign('NV_OP_VARIABLE', NV_OP_VARIABLE);
    $xtpl->assign('OP', $op);
    $xtpl->assign('TOKEN', md5($file_id . NV_CHECK_SESSION . $global_config['sitekey']));

    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $can_edit = ($current_permission >= 3 || defined('NV_IS_SPADMIN'));

    $xtpl->assign('DISABLE_CLASS', $can_edit ? '' : 'readonly-editor');
    $xtpl->assign('DISABLE_ATTR', $can_edit ? '' : 'readonly');
    $xtpl->assign('READONLY', $can_edit ? 'false' : 'true');

    if ($can_edit == false) {
        $xtpl->parse('main.cannt_save');
    }

    if (!empty($reponse['message'])) {
        $xtpl->assign('MESSAGE_CLASS', ($reponse['status'] == 'success') ? 'alert-success' : 'alert-danger');
        $xtpl->assign('MESSAGE', $reponse['message']);
        $xtpl->parse('main.message');
    }

    if ($file_extension == 'pdf') {
        $xtpl->parse('main.pdf');
    } elseif (in_array($file_extension, ['doc', 'docx'])) {
        $xtpl->parse('main.docx');
    } elseif (in_array($file_extension, ['xls', 'xlsx'])) {
        $xtpl->parse('main.xlsx');
    } else {
        $xtpl->parse('main.text');
    }

    if (!empty($back_url)) {
        $xtpl->parse('main.back');
    }

    $xtpl->parse('main');
    return $xtpl->text('main');
}
function nv_fileserver_perm($row, $perm, $reponse, $group_list)
{
    global $module_file, $global_config, $lang_module;
    $xtpl = new XTemplate('perm.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
    $xtpl->assign('LANG', $lang_module);
    $xtpl->assign('FILE_NAME', $row['file_name']);
    $xtpl->assign('FILE_PATH', $row['file_path']);
    foreach ($group_list as $group) {
        $xtpl->assign('GROUP_ID', $group['group_id']);
        $xtpl->assign('GROUP_TITLE', $group['title']);
        $xtpl->parse('main.group');
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
