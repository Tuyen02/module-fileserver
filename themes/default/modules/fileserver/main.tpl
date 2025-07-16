<!-- BEGIN: main -->
<div class="container">
    <!-- BEGIN: breadcrumbs -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{BASE_URL}">{LANG.home}</a>
            </li>
            <!-- BEGIN: loop -->
            <li class="breadcrumb-item">
                <a href="{BREADCRUMB.link}">{BREADCRUMB.title}</a>
            </li>
            <!-- END: loop -->
        </ol>
    </nav>
    <!-- END: breadcrumbs -->

    <h1 class="text-center">{LANG.module_title}</h1>
    <br>
    <!-- BEGIN: error -->
    <div class="alert alert-danger">{ERROR}</div>
    <!-- END: error -->
    <!-- BEGIN: success -->
    <div class="alert alert-success">{SUCCESS}</div>
    <!-- END: success -->
    <div class="d-flex align-items-center flex-wrap flex-column flex-md-row w-100">
        <form action="" method="get" id="searchForm" class="form-inline my-2 my-lg-0 w-100 flex-wrap">
            <input type="hidden" name="lev" value="{ROW.lev}">
            <input type="text" class="form-control mb-2 mb-md-0 w-100 w-md-auto" placeholder="{LANG.search}"
                id="searchInput" name="search" value="{SEARCH_TERM}">
            <select class="form-control ml-0 ml-md-2 mb-2 mb-md-0 w-100 w-md-auto" name="search_type">
                <option value="all" {SELECTED_ALL}>{LANG.all}</option>
                <option value="file" {SELECTED_FILE}>{LANG.file}</option>
                <option value="folder" {SELECTED_FOLDER}>{LANG.folder}</option>
            </select>
            <button type="submit"
                class="btn btn-primary ml-0 ml-md-2 mb-2 mb-md-0 w-100 w-md-auto">{LANG.search_btn}</button>
        </form>
        <br>
        <form action="" method="post" enctype="multipart/form-data" id="uploadForm"
            class="form-inline my-2 my-lg-0 w-100 flex-wrap">
            <!-- BEGIN: can_create -->
            <a href="#" class="btn btn-primary mb-2 mb-md-0" data-toggle="modal"
                data-target="#createModal">{LANG.create_btn}</a>
            <button type="button" class="btn btn-success mb-2 mb-md-0 mr-2" id="uploadButton">{LANG.upload_btn}</button>
            <!-- END: can_create -->
            <!-- BEGIN: back -->
            <a href="{BACK_URL}" class="btn btn-info mb-2 mb-md-0 mr-2">
                <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
            </a>
            <!-- END: back -->
            <input type="file" name="uploadfile" id="uploadfile" required style="display: none;">
            <input type="hidden" name="lev" id="lev" value="{ROW.lev}">
            <input type="hidden" name="submit_upload" value="1">
        </form>
    </div>

    <hr>
    <div class="row">
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="tree">
                {TREE}
            </div>
        </div>
        <div class="col-md-18 col-lg-20">
            <!-- BEGIN: has_data_content -->
            <table class="table table-hover">
                <colgroup>
                    <col style="width: 3%;">
                    <col style="width: 40%;">
                    <col style="width: 10%;">
                    <col style="width: 15%;">
                    <col style="width: 32%;">
                </colgroup>
                <thead class="thead-dark">
                    <tr>
                        <th scope="col" style="text-align:center;"><input class="form-check-input" type="checkbox"
                                value="" id="defaultCheck1"></th>
                        <th scope="col" class="sortable" data-sort="file_name">{LANG.f_name} <i class="fa fa-sort"></i>
                        </th>
                        <th scope="col" class="sortable" data-sort="file_size">{LANG.f_size} <i class="fa fa-sort"></i>
                        </th>
                        <th scope="col" class="sortable" data-sort="created_at">{LANG.created_at} <i
                                class="fa fa-sort"></i></th>
                        <th scope="col">{LANG.option}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BEGIN: file_row -->
                    <tr>
                        <td>
                            <input type="checkbox" name="files[]" value="{ROW.file_id}"
                                data-checksess="{ROW.checksess}">
                        </td>
                        <td class="text-break" style="max-width:220px; word-break:break-all;">
                            <a href="{VIEW}" {PREVIEW_LINK_ATTRIBUTES}>
                                <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                                {ROW.file_name}
                            </a>
                        </td>
                        <td>{ROW.file_size}</td>
                        <td>{ROW.created_at}</td>
                        <td>
                            <!-- BEGIN: delete -->
                            <button class="btn btn-sm btn-danger delete function-btn" data-file-id="{ROW.file_id}"
                                data-checksess="{CHECK_SESS}" data-url="{ROW.url_delete}" title="{LANG.delete_btn}">
                                <i class="fa fa-trash-o"></i>
                            </button>
                            <!-- END: delete -->

                            <!-- BEGIN: rename -->
                            <button class="btn btn-sm btn-info rename function-btn" data-file-name="{ROW.file_name}"
                                data-file-id="{ROW.file_id}" data-toggle="modal" data-target="#renameModal"
                                title="{LANG.rename_btn}">
                                <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                            </button>
                            <!-- END: rename -->

                            <!-- BEGIN: share -->
                            <a href="{ROW.url_perm}" class="btn btn-sm btn-info share function-btn"
                                title="{LANG.perm_btn}">
                                <i class="fa fa-link"></i>
                            </a>
                            <!-- END: share -->

                            <!-- BEGIN: download -->
                            <a href="{DOWNLOAD}" class="btn btn-sm btn-success download function-btn"
                                data-file-id="{ROW.file_id}" title="{LANG.download_btn}">
                                <i class="fa fa-download" aria-hidden="true"></i>
                            </a>
                            <!-- END: download -->

                            <!-- BEGIN: edit -->
                            <a href="{EDIT}" class="btn btn-sm btn-info function-btn" title="{LANG.edit_btn}">
                                <i class="fa fa-pencil-square"></i>
                            </a>
                            <!-- END: edit -->

                            <!-- BEGIN: copy -->
                            <a href="{COPY}" class="btn btn-sm btn-info function-btn" title="{LANG.copy}">
                                <i class="fa fa-clone"></i>
                            </a>
                            <!-- END: copy -->
                        </td>
                    </tr>
                    <!-- END: file_row -->
                </tbody>
                <!-- BEGIN: stats -->
                <tfoot>
                    <tr>
                        <td class="gray" colspan="7">
                            <strong>{LANG.full_size}</strong>
                            <span class="badge badge-light">{ROW.total_size}</span>
                            <strong>- {LANG.file}:</strong>
                            <span class="badge badge-secondary">{ROW.total_files}</span>
                            <strong>- {LANG.folder}:</strong>
                            <span class="badge badge-secondary">{ROW.total_folders}</span>
                        </td>
                    </tr>
                </tfoot>
                <!-- END: stats -->
            </table>
            <!-- BEGIN: can_compress -->
            <a href="#" class="btn btn-primary" id="compressButton" data-toggle="modal" data-target="#compressModal">
                <i class="fa fa-file-archive-o" aria-hidden="true"></i> {LANG.zip_btn}
            </a>
            <!-- END: can_compress -->

            <!-- BEGIN: can_delete_all -->
            <button type="submit" name="deleteAll" class="btn btn-danger mt-2 deleteAll" id="deleteAll">
                <i class="fa fa-trash" aria-hidden="true"></i> {LANG.delete_btn}
            </button>
            <!-- END: can_delete_all -->
            <!-- BEGIN: generate_page -->
            <div class="text-center">{GENERATE_PAGE}</div>
            <!-- END: generate_page -->
            <!-- END: has_data_content -->
            <!-- BEGIN: no_search_result -->
            <div class="text-center">
                <p><i class="fa fa-info-circle"></i> {LANG.no_data}</p>
            </div>
            <!-- END: no_search_result -->
    </div>
    <br>

    <div class="modal fade" id="compressModal" tabindex="-1" role="dialog" aria-labelledby="compressModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="compressModalLabel">{LANG.compress_modal}</h3>
                </div>
                <div class="modal-body">
                    <form action="" id="compressForm" onsubmit="submitCompressForm(event);">
                        <div class="form-group">
                            <label for="zipFileName">{LANG.zip_file_name}</label>
                            <input type="text" class="form-control" id="zipFileName" name="zipFileName" required>
                            <div id="zipWarning" class="text-danger mt-1" style="display: none;"></div>
                            <div id="zipSuccess" class="text-success mt-1" style="display: none;"></div>
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
                        <button type="submit" class="btn btn-primary">{LANG.zip_btn}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header row">
                    <h3 class="modal-title col-lg-11" id="createModalLabel">{LANG.create_btn}</h3>
                </div>
                <div class="modal-body">
                    <form id="createForm" method="post" action="" onsubmit="submitCreateForm(event);" <!-- BEGIN:
                        recaptcha3 -->data-recaptcha3="1"<!-- END: recaptcha3 -->>
                        <div class="form-group">
                            <label for="type">{LANG.type}:</label>
                            <select class="form-control" id="type" name="type"">
                            <option value=" 1">{LANG.folder}</option>
                                <option value="0">{LANG.file}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="name_f">{LANG.f_name}:</label>
                            <input type="text" class="form-control" id="name_f" name="name_f" required>
                            <div id="fileNameWarning" class="text-danger mt-1" style="display: none;"></div>
                            <div id="fileNameSuccess" class="text-success mt-1" style="display: none;"></div>
                        </div>
                        <div class="alert alert-info mt-2">
                            <strong>{LANG.cautions}</strong>
                            <p><small class="form-text">
                                    {LANG.allowed_extensions}
                                </small></p>
                        </div>
                        <input type="hidden" name="action" value="create">
                        <!-- BEGIN: captcha -->
                        <div class="form-group">
                            <div class="middle text-right clearfix">
                                <img width="{GFX_WIDTH}" height="{GFX_HEIGHT}" title="{LANG.captcha}"
                                    alt="{LANG.captcha}"
                                    src="{NV_BASE_SITEURL}index.php?scaptcha=captcha&t={NV_CURRENTTIME}"
                                    class="captchaImg display-inline-block">
                                <em onclick="change_captcha('.fcode');" title="{GLANG.captcharefresh}"
                                    class="fa fa-pointer fa-refresh margin-left margin-right"></em>
                                <input type="text" placeholder="{LANG.captcha}" maxlength="{NV_GFX_NUM}" value=""
                                    name="fcode" class="fcode required form-control display-inline-block"
                                    style="width:100px;" data-pattern="/^(.){{NV_GFX_NUM},{NV_GFX_NUM}}$/"
                                    onkeypress="nv_validErrorHidden(this);" data-mess="{LANG.error_captcha}" />
                            </div>
                        </div>
                        <!-- END: captcha -->
                        <!-- BEGIN: recaptcha2 -->
                        <div class="form-group">
                            <div class="middle text-center clearfix">
                                <div class="g-recaptcha" data-sitekey="{GLOBAL_CONFIG.recaptcha_sitekey}"></div>
                            </div>
                        </div>
                        <!-- END: recaptcha2 -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{LANG.close_btn}</button>
                            <button type="submit" class="btn btn-primary">{LANG.create_btn}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- BEGIN: recaptcha2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <!-- END: recaptcha2 -->
    <!-- BEGIN: recaptcha3 -->
    <script src="https://www.google.com/recaptcha/api.js?render={GLOBAL_CONFIG.recaptcha_sitekey}" async defer></script>
    <!-- END: recaptcha3 -->


    <div class="modal fade" id="renameModal" tabindex="-1" role="dialog" aria-labelledby="renameModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header row">
                    <h3 class="modal-title col-lg-11" id="renameModalLabel">{LANG.rename_btn}</h3>
                </div>
                <div class="modal-body">
                    <form id="renameForm" method="post" action="" onsubmit="submitRenameForm(event);">
                        <div class="form-group">
                            <label for="new_name">{LANG.new_name}:</label>
                            <input type="text" class="form-control" id="new_name" name="new_name" required>
                            <div id="renameWarning" class="text-danger mt-1" style="display: none;"></div>
                            <div id="renameSuccess" class="text-success mt-1" style="display: none;"></div>
                        </div>
                        <input type="hidden" name="file_id" id="file_id" value="">
                        <input type="hidden" name="rename_action" value="rename">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.cancel_btn}</button>
                    <button type="button" class="btn btn-primary"
                        onclick="submitRenameForm();">{LANG.submit_btn}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="overflow: hidden;">
                    <h4 class="modal-title pull-left" style="margin-top: 0;">
                        {LANG.f_name}: <span id="previewFileName" class="text-break"></span>
                    </h4>
                    <button type="button" class="close pull-right" data-dismiss="modal" aria-label="{LANG.close_btn}"
                        style="font-size: 30px; line-height: 1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="previewModalBody">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
                </div>
            </div>
        </div>
    </div>

