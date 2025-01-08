<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-warning">{MESSAGE}</div>
<!-- END: message -->
<h3>{LANG.f_name}: {FILE_NAME}</h3>
<p>{LANG.f_path}: {FILE_PATH}</p>

<form method="post">
    <input type="hidden" name="file_id" value="{FILE_ID}">
    <a href="{url_copy}" class="btn btn-info">
        <i class="fa fa-check-circle"></i> {LANG.copy}
    </a>
    <a href="{url_move}" class="btn btn-info">
        <i class="fa fa-check-circle"></i> {LANG.move}
    </a>
    <a href="{url_view}" class="btn btn-danger">
        <i class="fa fa-times-circle"></i> {LANG.cancel}
    </a>
</form>
<p>{LANG.choose_folder}:</p>
<p id="selected-folder-path">Đường dẫn thư mục đích: <span class="text-success"><u>{SELECTED_FOLDER_PATH}</u></span></p>
<!-- BEGIN: back -->
<div>
    <button type="button" class="btn btn-info" id="backButton">
        <i class="fa fa-chevron-circle-left" aria-hidden="true"></i>{BACK}
    </button>
</div>
<!-- END: back -->
<!-- BEGIN: directory_option -->
<a href="{DIRECTORY.url}">
    <i class="fa fa-folder-o" aria-hidden="true"></i> {DIRECTORY.file_name}{NO_DIRECTORY}
</a><br>
<!-- END: directory_option -->
<p>
<script>
    function selectFolder(directory) {
        document.getElementsByName("target_folder")[0].value = directory;
        alert('Selected folder: ' + directory);
        document.getElementById("selected-folder-path").innerText = 'Đường dẫn thư mục đích: ' + directory;
    }
    $(document).ready(function () {
        $("#backButton").on("click", function (e) {
            e.preventDefault();
            window.history.back();
        });
    });
</script>
<!-- END: main -->