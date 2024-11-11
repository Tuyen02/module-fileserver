<!-- BEGIN: main -->
<div>
    <p>{MESSAGE}</p>
    <form action="" method="post">
        <div class="form-group">
            <label>{FILE_NAME}</label>
            <textarea class="form-control" name="file_content"
                style="width: 500px; height: 300px;">{FILE_CONTENT}</textarea>
            <input type="hidden" name="file_id" value="{FILE_ID}">
        </div>
        <button type="button" class="btn btn-warning" id="backButton">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> Quay lại
        </button>
        <button type="submit" class="btn btn-primary">Lưu</button>
    </form>
</div>
<script>
    $(document).ready(function () {

        $("#backButton").on("click", function (e) {
            e.preventDefault();
            window.history.back();
        });
    });
</script>
<!-- END: main -->