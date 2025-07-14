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

$lang_translator['author'] = 'VINADES.,JSC <contact@vinades.vn>';
$lang_translator['createdate'] = 'March 4, 2010, 15:22';
$lang_translator['copyright'] = '@Copyright (C) 2009-2021 VINADES.,JSC. All rights reserved';
$lang_translator['info'] = '';
$lang_translator['langtype'] = 'lang_module';

$lang_module['total_file'] = 'Total number of files & folders';
$lang_module['api_fileserver'] = 'API File Server';
$lang_module['api_fileserver_GetFile'] = 'Get file information (GetFile)';
$lang_module['api_fileserver_DeleteFile'] = 'Delete file (DeleteFile)';
$lang_module['api_fileserver_AddFile'] = 'Add file (AddFile)';
$lang_module['api_fileserver_UpdateFile'] = 'Update File (UpdateFile)';
$lang_module['api_fileserver_UploadFile'] = 'Upload File (UploadFile)';
$lang_module['export'] = 'Export';
$lang_module['import'] = 'Import';
$lang_module['recycle_bin'] = 'Recycle Bin';
$lang_module['config'] = 'Configuration';
$lang_module['error_file_type'] = 'Invalid file format';
$lang_module['import_success'] = 'Import data successfully';
$lang_module['error_file_not_found'] = 'File does not exist';
$lang_module['main_title'] = 'Select the group of people allowed to access';
$lang_module['group_user'] = 'User group';
$lang_module['choose_group'] = 'Choose user group';
$lang_module['submit'] = 'Confirm';
$lang_module['error'] = 'Error';
$lang_module['success'] = 'Success';
$lang_module['stt'] = 'STT';
$lang_module['file_name'] = 'File name';
$lang_module['file_size'] = 'Size';
$lang_module['file_type'] = 'File type';
$lang_module['file_path'] = 'Path';
$lang_module['file_path_original'] = 'Original path';
$lang_module['deleted_at'] = 'Date deleted';
$lang_module['created_at'] = 'Date created';
$lang_module['option'] = 'Options';
$lang_module['export_title'] = 'Export system data';
$lang_module['list_items_root'] = 'List of root files/folders';
$lang_module['export_file'] = 'Export file';
$lang_module['import_file'] = 'Import data into the system';
$lang_module['choose_file'] = 'Choose data file';
$lang_module['caution'] = '📌 <strong>Note when importing data:</strong><br>
- Only support <strong>Excel files (.xlsx)</strong> according to the sample structure below.<br>
- The Excel file must contain:<br>
&nbsp;&nbsp;• <strong>File/folder name</strong><br>
&nbsp;&nbsp;• <strong>Path to the file on Google Drive</strong> (File permissions must be set to <em>public</em>)<br>
- The system will read these paths and automatically download them to the website.';
$lang_module['demo_title'] = '📥 Sample file:';
$lang_module['demo_file'] = 'import_file.xlsx';
$lang_module['update_success'] = 'Updated successfully';
$lang_module['update_error'] = 'Update failed';
$lang_module['no_group'] = 'No group selected';
$lang_module['restore_ok'] = 'Restore successful';
$lang_module['restore_false'] = 'Restore failed';
$lang_module['choose_file_0'] = 'No file selected';
$lang_module['delete_ok'] = 'Delete successful';
$lang_module['delete_false'] = 'Delete failed';
$lang_module['checksess_false'] = 'Invalid information or session';
$lang_module['file_id_false'] = 'Invalid file ID';
$lang_module['action_invalid'] = 'Invalid action';
$lang_module['recycle_bin'] = 'Recycle Bin';
$lang_module['list_item_delete'] = 'List of deleted files/folders';
$lang_module['no_data'] = 'No data';
$lang_module['all'] = 'All';
$lang_module['file'] = 'Files';
$lang_module['folder'] = 'Folder';
$lang_module['search'] = 'Search';
$lang_module['restore'] = 'Restore';
$lang_module['config_elastic'] = 'ElasticSearch Configuration';
$lang_module['elas_host'] = 'Elastic host address';
$lang_module['elas_port'] = 'Elastic port';
$lang_module['elas_user'] = 'Elastic account';
$lang_module['elas_pass'] = 'Elastic password';
$lang_module['save'] = 'Save';
$lang_module['config_updated'] = 'Configuration update successful';
$lang_module['config_failed'] = 'Configuration update failed';
$lang_module['use_elastic'] = 'Using elastic';
$lang_module['sys_err'] = 'System error';
$lang_module['blank_list'] = 'File list is empty, cannot export to Excel.';
$lang_module['sync_elastic'] = 'Synchronize data';
$lang_module['sync_elastic_desc'] = 'Sync data from system (from database) to ElasticSearch';
$lang_module['error_sync_elastic'] = 'Error syncing data';
$lang_module['sync_elastic_success'] = 'Sync data successfully, updated %d records to ElasticSearch';
$lang_module['sync_elastic_failed'] = 'Sync data failed';
$lang_module['confirm_sync_elastic'] = 'Are you sure you want to sync data to ElasticSearch?';
$lang_module['use_captcha'] = 'Use Captcha';
$lang_module['enable_captcha'] = 'Enable Captcha';
$lang_module['elastic_not_enabled'] = 'Elasticsearch is not enabled. Please enable and configure before syncing.';
$lang_module['elastic_config_incomplete'] = 'Elasticsearch configuration is incomplete. Please fill in all the information.';
$lang_module['update_perm_error'] = 'Access permission update failed';
$lang_module['update_alias_error'] = 'Alias update failed';
$lang_module['update_log_false'] = 'Log update failed';
$lang_module['update_config_false'] = 'Configuration update failed';
$lang_module['active'] = 'Active';
$lang_module['deactive'] = 'Deactivated';
$lang_module['supported_file_types'] = 'Supported file formats: xlsx, xls';
$lang_module['elastic_connection_error'] = 'Error connecting to Elasticsearch';