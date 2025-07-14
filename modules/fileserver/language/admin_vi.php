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
$lang_translator['createdate'] = '04/03/2010, 15:22';
$lang_translator['copyright'] = '@Copyright (C) 2009-2021 VINADES.,JSC. All rights reserved';
$lang_translator['info'] = '';
$lang_translator['langtype'] = 'lang_module';

$lang_module['total_file'] = 'Tổng số file & folder';
$lang_module['api_fileserver'] = 'API File Server';
$lang_module['api_fileserver_GetFile'] = 'Lấy thông tin file (GetFile)';
$lang_module['api_fileserver_DeleteFile'] = 'Xóa file (DeleteFile)';
$lang_module['api_fileserver_AddFile'] = 'Thêm file (AddFile)';
$lang_module['api_fileserver_UpdateFile'] = 'Cập nhật file (UpdateFile)';
$lang_module['api_fileserver_UploadFile'] = 'Upload file (UploadFile)';
$lang_module['export'] = 'Export';
$lang_module['import'] = 'Import';
$lang_module['recycle_bin'] = 'Thùng rác';
$lang_module['config'] = 'Cấu hình';
$lang_module['error_file_type'] = 'File không đúng định dạng';
$lang_module['import_success'] = 'Import dữ liệu thành công';
$lang_module['error_file_not_found'] = 'File không tồn tại';
$lang_module['main_title'] = 'Chọn nhóm người được phép truy cập';
$lang_module['group_user'] = 'Nhóm người dùng';
$lang_module['choose_group'] = 'Chọn nhóm người dùng';
$lang_module['submit'] = 'Xác nhận';
$lang_module['error'] = 'Lỗi';
$lang_module['success'] = 'Thành công';
$lang_module['stt'] = 'STT';
$lang_module['file_name'] = 'Tên file';
$lang_module['file_size'] = 'Kích thước';
$lang_module['file_type'] = 'Loại file';
$lang_module['file_path'] = 'Đường dẫn';
$lang_module['file_path_original'] = 'Đường dẫn ban đầu';
$lang_module['deleted_at'] = 'Ngày xóa';
$lang_module['created_at'] = 'Ngày tạo';
$lang_module['option'] = 'Tùy chọn';
$lang_module['export_title'] = 'Xuất dữ liệu hệ thống';
$lang_module['list_items_root'] = 'Danh sách file/folder gốc';
$lang_module['export_file'] = 'Xuất file';
$lang_module['import_file'] = 'Nhập dữ liệu vào hệ thống';
$lang_module['choose_file'] = 'Chọn file dữ liệu';
$lang_module['caution'] = '📌 <strong>Lưu ý khi nhập dữ liệu:</strong><br>
- Chỉ hỗ trợ <strong>tập tin Excel (.xlsx)</strong> đúng theo cấu trúc mẫu bên dưới.<br>
- File Excel cần chứa:<br>
&nbsp;&nbsp;• <strong>Tên tệp/thư mục</strong><br>
&nbsp;&nbsp;• <strong>Đường dẫn tới file trên Google Drive </strong> (Cần phân quyền file ở chế độ <em>public</em>)<br>
- Hệ thống sẽ đọc các đường dẫn này và xử lý download về website tự động.';
$lang_module['demo_title'] = '📥 File mẫu:';
$lang_module['demo_file'] = 'import_file.xlsx';
$lang_module['update_success'] = 'Cập nhật thành công';
$lang_module['update_error'] = 'Cập nhật thất bại';
$lang_module['no_group'] = 'Chưa chọn nhóm nào';
$lang_module['restore_ok'] = 'Khôi phục thành công';
$lang_module['restore_false'] = 'Khôi phục thất bại';
$lang_module['choose_file_0'] = 'Chưa chọn file';
$lang_module['delete_ok'] = 'Xóa thành công';
$lang_module['delete_false'] = 'Xóa thất bại';
$lang_module['checksess_false'] = 'Thông tin không hợp lệ hoặc phiên làm việc không đúng';
$lang_module['file_id_false'] = 'ID file không hợp lệ';
$lang_module['action_invalid'] = 'Hành động không hợp lệ';
$lang_module['recycle_bin'] = 'Thùng rác';
$lang_module['list_item_delete'] = 'Danh sách file/folder đã xóa';
$lang_module['no_data'] = 'Không có dữ liệu';
$lang_module['all'] = 'Tất cả';
$lang_module['file'] = 'Tệp tin';
$lang_module['folder'] = 'Thư mục';
$lang_module['search'] = 'Tìm kiếm';
$lang_module['restore'] = 'Khôi phục';
$lang_module['config_elastic'] = 'Cấu hình ElasticSearch';
$lang_module['elas_host'] = 'Địa chỉ elastic host';
$lang_module['elas_port'] = 'Cổng elastic';
$lang_module['elas_user'] = 'Tài khoản elastic';
$lang_module['elas_pass'] = 'Mật khẩu elastic';
$lang_module['save'] = 'Lưu';
$lang_module['config_updated'] = 'Cập nhật cấu hình thành công';
$lang_module['config_failed'] = 'Cập nhật cấu hình thất bại';
$lang_module['use_elastic'] = 'Sử dụng elastic';
$lang_module['sys_err'] = 'Lỗi hệ thống';
$lang_module['blank_list'] = 'Danh sách file trống, không thể xuất Excel.';
$lang_module['sync_elastic'] = 'Đồng bộ dữ liệu';
$lang_module['sync_elastic_desc'] = 'Đồng bộ dữ liệu từ hệ thống (từ cơ sở dữ liệu) vào ElasticSearch';
$lang_module['error_sync_elastic'] = 'Lỗi đồng bộ dữ liệu';
$lang_module['sync_elastic_success'] = 'Đồng bộ dữ liệu thành công, đã cập nhật %d bản ghi vào ElasticSearch';
$lang_module['sync_elastic_failed'] = 'Đồng bộ dữ liệu thất bại';
$lang_module['confirm_sync_elastic'] = 'Bạn có chắc chắn muốn đồng bộ dữ liệu với ElasticSearch không?';
$lang_module['use_captcha'] = 'Sử dụng Captcha';
$lang_module['enable_captcha'] = 'Bật Captcha';
$lang_module['elastic_not_enabled'] = 'Elasticsearch chưa được bật. Vui lòng bật và cấu hình trước khi đồng bộ.';
$lang_module['elastic_config_incomplete'] = 'Cấu hình Elasticsearch chưa đầy đủ. Vui lòng điền đầy đủ thông tin.';
$lang_module['update_perm_error'] = 'Cập nhật quyền truy cập thất bại';
$lang_module['update_alias_error'] = 'Cập nhật alias thất bại';
$lang_module['update_log_false'] = 'Cập nhật nhật ký thất bại';
$lang_module['update_config_false'] = 'Cập nhật cấu hình thất bại';
$lang_module['active'] = 'Hoạt động';
$lang_module['deactive'] = 'Ngừng hoạt động';
$lang_module['supported_file_types'] = 'Các định dạng file được hỗ trợ: xlsx, xls';
$lang_module['elastic_connection_error'] = 'Lỗi kết nối tới Elasticsearch';