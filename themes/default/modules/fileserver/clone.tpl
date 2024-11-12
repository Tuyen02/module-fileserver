<!-- BEGIN: main -->

<div id="result">
    <p class="alert alert-warning">{MESSAGE}</p>
</div>

<h3>Tên file: {FILE_NAME}</h3>
<p>Đường dẫn: {FILE_PATH}</p>

<form method="post">
    <input type="hidden" name="file_id" value="{FILE_ID}">

        <button type="submit" name="action" value="copy" class="btn btn-primary">
            <i class="fa fa-check-circle"></i> Copy
        </button>
        <button type="submit" name="action" value="move" class="btn btn-primary">
            <i class="fa fa-check-circle"></i> Move
        </button>
        <button type="button" class="btn btn-danger" onclick="goBack()">
            <i class="fa fa-times-circle"></i>Cancel
        </button>
    </p>
</form>

<p>Chọn thư mục:</p>

        <!-- BEGIN: directory_option -->
        <a href="javascript:void(0)" onclick="selectFolder('{DIRECTORY}')">
            <i class="fa fa-folder-o" aria-hidden="true"></i> {DIRECTORY}
        </a><br>
        <!-- END: directory_option -->
<p>

<script>
    function selectFolder(directory) {
        document.getElementsByName("target_folder")[0].value = directory;
        alert('Selected folder: ' + directory);
    }
    function goBack() {
        window.history.back();
    }
</script>
<!-- END: main -->
