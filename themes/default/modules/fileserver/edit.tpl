<!-- BEGIN: main -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/monokai.min.css">
<style>
    body {
        font-family: Arial, sans-serif;
        /* display: flex; */
        /* flex-direction: column; */
        align-items: center;
        /* padding: 20px; */
    }
    .editor-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    #editor {
        height: auto;
        min-height: 500px;
    }
    .CodeMirror {
        height: 500 !important;
        font-size: 14px;
        line-height: 1.6;
    }
    iframe {
        width: 100%;
        height: 500px;
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
    .word-content {
        width: 100%;
        height: 500px;
        border: 1px solid #ccc;
        padding: 20px;
        overflow-y: auto;
        background: #fff;
    }
    @media (max-width: 768px) {
        .editor-container {
            width: 90%;
        }
        iframe, textarea, .word-content {
            height: 300px;
        }
        .CodeMirror {
            height: 300px !important;
        }
    }
</style>

<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->

<!-- BEGIN: back -->
<a href="{BACK_URL}" class="btn btn-info">
    <i class="fa fa-chevron-circle-left"></i> {LANG.back_btn}
</a>
<!-- END: back -->

<div class="editor-container">
    <form action="" method="post">
        <div class="form-group">
            <label>{LANG.f_name}: {FILE_NAME}</label>
            <!-- BEGIN: text -->
            <textarea id="editor" class="form-control {DISABLE_CLASS}" name="file_content" {DISABLE_ATTR}>{FILE_CONTENT}</textarea>
            <!-- END: text -->
            <!-- BEGIN: pdf -->
            <div id="pdfContainer">
                <iframe src="{FILE_CONTENT}"></iframe>
            </div>
            <!-- END: pdf -->
            <!-- BEGIN: docx -->
            <div class="word-content {DISABLE_CLASS}" {DISABLE_ATTR}>
                {FILE_CONTENT}
            </div>
            <!-- END: docx -->
            <!-- BEGIN: excel -->
            <textarea id="editor" class="form-control {DISABLE_CLASS}" name="file_content" {DISABLE_ATTR}>{FILE_CONTENT}</textarea>
            <!-- END: excel -->
            <input type="hidden" name="file_id" value="{FILE_ID}">
        </div>
        <!-- BEGIN: can_save -->
        <div class="text-center">
            <button type="submit" class="btn btn-primary">{LANG.save_btn}</button>
        </div>
        <!-- END: can_save -->
        <!-- BEGIN: cannt_save -->
        <div class="alert alert-warning text-center">{LANG.download_to_view}</div>
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
    const editor = CodeMirror.fromTextArea(document.getElementById('editor'), {
        lineNumbers: true,
        mode: 'css',
        theme: 'monokai',
        readOnly: {READONLY},
        lineWrapping: true,
        viewportMargin: Infinity
    });

    function changeMode() {
        const language = document.getElementById('language').value;
        editor.setOption('mode', language);
    }

    function saveContent() {
        const content = editor.getValue();
        console.log("Content to save:", content);
        // Here you can implement save functionality, e.g., send content to server
        alert("Content saved successfully (Check console log)");
    }
</script>
<!-- END: main -->