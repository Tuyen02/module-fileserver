<!-- BEGIN: main -->

    <!-- BEGIN: error -->
    <div class="alert alert-danger">
        {ERROR}
    </div>
    <!-- END: error -->

    <form action="{FORM_ACTION}" method="post" class="confirm-reload" enctype=“multipart/form-data”>
        <div class="form-group row text-center ">
            <button type="submit" class="btn btn-primary" value="1" name="submit" value="submit">Xuất File</button>
        </div>
    </form>
    <table class="table">
        <thead class="thead-dark">
            <tr>
                <th scope="col">Tên file</th>
                <th scope="col">Kích thước</th>
                <th scope="col">Đường dẫn</th>
                <th scope="col">Ngày tạo</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: file_row -->
            <tr>
                <td>
                    {ROW.file_name}
                </td>
                <td>{ROW.file_size}</td>
                <td><a href="{ROW.url_download}">{ROW.file_path}</a></td>
                <td>{ROW.created_at}</td>
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
    </table>

<!-- END: main -->