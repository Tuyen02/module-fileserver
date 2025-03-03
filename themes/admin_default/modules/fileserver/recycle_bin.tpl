<!-- BEGIN: main -->
<div class="container mt-4 mb-5 pb-5">
    <h1 class="text-center">Thùng rác</h1>
    <br>
    <!-- BEGIN: error -->
    <div class="alert alert-warning">{ERROR}</div>
    <!-- END: error -->
    <!-- BEGIN: success -->
    <div class="alert alert-success">{SUCCESS}</div>
    <!-- END: success -->

    <form action="" method="get" id="searchForm" class="form-inline my-2 my-lg-0">
        <!-- BEGIN: back -->
        <button type="button" class="btn btn-warning" id="backButton">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
        </button>
        <!-- END: back -->
        <input type="hidden" name="{NV_LANG_VARIABLE}" value="{NV_LANG_DATA}">
        <input type="hidden" name="{NV_NAME_VARIABLE}" value="{MODULE_NAME}">
        <input type="hidden" name="{NV_OP_VARIABLE}" value="recycle_bin">
        <input type="hidden" name="lev" value="{LEV}">
        <input type="text" class="form-control" placeholder="Tìm kiếm" id="searchInput" name="search" value="{SEARCH_TERM}">
        <select class="form-control ml-2" name="search_type">
            <option value="all" {SELECTED_ALL}>Tất cả</option>
            <option value="file" {SELECTED_FILE}>File</option>
            <option value="folder" {SELECTED_FOLDER}>Folder</option>
        </select>
        <button type="submit" class="btn btn-primary ml-2">Tìm kiếm</button>
    </form>

    <br>
    <hr>

    <table class="table table-hover">
        <thead class="thead-dark">
            <tr>
                <th scope="col"><input class="form-check-input" type="checkbox" id="selectAll"></th>
                <th scope="col">Tên</th>
                <th scope="col">Kích cỡ</th>
                <th scope="col">Ngày xóa</th>
                <th scope="col">Tùy chọn</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: file_row -->
            <tr>
                <td><input type="checkbox" name="files[]" value="{ROW.file_id}" data-checksess="{ROW.checksess}"></td>
                <td>
                    <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                    {ROW.file_name}
                </td>
                <td>{ROW.file_size}</td>
                <td>{ROW.deleted_at}</td>
                <td>
                    <button class="btn btn-sm btn-danger delete" data-file-id="{ROW.file_id}" data-checksess="{ROW.checksess}" data-url="{ROW.url_delete}" title="{LANG.delete_btn}">
                        <i class="fa fa-trash-o"></i>
                    </button>
                    <button class="btn btn-sm btn-success restore" data-file-id="{ROW.file_id}" data-url="{ROW.url_restore}" title="Khôi phục">
                        <i class="fa fa-reply"></i>
                    </button>
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
    </table>
    <button type="button" class="btn btn-primary mt-2" id="restoreAll"><i class="fa fa-reply" aria-hidden="true"></i> Khôi phục tất cả</button>
    <button type="button" class="btn btn-danger mt-2" id="deleteAll"><i class="fa fa-trash" aria-hidden="true"></i> {LANG.delete_btn}</button>
    <hr>

    <!-- BEGIN: generate_page -->
    <div class="text-center">{GENERATE_PAGE}</div>
    <!-- END: generate_page -->
</div>

<script>
    $(document).ready(function () {
        $("#backButton").on("click", function (e) {
            e.preventDefault();
            window.history.back();
        });

        $("#selectAll").on("change", function () {
            $("input[name='files[]']").prop("checked", this.checked);
        });

        $(document).on('click', '.delete', function () {
            const fileId = $(this).data('file-id');
            const deleteUrl = $(this).data('url');
            const checksess = $(this).data('checksess');
            if (confirm("Bạn có chắc chắn muốn xóa vĩnh viễn mục này?")) {
                handleAction('delete', fileId, deleteUrl, checksess);
            }
        });

        $(document).on('click', '.restore', function () {
            const fileId = $(this).data('file-id');
            const restoreUrl = $(this).data('url');
            if (confirm("Bạn có chắc chắn muốn khôi phục mục này?")) {
                handleAction('restore', fileId, restoreUrl, null);
            }
        });

        $("#deleteAll").on('click', function () {
            const selectedFiles = [];
            const checksessArray = [];
            $("input[name='files[]']:checked").each(function () {
                selectedFiles.push($(this).val());
                checksessArray.push($(this).data('checksess'));
            });
            if (selectedFiles.length === 0) {
                alert("Vui lòng chọn ít nhất một mục để xóa!");
                return;
            }
            if (confirm("Bạn có chắc chắn muốn xóa vĩnh viễn các mục đã chọn?")) {
                handleBulkAction('deleteAll', selectedFiles, checksessArray);
            }
        });

        $("#restoreAll").on('click', function () {
            const selectedFiles = [];
            $("input[name='files[]']:checked").each(function () {
                selectedFiles.push($(this).val());
            });
            if (selectedFiles.length === 0) {
                alert("Vui lòng chọn ít nhất một mục để khôi phục!");
                return;
            }
            if (confirm("Bạn có chắc chắn muốn khôi phục các mục đã chọn?")) {
                handleBulkAction('restoreAll', selectedFiles, null);
            }
        });
    });

    function handleAction(action, fileId, url, checksess) {
        const data = { action: action, file_id: fileId };
        if (checksess) {
            data.checksess = checksess;
        }
        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res && typeof res.status !== 'undefined' && typeof res.message !== 'undefined') {
                    alert(res.message);
                    if (res.status === 'success') {
                        location.reload();
                    }
                } else {
                    alert('Phản hồi không hợp lệ từ server');
                }
            },
            error: function (xhr, status, error) {
                alert('Đã có lỗi xảy ra: ' + (xhr.responseText || 'Không rõ nguyên nhân'));
            }
        });
    }

    function handleBulkAction(action, files, checksessArray) {
        const data = { action: action, files: files };
        if (checksessArray) {
            data.checksess = checksessArray;
        }
        $.ajax({
            type: 'POST',
            url: '{FORM_ACTION}',
            data: data,
            dataType: 'json',
            success: function (res) {
                if (res && typeof res.status !== 'undefined' && typeof res.message !== 'undefined') {
                    alert(res.message);
                    if (res.status === 'success') {
                        location.reload();
                    }
                } else {
                    alert('Phản hồi không hợp lệ từ server');
                }
            },
            error: function (xhr, status, error) {
                alert('Đã có lỗi xảy ra: ' + (xhr.responseText || 'Không rõ nguyên nhân'));
            }
        });
    }
</script>
<!-- END: main -->