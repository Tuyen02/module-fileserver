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
<div>
    <a href="{url_previous}" class="btn btn-sm btn-info">
        <i class="fa fa-chevron-left"></i>
    </a>
</div>

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
</script>
<!-- END: main -->