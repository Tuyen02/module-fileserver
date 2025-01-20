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
            <h3 class="card-title">Import File</h3>
        </div>
        <div class="card-body">
            <form action="{FORM_ACTION}" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label>Chọn file để import</label>
                    <input type="file" name="uploadfile" id="uploadfile" required>
                </div>
                <input type="hidden" name="submit_upload" value="1">
                <button type="submit" class="btn btn-success" id="submitForm" >Submit</button>
            </form>
        </div>
    </div>

    <div class="alert alert-warning mt-5">
        <p>Lưu ý dạng file tải lên phải là file excel có các đuôi như: xlsx, xls</p>
        File mẫu có dạng như sau: <a href="{URL_DOWNLOAD}"><i class="fa fa-file-excel-o"></i> test.xlsx</a>
    </div>
</div>

<!-- END: main -->