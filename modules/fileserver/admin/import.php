<?php
if (!defined('NV_IS_FILE_ADMIN')) {
    die('Stop!!!');
}

$error = '';
$success = '';

$apikey = 'r2GE8fx40FxtVjXU6b9ONBKpS12u40dC';
$apisecret = 'g6FEqSZL0Ggyt87om7tiM694oxqEOs5w';
$apiurl = 'http://demo.my/api.php'; // Thay bằng URL thực tế của bạn
date_default_timezone_set('Asia/Ho_Chi_Minh');

if ($nv_Request->isset_request('submit_upload', 'post') && isset($_FILES['uploadfile']) && is_uploaded_file($_FILES['uploadfile']['tmp_name'])) {
    $file_extension = strtolower(pathinfo($_FILES['uploadfile']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, ['xlsx', 'xls'])) {
        $error = $lang_module['error_file_type'] ?? 'Chỉ hỗ trợ file Excel (.xlsx, .xls)';
    } else {
        $timestamp = time();
        $agent = 'NukeViet Remote API Lib';

        $request = [
            'apikey' => $apikey,
            'hashsecret' => password_hash($apisecret . '_' . $timestamp, PASSWORD_DEFAULT),
            'language' => 'vi',
            'module' => 'fileserver',
            'action' => 'UploadFile',
            'file' => new CURLFile($_FILES['uploadfile']['tmp_name'], mime_content_type($_FILES['uploadfile']['tmp_name']), $_FILES['uploadfile']['name']),
            'lev' => 0,
            'timestamp' => $timestamp
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiurl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $safe_mode = (ini_get('safe_mode') == '1' || strtolower(ini_get('safe_mode')) == 'on') ? 1 : 0;
        $open_basedir = ini_get('open_basedir') ? true : false;
        if (!$safe_mode && !$open_basedir) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


        $res = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            $error = "Lỗi khi gọi API: " . $curl_error;
        } else {
            $response = json_decode($res, true);
            if ($response && isset($response['status'])) {
                if ($response['status'] === 'success') {
                    $success = $response['message'] ?? 'Import dữ liệu từ file Excel thành công.';
                } else {
                    $error = $response['message'] ?? 'Lỗi không xác định từ API.';
                }
            } else {
                $error = "Không thể phân tích phản hồi từ API.";
            }
        }
    }
}

$download = $nv_Request->get_int('download', 'get', 0);
if ($download == 1) {
    $file_path = NV_ROOTDIR . '/themes/default/images/fileserver/import_file.xlsx';
    if (file_exists($file_path)) {
        $download = new NukeViet\Files\Download($file_path, NV_ROOTDIR . '/themes/default/images/fileserver/', 'import_file.xlsx', true, 0);
        $download->download_file();
        exit();
    } else {
        $error = $lang_module['error_file_not_found'] ?? 'File mẫu không tồn tại';
    }
}

$xtpl = new XTemplate('import.tpl', NV_ROOTDIR . '/themes/' . $global_config['module_theme'] . '/modules/' . $module_file);
$xtpl->assign('LANG', $lang_module);
$xtpl->assign('OP', $op);
$xtpl->assign('MODULE_NAME', $module_name);
$xtpl->assign('FORM_ACTION', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=' . $op);
$xtpl->assign('URL_DOWNLOAD', NV_BASE_ADMINURL . 'index.php?' . NV_LANG_VARIABLE . '=' . NV_LANG_DATA . '&' . NV_NAME_VARIABLE . '=' . $module_name . '&' . NV_OP_VARIABLE . '=import&download=1');

if (!empty($error)) {
    $xtpl->assign('ERROR', $error);
    $xtpl->parse('main.error');
}

if (!empty($success)) {
    $xtpl->assign('SUCCESS', $success);
    $xtpl->parse('main.success');
}

$xtpl->parse('main');
$contents = $xtpl->text('main');

include NV_ROOTDIR . '/includes/header.php';
echo nv_admin_theme($contents);
include NV_ROOTDIR . '/includes/footer.php';