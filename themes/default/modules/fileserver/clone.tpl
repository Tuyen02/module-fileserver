<!-- BEGIN: main -->
<div class="container">
    <h2>{LANG.file}: {FILE_NAME}</h2>
    <p class="d-inline-block alert alert-info w-fit" id="target-folder-notice">{LANG.target_folder_not_found}</p>
    <p id="selected-folder-path">{LANG.target_folder} <i class="fa fa-arrow-circle-down" aria-hidden="true"></i>:
        <span class="text-success"><u><strong>{SELECTED_FOLDER_PATH}</strong></u></span>
    </p>
    <div class="root-folder-option">
        <i class="fa fa-home"></i> {LANG.root}
    </div>
    <div id="folder-tree" class="folder-tree">{TREE_HTML}</div>
    <form id="file-action-form" method="post" enctype="multipart/form-data" action="">
        <input type="hidden" name="file_id" value="{FILE_ID}">
        <input type="hidden" name="rank" id="rank-input" value="">
        <input type="hidden" name="root" id="root-input" value="0">
        <input type="hidden" name="action" id="action-input" value="">
        <div>
            <button type="button" class="btn btn-info" onclick="submitAction('copy')">
                <i class="fa fa-files-o"></i> {LANG.copy}
            </button>
            <button type="button" class="btn btn-warning" onclick="submitAction('move')">
                <i class="fa fa-location-arrow"></i> {LANG.move}
            </button>
            <a href="{url_view}" class="btn btn-danger">
                <i class="fa fa-times-circle"></i> {LANG.cancel}
            </a>
        </div>
    </form>
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
    .root-folder-option {
        cursor: pointer;
    }
    .root-folder-option:hover {
        color: #007bff;
    }
    .file-info {
        margin-top: 15px;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }
</style>

<script>

document.addEventListener('DOMContentLoaded', function() {
    const selectedFolderPath = document.getElementById('selected-folder-path');
    const rankInput = document.getElementById('rank-input');
    const rootInput = document.getElementById('root-input');
    const targetFolderNotice = document.getElementById('target-folder-notice');

    document.querySelectorAll('#folder-tree .folder-name').forEach(function(folder) {
        folder.addEventListener('click', function(e) {
            e.stopPropagation();
            document.querySelectorAll('#folder-tree .folder-name').forEach(function(f) {
                f.classList.remove('selected');
            });
            document.querySelector('.root-folder-option').classList.remove('selected');
            this.classList.add('selected');
            const li = this.parentElement;
            const fileId = li.getAttribute('data-file-id');
            const folderPath = li.getAttribute('data-path');
            selectedFolderPath.innerHTML =
                '{LANG.target_folder} <i class="fa fa-arrow-circle-down" aria-hidden="true"></i>: <span class="text-success"><u><strong>' + folderPath + '</strong></u></span>';
            rankInput.value = fileId;
            rootInput.value = 0;
            targetFolderNotice.style.display = 'none';
        });
    });

    document.querySelector('.root-folder-option').addEventListener('click', function() {
        document.querySelectorAll('#folder-tree .folder-name').forEach(function(f) {
            f.classList.remove('selected');
        });
        this.classList.add('selected');
        selectedFolderPath.innerHTML =
            '{LANG.target_folder} <i class="fa fa-arrow-circle-down" aria-hidden="true"></i>: <span class="text-success"><u><strong>{LANG.root}</strong></u></span>';
        rankInput.value = '';
        rootInput.value = 1;
        targetFolderNotice.style.display = 'none';
    });
});

function submitAction(act, overwrite = 0) {
    const rankInput = document.getElementById('rank-input');
    const rootInput = document.getElementById('root-input');
    const targetFolderNotice = document.getElementById('target-folder-notice');

    if (rankInput.value === '' && rootInput.value === '0') {
        targetFolderNotice.style.display = 'inline-block';
        return false;
    }

    document.getElementById('action-input').value = act;

    const form = document.getElementById('file-action-form');
    const formData = new FormData(form);

    if (overwrite === 1) {
        formData.append('overwrite', '1');
    }

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'warning' && data.requires_overwrite_confirmation) {
            const confirmation = confirm(data.message);
            if (confirmation) {
                submitAction(act, 1);
            }
        } else if (data.status === 'success') {
            if (data.message) {
                alert(data.message);
            }
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            if (data.message) {
                alert(data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('{LANG.unknow_error}');
    });

    return false;
}
</script>
<!-- END: main -->