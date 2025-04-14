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
    iframe {
        width: 100%;
        height: 500px;
    }
    textarea {
        width: 100%;
        height: auto;
        min-height: 300px;
    }
    @media (max-width: 768px) {
        .editor-container {
            width: 90%;
        }
        iframe {
            height: 300px;
        }
        textarea {
            min-height: 200px;
        }
    }
</style>

<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->

<div class="editor-container">
    <form action="" method="post">
        <div class="form-group">
            <label>{FILE_NAME}</label>
            <!-- BEGIN: text -->
            <textarea id="editor" class="form-control" name="file_content">{FILE_CONTENT}</textarea>
            <!-- END: text -->
            <!-- BEGIN: pdf -->
            <div id="pdfContainer">
                <iframe src="{FILE_CONTENT}"></iframe>
            </div>
            <!-- END: pdf -->
            <!-- BEGIN: docx -->
            <textarea id="editor" class="form-control" name="file_content">{FILE_CONTENT}</textarea>
            <!-- END: docx -->
            <!-- BEGIN: excel -->
            <textarea id="editor" class="form-control" name="file_content">{FILE_CONTENT}</textarea>
            <!-- END: excel -->
            <input type="hidden" name="file_id" value="{FILE_ID}">
        </div>
        <a href="{url_view}" class="btn btn-info">
            <i class="fa fa-chevron-circle-left"></i> {LANG.back_btn}
        </a>
        <button type="submit" class="btn btn-primary">{LANG.save_btn}</button>
    </form>
</div>

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
        mode: 'css', // Default mode
        theme: 'monokai' // Change to any theme you prefer
    });

    // Function to change syntax highlighting mode
    function changeMode() {
        const language = document.getElementById('language').value;
        editor.setOption('mode', language);
    }

    // Function to save content
    function saveContent() {
        const content = editor.getValue();
        console.log("Content to save:", content);
        // Here you can implement save functionality, e.g., send content to server
        alert("Content saved successfully (Check console log)");
    }
</script>
<!-- END: main -->