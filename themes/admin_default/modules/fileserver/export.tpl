<!-- BEGIN: main -->

<!-- BEGIN: error -->
<div class="alert alert-danger">
    {ERROR}
</div>
<!-- END: error -->
<h2>{LANG.export_title}</h2>
<table class="table table-bordered">
    <thead class="thead-dark">
        <tr>
            <th scope="col">{LANG.stt}</th>
            <th scope="col">{LANG.file_name}</th>
            <th scope="col">{LANG.file_size}</th>
            <th scope="col">{LANG.file_path}</th>
            <th scope="col">{LANG.created_at}</th>
        </tr>
    </thead>
    <tbody>
        <!-- BEGIN: file_row -->
        <tr>
            <td>
                {ROW.stt}
            </td>
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

<form action="{FORM_ACTION}" method="post" class="confirm-reload" enctype=“multipart/form-data”>
    <div class="form-group row text-center ">
        <button type="submit" class="btn btn-primary" value="1" name="submit" value="submit">{LANG.export_file}</button>
    </div>
</form>

<!-- END: main -->