<style>
    .tree {
        list-style-type: none;
        padding: 0 20px 0 5px;
        margin: 0 5px 0 5px;
        border-right: 1px solid #ccc;
        overflow-x: auto;
        word-break: break-all;
        white-space: pre-line;
    }

    .tree li {
        margin: 5px 0;
        cursor: pointer;
    }

    .tree li a {
        color: inherit;
        text-decoration: none;
    }

    .tree li a:hover {
        color: #007bff;
    }

    .tree li.active {
        background-color: #f8f9fa;
        border-radius: 4px;
        padding: 2px 5px;
    }

    .tree li.active > a {
        color: #007bff;
        font-weight: bold;
    }

    .tree li span {
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .tree ul {
        margin-left: 5px;
        padding-left: 10px;
        border-left: 1px dashed #ccc;
    }

    .tree i {
        font-size: 14px;
    }

    #previewModalBody img,
    #previewModalBody video,
    #previewModalBody audio {
        max-width: 100%;
        height: auto;
        display: block; 
        margin: 0 auto; 
        max-height: 500px;
    }

    .sortable {
        cursor: pointer;
    }
    .sortable:hover {
        background-color: #f8f9fa;
    }
    .fa-sort-up, .fa-sort-down {
        margin-left: 5px;
    }
</style>

    <script>
        var USE_ELASTIC = {USE_ELASTIC};
        if (typeof USE_ELASTIC !== "undefined" && USE_ELASTIC == 1) {
            setInterval(function () {
                fetch('{NV_BASE_SITEURL}modules/fileserver/update_elastic.php', {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => response.text())
                    .then(data => { console.log('Elastic update:', data); })
                    .catch(err => { console.log('Elastic update error:', err); });
            }, 6000);
        }
        function submitCreateForm(event) {
            event.preventDefault();

            var name_f = $("#name_f").val();
            var type = $("#type").val();
            var extension = name_f.split('.').pop().toLowerCase();
            var fcode = $("#createForm input[name='fcode']").val() || '';
            var recaptchaResponse = '';

            if (type == '0' && (extension == '')) {
                alert('Tên file không hợp lệ. Vui lòng nhập tên file có đuôi hợp lệ.');
                return;
            }
            if (name_f.trim() == '') {
                alert('Tên file không được để trống.');
                return;
            }

            function sendRequest(token) {
                var data = {
                    'action': 'create',
                    'name_f': name_f,
                    'type': type,
                    'fcode': fcode,
                    'g-recaptcha-response': token
                };

                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: data,
                    dataType: 'json',
                    success: function (res) {
                        console.log('Create response:', res);
                        alert(res.message);
                        if (res.status == 'success' && res.redirect) {
                            window.location.reload();
                        } else if (res.refresh_captcha) {
                            if ($("#createForm").data('recaptcha3')) {
                                grecaptcha.ready(function () {
                                    grecaptcha.execute('{GLOBAL_CONFIG.recaptcha_sitekey}', { action: 'create' });
                                });
                            } else if ($(".g-recaptcha").length) {
                                grecaptcha.reset();
                            } else {
                                change_captcha('.fcode');
                            }
                        }
                    },
                    error: function (xhr) {
                        console.log('Create error:', xhr.responseText);
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Đã có lỗi xảy ra.');
                        if ($("#createForm").data('recaptcha3')) {
                            grecaptcha.ready(function () {
                                grecaptcha.execute('{GLOBAL_CONFIG.recaptcha_sitekey}', { action: 'create' });
                            });
                        } else if ($(".g-recaptcha").length) {
                            grecaptcha.reset();
                        } else {
                            change_captcha('.fcode');
                        }
                    }
                });
            }

            if (typeof grecaptcha !== 'undefined') {
                if ($("#createForm").data('recaptcha3')) {
                    grecaptcha.ready(function () {
                        grecaptcha.execute('{GLOBAL_CONFIG.recaptcha_sitekey}', { action: 'create' }).then(function (token) {
                            sendRequest(token);
                        });
                    });
                } else if ($(".g-recaptcha").length) {
                    recaptchaResponse = grecaptcha.getResponse();
                    if (!recaptchaResponse) {
                        alert('Vui lòng xác minh reCaptcha.');
                        return;
                    }
                    sendRequest(recaptchaResponse);
                } else {
                    sendRequest('');
                }
            } else {
                sendRequest('');
            }
        }

        $('#name_f').on('input', function () {
            var name_f = $(this).val();
            var type = $('#type').val();

            if (name_f) {
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: {
                        action: 'check_filename',
                        name_f: name_f,
                        type: type,
                        lev: $('#lev').val()
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status == 'error') {
                            $('#fileNameWarning').html(res.message).show();
                            $('#fileNameSuccess').hide();
                        } else {
                            $('#fileNameSuccess').html(res.message).show();
                            $('#fileNameWarning').hide();
                        }
                    },
                    error: function (xhr) {
                        console.log('Error checking filename:', xhr.responseText);
                        $('#fileNameWarning').text('Có lỗi xảy ra khi kiểm tra tên file').show();
                        $('#fileNameSuccess').hide();
                    }
                });
            } else {
                $('#fileNameWarning').hide();
                $('#fileNameSuccess').hide();
            }
        });

        $(document).on('click', '.delete', function () {
            const fileId = $(this).data('file-id');
            const deleteUrl = $(this).data('url');
            const checksess = $(this).data('checksess');

            if (confirm("Bạn có chắc chắn muốn xóa mục này?")) {
                handleDelete(fileId, deleteUrl, checksess);
            }
        });

        function handleDelete(fileId, deleteUrl, checksess) {
            const data = {
                action: "delete",
                file_id: fileId,
                checksess: checksess,
            };
            $.ajax({
                type: 'POST',
                url: deleteUrl,
                data: data,
                dataType: 'json',
                success: function (res) {
                    console.log('Delete response:', res);
                    alert(res.message);
                    if (res.status == 'success' && res.redirect) {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    console.log('Delete error:', xhr.responseText);
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Đã có lỗi xảy ra.');
                }
            });
        }

        function submitRenameForm() {
            const data = {
                action: 'rename',
                new_name: $("#new_name").val(),
                file_id: $("#file_id").val(),
            };
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: data,
                dataType: 'json',
                success: function (res) {
                    console.log('Rename response:', res);
                    alert(res.message);
                    if (res.status == 'success' && res.redirect) {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    console.log('Rename error:', xhr.responseText);
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Đã có lỗi xảy ra.');
                }
            });
        }

        $(document).on('click', '.rename', function () {
            const fileId = $(this).data('file-id');
            const fileName = $(this).data('file-name');
            const renameUrl = $(this).attr('href');

            $("#file_id").val(fileId);
            $("#new_name").val(fileName);

            $("#renameForm").attr("action", renameUrl);
        });

        $('#uploadForm').on('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: window.location.href,
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    console.log('Upload response:', res);
                    alert(res.message || 'Tải lên thành công.');
                    if (res.status == 'success' && res.redirect) {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    console.log('Upload error:', xhr.responseText);
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Đã có lỗi xảy ra.');
                }
            });
        });

        if (document.getElementById('uploadButton')) {
            document.getElementById('uploadButton').addEventListener('click', function () {
                document.getElementById('uploadfile').click();
            });
        }

        if (document.getElementById('uploadfile')) {
            document.getElementById('uploadfile').addEventListener('change', function () {
                document.getElementById('uploadForm').submit();
            });
        }

        function submitShareForm() {
            const data = {
                action: 'share',
                file_id: $("#share_file_id").val(),
                share_option: $("#share_option").val()
            };
            console.log("File ID being sent:", data.file_id);
            $.ajax({
                type: 'POST',
                url: "",
                data: data,
                success: function (res) {
                    console.log(res);
                    alert(res.message);
                    location.reload();
                },
                error: function () {
                    alert(res.message);
                }
            });
        }

        $(document).on('click', '.share', function () {
            const fileId = $(this).data('file-id');
            $("#share_file_id").val(fileId);
        });

        $(document).ready(function () {
            $('#compressButton').on('click', function (e) {
                e.preventDefault();

                const selectedFiles = [];
                document.querySelectorAll('input[name="files[]"]:checked').forEach(input => {
                    selectedFiles.push(input.value);
                });

                if (selectedFiles.length == 0) {
                    alert("Vui lòng chọn ít nhất một file để nén!");
                    return false;
                }

                console.log(selectedFiles);
            });

            let isZipNameValid = false;

            $('#zipFileName').on('input', function () {
                var zipFileName = $(this).val();
                const selectedFiles = [];
                document.querySelectorAll('input[name="files[]"]:checked').forEach(input => {
                    selectedFiles.push(input.value);
                });

                if (zipFileName) {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: {
                            action: 'check_zip_name',
                            zipFileName: zipFileName,
                            files: selectedFiles,
                            lev: $('#lev').val()
                        },
                        dataType: 'json',
                        success: function (res) {
                            if (res.status == 'error') {
                                $('#zipWarning').html(res.message).show();
                                $('#zipSuccess').hide();
                                isZipNameValid = false;
                            } else {
                                $('#zipSuccess').html(res.message).show();
                                $('#zipWarning').hide();
                                isZipNameValid = true;
                            }
                            $(this).data('lastResponse', res);
                        }.bind(this),
                        error: function (xhr) {
                            $('#zipWarning').text('Có lỗi xảy ra khi kiểm tra tên file zip').show();
                            $('#zipSuccess').hide();
                            isZipNameValid = false;
                        }
                    });
                } else {
                    $('#zipWarning').hide();
                    $('#zipSuccess').hide();
                    isZipNameValid = false;
                }
            });

            $('#compressForm').on('submit', function (e) {
                e.preventDefault();

                if (!isZipNameValid) {
                    alert('Tên file zip không hợp lệ hoặc đã tồn tại. Vui lòng kiểm tra lại!');
                    return false;
                }

                var zipFileName = $('#zipFileName').val();
                const selectedFiles = [];
                document.querySelectorAll('input[name="files[]"]:checked').forEach(input => {
                    selectedFiles.push(input.value);
                });

                if (zipFileName && selectedFiles.length > 0) {
                    $.ajax({
                        type: 'POST',
                        url: window.location.href,
                        data: {
                            action: 'compress',
                            zipFileName: zipFileName,
                            files: selectedFiles
                        },
                        dataType: 'json',
                        success: function (res) {
                            console.log('Compress response:', res);
                            alert(res.message);
                            if (res.status === 'success' && res.redirect) {
                                window.location.reload();
                            }
                        },
                        error: function (xhr) {
                            console.log('Compress error:', xhr.responseText);
                            let errorMessage = 'Đã xảy ra lỗi khi nén file.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            alert(errorMessage);
                        }
                    });
                } else {
                    alert('Vui lòng nhập tên file zip và chọn ít nhất một file!');
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function () {
            const mainCheckbox = document.getElementById("defaultCheck1");

            const fileCheckboxes = document.querySelectorAll('input[type="checkbox"][name="files[]"]');

            if (mainCheckbox) {
                mainCheckbox.addEventListener("change", function () {
                    fileCheckboxes.forEach(function (checkbox) {
                        checkbox.checked = mainCheckbox.checked;
                    });
                });

                fileCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener("change", function () {
                        mainCheckbox.checked = Array.from(fileCheckboxes).every((cb) => cb.checked);
                    });
                });
            }
            const deleteAllBtn = document.querySelector('[name="deleteAll"]');
            if (deleteAllBtn) {
                deleteAllBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const selectedFiles = [];
                    const checksessArray = {};
                    document.querySelectorAll('input[name="files[]"]:checked').forEach((input, index) => {
                        selectedFiles.push(input.value);
                        checksessArray[index] = input.getAttribute('data-checksess');
                    });
                    if (selectedFiles.length == 0) {
                        alert("Vui lòng chọn ít nhất một file để xóa!");
                        return;
                    }
                    if (!confirm("Bạn có chắc chắn muốn xóa tất cả các file đã chọn?")) {
                        return;
                    }
                    $.ajax({
                        type: 'POST',
                        url: '',
                        data: {
                            action: 'deleteAll',
                            files: selectedFiles,
                            checksess: checksessArray
                        },
                        success: function (res) {
                            console.log(res);
                            alert(res.message);
                            if (res.status === 'success' && res.redirect) {
                                window.location.reload();
                            }
                        },
                        error: function (xhr) {
                            alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Đã có lỗi xảy ra');
                        }
                    });
                });
            }
        });

        $('#new_name').on('input', function () {
            var new_name = $(this).val();
            var file_id = $('#file_id').val();

            if (new_name) {
                $.ajax({
                    type: 'POST',
                    url: window.location.href,
                    data: {
                        action: 'check_rename',
                        new_name: new_name,
                        file_id: file_id
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status == 'error') {
                            $('#renameWarning').html(res.message).show();
                            $('#renameSuccess').hide();
                        } else {
                            $('#renameSuccess').html(res.message).show();
                            $('#renameWarning').hide();
                        }
                    },
                    error: function (xhr) {
                        console.log('Error checking rename:', xhr.responseText);
                        $('#renameWarning').text('Có lỗi xảy ra khi kiểm tra tên mới').show();
                        $('#renameSuccess').hide();
                    }
                });
            } else {
                $('#renameWarning').hide();
                $('#renameSuccess').hide();
            }
        });

        function togglePreview(event, element) {
            event.preventDefault();
            console.log('Toggle Preview triggered.');

            var fileName = $(element).text().trim();
            var filePath = $(element).data('filepath');
            var fileType = $(element).data('filetype');

            var modalBody = $('#previewModalBody');
            modalBody.empty();

            $('#previewFileName').text(fileName);

            if (fileType === 'img') {
                modalBody.append('<img src="' + filePath + '" class="img-fluid" alt="' + fileName + '">');
            } else if (fileType === 'video') {
                modalBody.append('<video width="100%" controls><source src="' + filePath + '" type="video/mp4">{LANG.browser_support_video}</video>');
            } else if (fileType === 'audio') {
                modalBody.append('<div class="text-center"><audio controls style="width: 100%; min-height: 50px;"><source src="' + filePath + '" type="audio/mpeg">{LANG.browser_support_audio}</audio></div>');
            } else if (fileType === 'powerpoint') {
                modalBody.append('<div class="alert alert-warning text-center">{LANG.download_to_view_ppt}</div>');
            } else {
                modalBody.append('<p>{LANG.download_to_view_ppt}</p>');
            }

            $('#previewModal').modal('show');
        }

        $(document).ready(function () {
            $('.sortable').click(function () {
                var sortField = $(this).data('sort');
                var currentOrder = $(this).find('i').hasClass('fa-sort-up') ? 'desc' : 'asc';

                $('.sortable i').removeClass('fa-sort-up fa-sort-down').addClass('fa-sort');

                $(this).find('i').removeClass('fa-sort').addClass(currentOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down');

                sortTable(sortField, currentOrder);
            });
        });

        function sortTable(field, order) {
            var tbody = $('table tbody');
            var rows = tbody.find('tr').toArray();

            rows.sort(function (a, b) {
                var aVal = $(a).find('td').eq(getColumnIndex(field)).text().trim();
                var bVal = $(b).find('td').eq(getColumnIndex(field)).text().trim();

                if (field === 'file_size') {
                    aVal = convertFileSizeToBytes(aVal);
                    bVal = convertFileSizeToBytes(bVal);
                } else if (field === 'created_at') {
                    aVal = new Date(aVal).getTime();
                    bVal = new Date(bVal).getTime();
                }

                if (order === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });

            tbody.append(rows);
        }

        function getColumnIndex(field) {
            switch (field) {
                case 'file_name': return 1;
                case 'file_size': return 2;
                case 'created_at': return 3;
                default: return 0;
            }
        }

        function convertFileSizeToBytes(sizeStr) {
            var units = {
                'b': 1,
                'bytes': 1,
                'kb': 1024,
                'mb': 1024 * 1024,
                'gb': 1024 * 1024 * 1024
            };

            if (sizeStr.toLowerCase() == '0 bytes') {
                return 0;
            }

            var match = sizeStr.match(/^([\d.]+)\s*([A-Z]+)$/i);
            if (match) {
                var size = parseFloat(match[1]);
                var unit = match[2].toLowerCase();
                return size * units[unit];
            }
            return 0;
        }

</script>
<!-- END: main -->