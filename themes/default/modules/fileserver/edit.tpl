<!-- BEGIN: main -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/monokai.min.css">
<style>
    .editor-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    #editor {
        height: auto;
        min-height: 500px;
    }

    .CodeMirror {
        height: 500px !important;
        font-size: 14px;
        line-height: 1.6;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    textarea {
        width: 100%;
        height: 500px;
        font-size: 14px;
        line-height: 1.6;
    }

    .readonly-editor {
        opacity: 0.7;
        pointer-events: none;
    }

    @media (max-width: 768px) {
        .editor-container {
            width: 90%;
            padding: 10px;
        }
        textarea, .CodeMirror {
            height: 300px !important;
        }
    }
</style>

<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->

<!-- BEGIN: back -->
<div class="mb-3">
    <a href="{BACK_URL}" class="btn btn-info">
        <i class="fa fa-chevron-circle-left"></i> {LANG.back_btn}
    </a>
</div>
<!-- END: back -->

<div class="editor-container">
    <form action="" method="post">
        <div class="form-group">
            <label>{LANG.f_name}: {FILE_NAME}</label>
            <!-- BEGIN: text -->
            <textarea id="editor" class="form-control {DISABLE_CLASS}" name="file_content" {DISABLE_ATTR}>{FILE_CONTENT}</textarea>
            <!-- END: text -->
            <input type="hidden" name="file_id" value="{FILE_ID}">
        </div>
        <!-- BEGIN: can_save -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary">{LANG.save_btn}</button>
        </div>
        <!-- END: can_save -->
        <!-- BEGIN: cannt_save -->
        <div class="alert alert-warning text-center">
            <a href="{BASE_URL}&amp;{NV_OP_VARIABLE}={OP}&amp;file_id={FILE_ID}&amp;download=1&amp;token={TOKEN}" class="text-secondary">
                <i class="fa fa-download"></i> {LANG.download_to_view}
            </a>
        </div>
        <!-- END: cannt_save -->
    </form>
</div>
<hr>

<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/mode/htmlmixed/htmlmixed.min.js"></script>
<script>
    $(document).ready(function () {
        $("#backButton").on("click", function (e) {
            e.preventDefault();
            window.history.back();
        });
    });
    var textarea = document.getElementById('editor');
    if (textarea) {
        const editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: 'htmlmixed',
            theme: 'monokai',
            readOnly: '{READONLY}' == 'true',
            lineWrapping: true,
            viewportMargin: Infinity,
            autoCloseTags: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 4,
            tabSize: 4,
            indentWithTabs: false
        });
    }
</script>
<!-- END: main -->