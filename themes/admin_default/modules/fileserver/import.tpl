<!-- BEGIN: main -->
<div class="container mt-5">
    <!-- BEGIN: error -->
    <div class="alert alert-danger">
        {ERROR}
    </div>
    <!-- END: error -->

    <!-- BEGIN: success -->
    <div class="alert alert-success">
        {SUCCESS}
    </div>
    <!-- END: success -->

    <div class="card border border-primary">
        <div class="card-header">
            <h2 class="card-title">{LANG.import_file}</h2>
        </div>
        <div class="card-body">
            <form action="{FORM_ACTION}" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label>{LANG.choose_file}</label>
                    <input type="file" name="uploadfile" id="uploadfile" required>
                </div>
                <input type="hidden" name="submit_upload" value="1">
                <button type="submit" class="btn btn-success" id="submitForm">{LANG.submit}</button>
            </form>
        </div>
    </div>

    <div class="alert alert-warning mt-5">
        <p>{LANG.caution}</p>
        {LANG.demo_title} <a href="{URL_DOWNLOAD}"><i class="fa fa-file-excel-o"></i> {LANG.demo_file}</a>
    </div>
</div>
<!-- END: main -->