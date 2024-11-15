<!-- BEGIN: main -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/codemirror.min.css">
<!-- CodeMirror Theme (Optional) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.5/theme/monokai.min.css">
<style>
    body {
        font-family: Arial, sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;
    }
    .editor-container {
        width: 80%;
        margin: 0 auto;
    }
    .editor-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    #editor {
        height: 500px;
    }
</style>
<!-- BEGIN: message -->
<div class="alert alert-warning">{MESSAGE}</div>
<!-- END: message -->
<div class="editor-container">
    <form action="" method="post">
        <div class="form-group">
            <label>{FILE_NAME}</label>
            <textarea id="editor" class="form-control" name="file_content"
                style="width: 500px; height: 300px;">{FILE_CONTENT}</textarea>
            <input type="hidden" name="file_id" value="{FILE_ID}">
        </div>
        <a href="{url_view}" class="btn btn-warning">
            <i class="fa fa-chevron-circle-left"></i> Quay lại
        </a>
        <button type="submit" class="btn btn-primary">Lưu</button>
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