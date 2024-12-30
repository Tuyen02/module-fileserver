<!-- BEGIN: main -->
<div class="container">
    <!-- BEGIN: error -->
    <div class="alert alert-danger" style="{ifempty(ERROR) display:none; }">
        {ERROR}
    </div>
    <!-- END: error -->

    <!-- BEGIN: success -->
    <div class="alert alert-success" style="{ifempty(SUCCESS) display:none; }">
        {SUCCESS}
    </div>
    <!-- END: success -->

    <form action="{FORM_ACTION}" method="post" enctype="multipart/form-data" id="uploadForm"
        class="form-inline my-2 my-lg-0">
        <button type="button" class="btn btn-primary" id="uploadButton">Import</button>
        <input type="file" name="uploadfile" id="uploadfile" required style="display: none;">
        <input type="hidden" name="submit_upload" value="1">
        <button type="submit" class="btn btn-success" id="submitForm" style="display: none;">Submit</button>
    </form>
</div>
<script>
    document.getElementById('uploadButton').addEventListener('click', function () {
        document.getElementById('uploadfile').click();
    });

    document.getElementById('uploadfile').addEventListener('change', function () {
        if (this.files.length > 0) {
            document.getElementById('uploadForm').submit();
        }
    });
</script>

<!-- END: main -->
