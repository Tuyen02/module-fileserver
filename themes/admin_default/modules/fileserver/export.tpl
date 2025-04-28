<!-- BEGIN: main -->

<!-- BEGIN: error -->
<div class="alert alert-danger">
    {ERROR}
</div>
<!-- END: error -->
<h2>{LANG.list_items_root}</h2>
<table class="table table-bordered">
    <thead class="thead-dark">
        <tr>
            <th class="text-center">{LANG.stt}</th>
            <th class="text-center">{LANG.file_name}</th>
            <th class="text-center">{LANG.file_size}</th>
            <th class="text-center">{LANG.created_at}</th>
        </tr>
    </thead>
    <tbody>
        <!-- BEGIN: file_row -->
        <tr>
            <td class="text-center">
                {ROW.stt}
            </td>
            <td class="text-center">
                <a href="{ROW.url_download}">{ROW.file_name}</a>
            </td>
            <td class="text-center">{ROW.file_size}</td>
            <td class="text-center">{ROW.created_at}</td>
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