<!-- BEGIN: main -->
<div class="container mt-4 mb-5 pb-5">
    <h1 class="text-center">{LANG.module_title}</h1>
    <br>
    <!-- BEGIN: error -->
    <div class="alert alert-warning">{ERROR}</div>
    <!-- END: error -->
    <!-- BEGIN: success -->
    <div class="alert alert-warning">{SUCCESS}</div>
    <!-- END: success -->
    <form action="" method="get" id="searchForm" class="form-inline my-2 my-lg-0">
        <input type="hidden" name="lev" value="{ROW.lev}">
        <input type="text" class="form-control" placeholder="{LANG.search}" id="searchInput" name="search"
            value="{SEARCH_TERM}">
        <select class="form-control ml-2" name="search_type">
            <option value="all" {SELECTED_ALL}>{LANG.all}</option>
            <option value="file" {SELECTED_FILE}>{LANG.file}</option>
            <option value="folder" {SELECTED_FOLDER}>{LANG.folder}</option>
        </select>
        <button type="submit" class="btn btn-primary ml-2">{LANG.search_btn}</button>
    </form>

    <br>
    <form action="" method="post" enctype="multipart/form-data" id="uploadForm" class="form-inline my-2 my-lg-0">
        <!-- BEGIN: can_create -->
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createModal">{LANG.create_btn}</a>
        <button type="button" class="btn btn-primary" id="uploadButton">{LANG.upload_btn}</button>
        <!-- END: can_create -->
        <!-- BEGIN: back -->
        <a href="{BACK_URL}" class="btn btn-info">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
        </a>
        <!-- END: back -->
        <input type="file" name="uploadfile" id="uploadfile" required style="display: none;">
        <input type="hidden" name="lev" id="lev" value="{ROW.lev}">
        <input type="hidden" name="submit_upload" value="1">
    </form>

    <hr>
    <table class="table table-hover">
        <thead class="thead-dark">
            <tr>
                <th scope="col"><input class="form-check-input" type="checkbox" value="" id="defaultCheck1"></th>
                <th scope="col">{LANG.f_name}</th>
                <th scope="col">{LANG.f_size}</th>
                <th scope="col">{LANG.created_at}</th>
                <th scope="col">{LANG.option}</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: no_data -->
            <tr>
                <td colspan="5" class="text-center">{LANG.no_data}</td>
            </tr>
            <!-- END: no_data -->
            <!-- BEGIN: file_row -->
            <tr>
                <td>
                    <input type="checkbox" name="files[]" value="{ROW.file_id}" data-checksess="{ROW.checksess}">
                </td>
                <td>
                    <a href="{VIEW}">
                        <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                        {ROW.file_name}
                    </a>
                </td>
                <td>{ROW.file_size}</td>
                <td>{ROW.created_at}</td>
                <td>
                    <!-- BEGIN: delete -->
                    <button class="btn btn-sm btn-danger delete" data-file-id="{ROW.file_id}"
                        data-checksess="{CHECK_SESS}" data-url="{ROW.url_delete}" title="{LANG.delete_btn}">
                        <i class="fa fa-trash-o"></i>
                    </button>
                    <!-- END: delete -->

                    <!-- BEGIN: rename -->
                    <button class="btn btn-sm btn-info rename" data-file-name="{ROW.file_name}"
                        data-file-id="{ROW.file_id}" data-toggle="modal" data-target="#renameModal"
                        title="{LANG.rename_btn}">
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                    </button>
                    <!-- END: rename -->

                    <!-- BEGIN: share -->
                    <a href="{ROW.url_perm}" class="btn btn-sm btn-info share" title="{LANG.perm_btn}">
                        <i class="fa fa-link"></i>
                    </a>
                    <!-- END: share -->

                    <!-- BEGIN: download -->
                    <a href="{DOWNLOAD}" class="btn btn-sm btn-success download" data-file-id="{ROW.file_id}"
                        title="{LANG.download_btn}">
                        <i class="fa fa-download" aria-hidden="true"></i>
                    </a>
                    <!-- END: download -->

                    <!-- BEGIN: edit -->
                    <a href="{EDIT}" class="btn btn-sm btn-info" title="{LANG.edit_btn}">
                        <i class="fa fa-pencil-square"></i>
                    </a>
                    <!-- END: edit -->

                    <!-- BEGIN: copy -->
                    <a href="{COPY}" class="btn btn-sm btn-info" title="{LANG.copy}">
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
                    <span class="badge text-bg-light border-radius-0">{ROW.total_size}</span>
                    <strong>{LANG.file}</strong>
                    <span class="badge badge-secondary">{ROW.total_files}</span>
                    <strong>{LANG.folder}</strong>
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
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
                        <button type="submit" class="btn btn-primary">{LANG.zip_btn}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="text-center">{GENERATE_PAGE}</div>

