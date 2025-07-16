<!-- BEGIN: main -->
<div>
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
    <div class="card border border-primary border-2">
        <div class="card-header">
            <h2 class="card-title">{LANG.choose_file}</h2>
        </div>
        <div class="card-body">
            <form action="{FORM_ACTION}" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="file" name="excel_file" accept=".xlsx,.xls" required>
                </div>
                <button type="submit" name="submit_upload" class="btn btn-primary">{LANG.submit}</button>
            </form>
        </div>
    </div>
    <div class="alert alert-warning mt-5 d-inline" style="width: 700px;">
        <p>{LANG.caution}</p>
        {LANG.demo_title} <a href="{URL_DOWNLOAD}"><i class="fa fa-file-excel-o"></i> {LANG.demo_file}</a>
    </div>
</div>
<!-- END: main -->