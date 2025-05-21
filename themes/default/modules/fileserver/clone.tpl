<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->
<div class="container">
    <h3 class="my-2">{LANG.file}: {FILE_NAME}</h3>
    <br>
    <p class="d-inline-block alert alert-info w-fit">{LANG.target_folder_not_found}</p>
    <p id="selected-folder-path">{LANG.target_folder} <i class="fa fa-arrow-circle-down" aria-hidden="true"></i>: 
        <span class="text-success"><u><strong>{SELECTED_FOLDER_PATH}</strong></u></span>
    </p>
    <div id="folder-tree" class="folder-tree">{TREE_HTML}</div>
    <div>
        <a id="copyButton" href="{url_copy}" class="btn btn-info">
            <i class="fa fa-files-o"></i> {LANG.copy}
        </a>
        <a id="moveButton" href="{url_move}" class="btn btn-warning">
            <i class="fa fa-location-arrow"></i> {LANG.move}
        </a>
        <a href="{url_view}" class="btn btn-danger">
            <i class="fa fa-times-circle"></i> {LANG.cancel}
        </a>
    </div>
</div>
<style>
    .w-fit {
        width: fit-content !important;
    }
    .folder-tree ul {
        list-style-type: none;
        margin-left: 20px;
        padding-left: 10px;
        border-left: 1px dashed #ccc;
    }
    .folder-tree li {
        margin: 5px 0;
    }
    .folder-tree .folder-name {
        cursor: pointer;
        display: inline-block;
    }
    .folder-tree .folder-name:hover {
        color: #007bff;
    }
    .folder-tree .selected {
        font-weight: bold;
        color: #007bff;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#folder-tree .folder-name').forEach(function(folder) {
        folder.addEventListener('click', function(e) {
            e.stopPropagation();

            document.querySelectorAll('#folder-tree .folder-name').forEach(function(f) {
                f.classList.remove('selected');
            });

            this.classList.add('selected');

            const li = this.parentElement;
            const fileId = li.getAttribute('data-file-id');
            const folderPath = li.getAttribute('data-path');
            const folderUrl = li.getAttribute('data-url');

            document.getElementById('selected-folder-path').innerHTML = 
                'Thư mục đích: <span class="text-success"><u><strong>' + folderPath + '</strong></u></span>';
            
            window.history.pushState({}, '', folderUrl);
            
            const copyButton = document.getElementById('copyButton');
            const moveButton = document.getElementById('moveButton');
            const baseCopyUrl = copyButton.getAttribute('href').split('&copy=')[0];
            const baseMoveUrl = moveButton.getAttribute('href').split('&move=')[0];
            copyButton.setAttribute('href', folderUrl + '&copy=1');
            moveButton.setAttribute('href', folderUrl + '&move=1');
        });
    });
});
</script>
<!-- END: main -->