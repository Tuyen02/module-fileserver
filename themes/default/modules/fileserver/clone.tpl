<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->
<div class="container">
    <h3 class="my-2">{LANG.file}: {FILE_NAME}</h3>
    <br>
     <p class="d-inline-block alert alert-info w-fit">{LANG.choose_folder}</p>
    <p id="selected-folder-path">{LANG.target_folder} <i class="fa fa-arrow-circle-down"
            aria-hidden="true"></i>:  <span
            class="text-success"><u><strong>{SELECTED_FOLDER_PATH}</strong></u></span></p>
    <!-- BEGIN: back -->
    <div>
        <button id="backButton">
            <i class="fa fa-chevron-circle-left text-primary" aria-hidden="true"></i>{BACK}
        </button>
    </div>
    <!-- END: back -->
    <!-- BEGIN: root_option -->
    <a href="{ROOT_URL}">
        <i class="fa fa-folder-o" aria-hidden="true"></i> {LANG.root_directory}
    </a><br>
    <!-- END: root_option -->
    <!-- BEGIN: directory_option -->
    <a href="{DIRECTORY.url}">
        <i class="fa fa-folder-o" aria-hidden="true"></i> {DIRECTORY.file_name}
    </a><br>
    <!-- END: directory_option -->
    <p>

    <form method="post">
        <input type="hidden" name="file_id" value="{FILE_ID}">
        <a href="{url_copy}" class="btn btn-info">
            <i class="fa fa-files-o"></i> {LANG.copy}
        </a>
        <a href="{url_move}" class="btn btn-warning">
            <i class="fa fa-location-arrow"></i> {LANG.move}
        </a>
        <a href="{url_view}" class="btn btn-danger">
            <i class="fa fa-times-circle"></i> {LANG.cancel}
        </a>
    </form>
</div>
<style>
    .w-fit {
        width: fit-content !important;
    }

    .text-indent {
        text-indent: 2em;
    }
</style>
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