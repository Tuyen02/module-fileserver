<!-- BEGIN: main -->
<div class="container mt-4 mb-5 pb-5">
    <h1 class="text-center">Module File Server</h1>
    <br>

    <form class="form-inline my-2 my-lg-0">
        <input type="text" class="form-control" placeholder="Tìm kiếm file..." id="searchInput">
        <a href="#" class="btn btn-primary">Tải lên</a>
        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createModal">Tạo mục mới</a>
    </form>
    <hr />
    <!-- File Table -->
    <table class="table table-hover">
        <thead class="thead-dark">
            <tr>
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
                <td>
                    <a href="#">
                        <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                        {ROW.file_name}
                    </a>
                </td>
                <td>{ROW.file_size}</td>
                <td>{ROW.created_at}</td>
                <td><a href="#">123</a></td>
                <td>{ROW.uploaded_by}</td>
                <td>
                    <a href="{ROW.url_delete}" class="btn btn-sm btn-info delete"><i class="fa fa-trash-o"></i></a>
                    <button class="btn btn-sm btn-info rename" data-file-name="{ROW.file_name}" data-file-id="{ROW.file_id}"
                        data-toggle="modal" data-target="#renameModal">
                        <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                    </button>
                    <button class="btn btn-sm btn-info"><i class="fa fa-clone" aria-hidden="true"></i></button>
                    <button class="btn btn-sm btn-info"><i class="fa fa-link" aria-hidden="true"></i></button>
                    <button class="btn btn-sm btn-info"><i class="fa fa-download" aria-hidden="true"></i></button>
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
    </table>
</div>

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
                            <option value="folder">Thư mục</option>
                            <option value="file">File</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="name">Tên:</label>
                        <input type="text" class="form-control" id="name" name="name" required>
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

<script>
    $(document).ready(function () {
        $('.delete').click(function () {
            return confirm("Xóa?");
        });

        // Sự kiện cho nút đổi tên
        $('.rename').click(function () {
            var fileName = $(this).data('file-name');
            var fileId = $(this).data('file-id');
            $('#new_name').val(fileName); 
            $('#file_id').val(fileId); 
        });

        $('#createForm').submit(function (e) {
            e.preventDefault();
            submitCreateForm();
        });
    });

    function submitCreateForm() {
        $.ajax({
            type: 'POST',
            url: $('#createForm').attr('action'), 
            data: $('#createForm').serialize(), 
            success: function (response) {
                alert('Tạo mục mới thành công!'); 
                location.reload(); 
            },
            error: function () {
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            }
        });
    }

    function submitRenameForm() {
        $.ajax({
            type: 'POST',
            url: $('#renameForm').attr('action'), 
            data: $('#renameForm').serialize(), 
            success: function (response) {
                alert('Đổi tên thành công!'); 
                location.reload(); 
            },
            error: function () {
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            }
        });
    }
</script>
<!-- END: main -->