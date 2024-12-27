<!-- BEGIN: main -->
<div class="container">
    <!-- BEGIN: error -->
    <div class="alert alert-danger">
        {ERROR}
    </div>
    <!-- END: error -->

    <div>
        <form action="{FORM_ACTION}" method="post" class="confirm-reload" enctype=“multipart/form-data”>
            <div class="form-group row text-center ">
                <button type="submit" class="btn btn-primary" value="1" name="submit" value="submit">Xuất File</button>
            </div>
        </form>
    </div>


    <table class="table table-hover">
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
                <td>{ROW.file_path}</td>
                <td>{ROW.created_at}</td>

                <!-- END: download -->
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
    </table>
</div>
<!-- END: main -->