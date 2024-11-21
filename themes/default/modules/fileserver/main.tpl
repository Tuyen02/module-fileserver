<!-- BEGIN: main -->
<div class="container mt-4 mb-5 pb-5">
    <h1 class="text-center">Module File Server</h1>
    <br>
    <!-- BEGIN: error -->
    <div class="alert alert-warning">{ERROR}</div>
    <!-- END: error -->
    <form action="{FORM_ACTION}" method="get" id="searchForm" class="form-inline my-2 my-lg-0">

        <input type="text" class="form-control" placeholder="Tìm kiếm ..." id="searchInput" name="search"
            value="{SEARCH_TERM}">
        <select class="form-control ml-2" name="search_type">
            <option value="all">Tất cả</option>
            <option value="file">File</option>
            <option value="folder">Folder</option>
        </select>
        <button type="submit" class="btn btn-primary ml-2">Tìm kiếm</button>
    </form>

    <br>
    <form action="{FORM_ACTION}" method="post" enctype="multipart/form-data" id="uploadForm"
        class="form-inline my-2 my-lg-0">
        <button type="button" class="btn btn-warning" id="backButton">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> Quay lại
        </button>
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createModal">Tạo mục mới</a>
        <button type="button" class="btn btn-primary" id="uploadButton">Tải lên</button>
        <input type="file" name="uploadfile" id="uploadfile" required style="display: none;">
        <input type="hidden" name="submit_upload" value="1">
    </form>

    <hr>
    <table class="table table-hover">
        <thead class="thead-dark">
            <tr>
                <th scope="col"><input class="form-check-input" type="checkbox" value="" id="defaultCheck1"></th>
                <th scope="col">Tên</th>
                <th scope="col">Kích thước</th>
                <th scope="col">Ngày tải lên</th>
                <th scope="col">Quyền</th>
                <th scope="col">Tác giả</th>
                <th scope="col">Tùy chọn</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: file_row -->
            <tr>
                <td><input type="checkbox" name="files[]" value="{ROW.file_path}"></td>
                <td>
                    <a href="{VIEW}">
                        <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                        {ROW.file_name}
                    </a>
                </td>
                <td>{ROW.file_size}</td>
                <td>{ROW.created_at}</td>
                <td>
                    <a href="{ROW.url_perm}">{ROW.permissions}</i>
                    </a>
                </td>
                <td>{ROW.uploaded_by}</td>
                <td>
                    <a href="{ROW.url_delete}" data-file-id="{ROW.file_id}" data-checksess="{CHECK_SESS}"
                        class="btn btn-sm btn-danger delete" title="Xóa">
                        <i class="fa fa-trash-o"></i>
                    </a>
                    <button class="btn btn-sm btn-info rename" data-file-name="{ROW.file_name}"
                        data-file-id="{ROW.file_id}" data-toggle="modal" data-target="#renameModal" title="Đổi tên">
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                    </button>
                    <!-- BEGIN: edit -->
                    <a href="{EDIT}" class="btn btn-sm btn-info" title="Sửa">
                        <i class="fa fa-amazon"></i>
                    </a>
                    <!-- END: edit -->
                    <button class="btn btn-sm btn-info share" data-file-id="{ROW.file_id}" data-toggle="modal"
                        data-target="#shareModal" title="Chia sẻ">
                        <i class="fa fa-link" aria-hidden="true"></i>
                    </button>
                    <a href="{ROW.url_clone}" class="btn btn-sm btn-info" title="Sao chép">
                        <i class="fa fa-clone"></i>
                    </a>
                    <!-- BEGIN: download -->
                    <a href="{DOWNLOAD}" class="btn btn-sm btn-success" title="Tải xuống">
                        <i class="fa fa-download" aria-hidden="true"></i>
                    </a>
                    <!-- END: download -->
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
    </table>
    <hr>
    <button type="submit" name="compress" class="btn btn-primary mt-2" id="compressButton">Zip
        Files</button>
</div>
<br>

<!-- Modal Tạo Mới -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header row">
                <h3 class="modal-title col-lg-11" id="createModalLabel">Tạo mục mới</h3>
            </div>
            <div class="modal-body">
                <form id="createForm" method="post" action="">
                    <div class="form-group">
                        <label for="type">Loại:</label>
                        <select class="form-control" id="type" name="type">
                            <option value="0">File</option>
                            <option value="1">Thư mục</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="name">Tên:</label>
                        <input type="text" class="form-control" id="name_f" name="name_f" required>
                    </div>
                    <input type="hidden" name="create_action" value="create">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitCreateForm();">Tạo</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Đổi Tên -->
<div class="modal fade" id="renameModal" tabindex="-1" role="dialog" aria-labelledby="renameModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header row">
                <h3 class="modal-title col-lg-11" id="renameModalLabel">Đổi tên mục</h3>
            </div>
            <div class="modal-body">
                <form id="renameForm" method="post" action="">
                    <div class="form-group">
                        <label for="new_name">Tên mới:</label>
                        <input type="text" class="form-control" id="new_name" name="new_name" required>
                    </div>
                    <input type="hidden" name="file_id" id="file_id" value="">
                    <input type="hidden" name="rename_action" value="rename">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitRenameForm();">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Chia Sẻ -->
<div class="modal fade" id="shareModal" tabindex="-1" role="dialog" aria-labelledby="shareModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header row">
                <h3 class="modal-title col-lg-11" id="shareModalLabel">Chia sẻ file</h3>
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
</div>


<script>
    function submitCreateForm() {
        data = {
            'action': 'create',
            'name_f': $("#name_f").val(),
            'type': $("#type").val(),
        }
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
                alert('Đã có lỗi xảy ra. Vui lòng thử lại.');
            },
        });
    }

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

    $(document).on('click', '.delete', function () {
        const fileId = $(this).data('file-id');
        const deleteUrl = $(this).attr('href');
        const checksess = $(this).data('checksess');

        if (confirm("Bạn có chắc chắn muốn xóa mục này?")) {
            handleDelete(fileId, deleteUrl, checksess);
        }
    });

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
                alert('Đã có lỗi xảy ra. Vui lòng thử lại.');
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
                alert('Đã có lỗi xảy ra. Vui lòng thử lại.');
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
                alert('Đã có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    }

    $(document).on('click', '.share', function () {
        const fileId = $(this).data('file-id');
        $("#share_file_id").val(fileId);
    });

    document.querySelector('[name="compress"]').addEventListener('click', function (e) {
        e.preventDefault();

        const selectedFiles = [];
        document.querySelectorAll('input[name="files[]"]:checked').forEach(input => {
            selectedFiles.push(input.value);
        });

        if (selectedFiles.length == 0) {
            alert("Vui lòng chọn ít nhất một file để nén!");
            return;
        }

        console.log(selectedFiles);

        $.ajax({
            type: 'POST',
            url: '',
            data: {
                action: 'compress',
                files: selectedFiles
            },
            success: function (res) {
                console.log(res);
                alert(res.message);
                location.reload();
            },
            error: function () {
                alert('Đã có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });

</script>
<!-- END: main -->