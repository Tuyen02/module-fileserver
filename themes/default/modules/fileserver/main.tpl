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
    <form action="{FORM_ACTION}" method="get" id="searchForm" class="form-inline my-2 my-lg-0" >
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
    <form action="{FORM_ACTION}" method="post" enctype="multipart/form-data" id="uploadForm"
        class="form-inline my-2 my-lg-0">
        <!-- BEGIN: back -->
        <button type="button" class="btn btn-warning" id="backButton">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
        </button>
        <!-- END: back -->
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createModal">{LANG.create_btn}</a>
        <button type="button" class="btn btn-primary" id="uploadButton">{LANG.upload_btn}</button>
        <input type="file" name="uploadfile" id="uploadfile" required style="display: none;">
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
                <!-- <th scope="col">{LANG.module_title}</th>
                <th scope="col">{LANG.module_title}</th> -->
                <th scope="col">{LANG.option}</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: file_row -->
            <tr>
                <td><input type="checkbox" name="files[]" value="{ROW.file_id}" data-checksess="{ROW.checksess}"></td>
                <td>
                    <a href="{VIEW}">
                        <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                        {ROW.file_name}
                    </a>
                </td>
                <td>{ROW.file_size}</td>
                <td>{ROW.created_at}</td>
                <!-- <td>
                    <a href="{ROW.url_perm}">{ROW.permissions}</i>
                    </a>
                </td>
                <td>{ROW.username} {ROW.uploaded_by}</td> -->
                <td>
                    <a href="{ROW.url_delete}" data-file-id="{ROW.file_id}" data-checksess="{CHECK_SESS}"
                        class="btn btn-sm btn-danger delete" title="{LANG.delete_btn}">
                        <i class="fa fa-trash-o"></i>
                    </a>
                    <button class="btn btn-sm btn-info rename" data-file-name="{ROW.file_name}"
                        data-file-id="{ROW.file_id}" data-toggle="modal" data-target="#renameModal"
                        title="{LANG.rename_btn}">
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                    </button>
                    <!-- BEGIN: edit -->
                    <a href="{EDIT}" class="btn btn-sm btn-info" title="{LANG.edit_btn}">
                        <i class="fa fa-pencil-square"></i>
                    </a>
                    <!-- END: edit -->
                    <!-- <button class="btn btn-sm btn-info share" data-file-id="{ROW.file_id}" data-toggle="modal"
                        data-target="#shareModal" title="{LANG.share_btn}">
                        <i class="fa fa-link" aria-hidden="true"></i>
                    </button> -->
                    <!-- BEGIN: copy -->
                    <a href="{COPY}" class="btn btn-sm btn-info" title="{LANG.copy}">
                        <i class="fa fa-clone"></i>
                    </a>
                    <!-- END: copy -->
                    <a href="{ROW.url_perm}" class="btn btn-sm btn-info share" title="{LANG.perm_btn}">
                        <i class="fa fa-link"></i>
                    </a>
                    <!-- BEGIN: download -->
                    <a href="{DOWNLOAD}" class="btn btn-sm btn-success download" data-file-id="{ROW.file_id}"
                        title="{LANG.download_btn}">
                        <i class="fa fa-download" aria-hidden="true"></i>
                    </a>
                    <!-- END: download -->
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
        <tfoot>
            <tr>
                <td class="gray" colspan="7">
                    <strong>Full Size:</strong>
                    <span class="badge text-bg-light border-radius-0">{ROW.total_size}</span>
                    <strong>File:</strong>
                    <span class="badge badge-secondary">{ROW.total_files}</span>
                    <strong>Folder:</strong>
                    <span class="badge badge-secondary">{ROW.total_folders}</span>
                </td>
            </tr>
        </tfoot>
    </table>
    <hr>
    <a href="#" class="btn btn-primary" id="compressButton" data-toggle="modal" data-target="#compressModal">
        <i class="fa fa-file-archive-o" aria-hidden="true"></i> {LANG.zip_btn}
    </a>

    <div class="modal fade" id="compressModal" tabindex="-1" role="dialog" aria-labelledby="compressModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="compressModalLabel">Nén Modal</h3>
                </div>
                <div class="modal-body">
                    <form id="compressForm" onsubmit="submitCompressForm(event);">
                        <div class="form-group">
                            <label for="zipFileName">Tên file zip</label>
                            <input type="text" class="form-control" id="zipFileName" name="zipFileName" required>
                            <div id="fileNameWarning" class="text-danger mt-2" style="display: none;"></div>
                        </div>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
                        <button type="submit" class="btn btn-primary">{LANG.zip_btn}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <button type="submit" name="deleteAll" class="btn btn-danger mt-2 deleteAll" id="deleteAll"><i class="fa fa-trash"
            aria-hidden="true"></i> {LANG.delete_btn}
    </button>
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
                <form id="createForm" method="post" action="" onsubmit="submitCreateForm(event);">
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
                        <div id="nameWarning" class="text-danger mt-2" style="display: none;"></div>
                        <div id="nameSuccess" class="text-success mt-2" style="display: none;"></div>
                    </div>
                    <input type="hidden" name="create_action" value="create">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
                <button type="button" class="btn btn-primary" onclick="submitCreateForm();">{LANG.create_btn}</button>
            </div>
        </div>
    </div>
