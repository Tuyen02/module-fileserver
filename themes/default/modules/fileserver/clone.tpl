<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-warning">{MESSAGE}</div>
<!-- END: message -->
<h3>Tên file: {FILE_NAME}</h3>
<p>Đường dẫn: {FILE_PATH}</p>

<form method="post">
    <input type="hidden" name="file_id" value="{FILE_ID}">


    <a href="{url_copy}" class="btn btn-sm btn-info">
        <i class="fa fa-check-circle"></i> Copy
    </a>
    <a href="{url_move}" class="btn btn-sm btn-info">
        <i class="fa fa-check-circle"></i> Move
    </a>

    <button type="button" class="btn btn-danger" onclick="goBack()">
        <i class="fa fa-times-circle"></i>Cancel
    </button>
    </p>
</form>

<p>Chọn thư mục:</p>

<!-- BEGIN: directory_option -->
<a href="{DIRECTORY.url}">
    <i class="fa fa-folder-o" aria-hidden="true"></i> {DIRECTORY.file_name}
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