<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header row">
                <h3 class="modal-title col-lg-11" id="createModalLabel">{LANG.create_btn}</h3>
            </div>
            <div class="modal-body">
                <form id="createForm" method="post" action="" onsubmit="submitCreateForm(event);" <!-- BEGIN: recaptcha3
                    -->data-recaptcha3="1"<!-- END: recaptcha3 -->>
                    <div class="form-group">
                        <label for="type">{LANG.type}:</label>
                        <select class="form-control" id="type" name="type">
                            <option value="0">{LANG.file}</option>
                            <option value="1">{LANG.folder}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="name">{LANG.f_name}:</label>
                        <input type="text" class="form-control" id="name_f" name="name_f" required>
                    </div>
                    <input type="hidden" name="action" value="create">
                    <!-- BEGIN: captcha -->
                    <div class="form-group">
                        <div class="middle text-right clearfix">
                            <img width="{GFX_WIDTH}" height="{GFX_HEIGHT}" title="{LANG.captcha}" alt="{LANG.captcha}"
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
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
                    </div>
                    <input type="hidden" name="file_id" id="file_id" value="">
                    <input type="hidden" name="rename_action" value="rename">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.cancel_btn}</button>
                <button type="button" class="btn btn-primary" onclick="submitRenameForm();">{LANG.submit_btn}</button>
            </div>
        </div>
    </div>
</div>

<script>
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
                    }
                },
                error: function (xhr) {
                    console.log('Create error:', xhr.responseText);
                    alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Đã có lỗi xảy ra.');
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

    document.getElementById('uploadButton').addEventListener('click', function () {
        document.getElementById('uploadfile').click();
    });

    document.getElementById('uploadfile').addEventListener('change', function () {
        document.getElementById('uploadForm').submit();
    });

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

        let isFileNameValid = false;

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
                        action: 'check_filename',
                        zipFileName: zipFileName,
                        files: selectedFiles,
                        lev: $('#lev').val()
                    },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status == 'error') {
                            $('#fileNameWarning').text(res.message).show();
                            $('#fileNameSuccess').hide();
                            isFileNameValid = false;
                        } else {
                            $('#fileNameSuccess').text(res.message).show();
                            $('#fileNameWarning').hide();
                            isFileNameValid = true;
                        }
                    },
                    error: function (xhr) {
                        console.log('Error checking filename:', xhr.responseText);
                        $('#fileNameWarning').text('Lỗi khi kiểm tra tên file.').show();
                        isFileNameValid = false;
                    }
                });
            } else {
                $('#fileNameWarning').hide();
                $('#fileNameSuccess').hide();
                isFileNameValid = false;
            }
        });

        $('#compressForm').on('submit', function (e) {
            e.preventDefault();
            var zipFileName = $('#zipFileName').val();
            const selectedFiles = [];
            document.querySelectorAll('input[name="files[]"]:checked').forEach(input => {
                selectedFiles.push(input.value);
            });

            if (!isFileNameValid) {
                alert('Tên file không hợp lệ. Vui lòng kiểm tra lại.');
                return false;
            }

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
    });


    document.querySelector('[name="deleteAll"]').addEventListener('click', function (e) {
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

</script>
<!-- END: main -->