</div>

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
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{LANG.close_btn}</button>
                <button type="button" class="btn btn-primary" onclick="submitRenameForm();">{LANG.submit_btn}</button>
            </div>
        </div>
    </div>
</div>

<!-- <div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header row">
                <h3 class="modal-title col-lg-11" id="shareModalLabel">{LANG.share_btn}</h3>
            </div>
            <div class="modal-body">
                <form id="shareForm" method="post" action="">
                    <div class="form-group">
                        <label for="share_option">Chọn tùy chọn chia sẻ:</label>
                        <select class="form-control" id="share_option" name="share_option" required>
                            <option value="0">Không chia sẻ</option>
                            <option value="1">Chia sẻ với người có tài khoản</option>
                            <option value="2">Chia sẻ với tất cả mọi người</option>
                        </select>
                    </div>
                    <input type="hidden" name="file_id" id="share_file_id" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitShareForm();">Chia sẻ</button>
            </div>
        </div>
    </div>
</div> -->


<script>

    function submitCreateForm() {
        var name_f = $("#name_f").val();
        var type = $("#type").val();
        var allowedExtensions = ['txt', 'doc', 'docx', 'pdf', 'xlsx', 'xls'];
        var extension = name_f.split('.').pop().toLowerCase();
        var nameWarning = $("#nameWarning");

        nameWarning.hide();

        if (type == '0' && (extension == '' || !allowedExtensions.includes(extension))) {
            nameWarning.text('Tên file không hợp lệ. Vui lòng nhập tên file có đuôi hợp lệ.');
            nameWarning.show();
        }

        if (name_f.trim() == '') {
            nameWarning.text('Tên file không được để trống.');
            nameWarning.show();
        }

        var data = {
            'action': 'create',
            'name_f': name_f,
            'type': type,
        };

        $.ajax({
            type: 'POST',
            url: "",
            data: data,
            success: function (res) {
                if (res.status == 'error') {
                    nameWarning.text(res.message);
                    nameWarning.show();
                } else {
                    location.reload();
                    alert(res.message);
                }
            },
            error: function () {
                nameWarning.text(res.message);
                nameWarning.show();
            },
        });
    }

    $(document).on('click', '.delete', function () {
        const fileId = $(this).data('file-id');
        const deleteUrl = $(this).attr('href');
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
            success: function (res) {
                console.log(res);
                alert(res.message);
                location.reload();
            },
            error: function () {
                alert('Đã có lỗi xảy ra. Vui lòng thử lại.');
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
            url: "",
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                console.log(res);
                alert(res.message);
                location.reload();
            },
            error: function () {
                alert(res.message);
            }
        });
    });

    document.getElementById('uploadButton').addEventListener('click', function () {
        document.getElementById('uploadfile').click();
    });

    document.getElementById('uploadfile').addEventListener('change', function () {
        document.getElementById('uploadForm').submit();
    });

    $(document).ready(function () {
        $("#backButton").on("click", function (e) {
            e.preventDefault();
            window.history.back();
        });
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
            url: "{FORM_ACTION}",
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
                    url: '',
                    data: {
                        action: 'check_filename',
                        zipFileName: zipFileName,
                        files: selectedFiles,
                        lev: 0
                    },
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
                    error: function () {
                        alert(res.message);
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
                    url: '',
                    data: {
                        action: 'compress',
                        zipFileName: zipFileName,
                        files: selectedFiles
                    },
                    success: function (res) {
                        console.log(res);
                        alert(res.message);
                        location.reload();
                    },
                    error: function () {
                        alert(res.message);
                    }
                });
            } else {
                alert('Vui lòng nhập tên file zip!');
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
        const checksessArray = [];
        document.querySelectorAll('input[name="files[]"]:checked').forEach(input => {
            selectedFiles.push(input.value);
            checksessArray.push(input.getAttribute('data-checksess'));
        });

        if (selectedFiles.length == 0) {
            alert("Vui lòng chọn ít nhất một file để xóa!");
            return;
        }
        if (!confirm("Bạn có chắc chắn muốn xóa tất cả các file đã chọn?")) {
            return;
        }

        console.log(selectedFiles);
        console.log(checksessArray);

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
                location.reload();
            },
            error: function () {
                alert(res.message);
            }
        });
    });

</script>
<!-- END: main -->