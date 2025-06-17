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

    iframe, .file-content-container {
        width: 100%;
        height: 500px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fff;
        overflow-y: auto;
        box-sizing: border-box;
        box-shadow: inset 0 0 5px rgba(0, 0, 0, 0.05);
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
        padding: 20px;
        font-family: 'Times New Roman', Times, serif;
        font-size: 16px;
        line-height: 1.6;
        color: #333;
    }

    .word-content h1, .word-content h2, .word-content h3 {
        margin: 10px 0;
        font-weight: bold;
    }

    .word-content p {
        margin: 10px 0;
    }

    .word-content table, .word-content th, .word-content td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    .excel-content {
        padding: 10px;
        overflow: auto;
    }

    .sheet-tabs {
        display: flex;
        border-bottom: 1px solid #ddd;
        margin-bottom: 10px;
        overflow-x: auto;
        white-space: nowrap;
    }

    .tab {
        padding: 10px 20px;
        cursor: pointer;
        background: #f2f2f2;
        margin-right: 5px;
        border-radius: 4px 4px 0 0;
        border: 1px solid #ddd;
        border-bottom: none;
    }

    .tab.active {
        background: #fff;
        border-bottom: 2px solid #007bff;
    }

    .tab-content {
        display: none;
        overflow: auto;
    }

    .tab-content.active {
        display: block;
    }

    .excel-table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 14px;
    }

    .excel-table td {
        border: 1px solid #ddd;
        padding: 8px;
        min-width: 100px;
        position: relative;
    }

    .excel-table td:hover {
        background-color: #f8f9fa;
    }

    .excel-table tr:first-child td {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    @media (max-width: 768px) {
        .editor-container {
            width: 90%;
            padding: 10px;
        }

        iframe, .file-content-container, textarea, .CodeMirror {
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
            <!-- BEGIN: pdf -->
            <div id="pdfContainer">
                <iframe src="{FILE_CONTENT}"></iframe>
            </div>
            <!-- END: pdf -->
            <!-- BEGIN: docx -->
            <div id="docxContainer" class="file-content-container">
                <div id="docx-output" class="word-content">Đang tải nội dung file Word...</div>
            </div>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.9.0/mammoth.browser.min.js"></script>
            <script>
            window.addEventListener('DOMContentLoaded', function() {
                fetch("{FILE_URL}")
                    .then(response => {
                        if (!response.ok) throw new Error('Không tải được file từ server: ' + response.status);
                        return response.arrayBuffer();
                    })
                    .then(arrayBuffer => mammoth.convertToHtml({ 
                        arrayBuffer: arrayBuffer,
                        styleMap: [
                            "p[style-name='Heading 1'] => h1:fresh",
                            "p[style-name='Heading 2'] => h2:fresh",
                            "p[style-name='Heading 3'] => h3:fresh",
                            "p => p:fresh",
                            "b => strong",
                            "i => em",
                            "table => table",
                            "tr => tr",
                            "td => td"
                        ]
                    }))
                    .then(result => {
                        document.getElementById('docx-output').innerHTML = result.value;
                    })
                    .catch(err => {
                        document.getElementById('docx-output').innerHTML = "Không thể hiển thị file Word.<br><small>" + err.message + "</small>";
                        console.error('Lỗi khi đọc file docx:', err);
                    });
            });
            </script>
            <!-- END: docx -->
            <!-- BEGIN: xlsx -->
            <div id="xlsxContainer" class="file-content-container">
                <div id="xlsx-output" class="excel-content">
                    <div id="tabs" class="sheet-tabs"></div>
                    <div id="tab-content"></div>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    try {
                        const sheetData = JSON.parse('{FILE_CONTENT}');
                        const tabsContainer = document.getElementById('tabs');
                        const contentContainer = document.getElementById('tab-content');
                        
                        Object.keys(sheetData).forEach((sheetName, index) => {
                            const tab = document.createElement('div');
                            tab.className = 'tab' + (index === 0 ? ' active' : '');
                            tab.textContent = sheetName;
                            tab.onclick = () => {
                                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                                tab.classList.add('active');
                                document.getElementById('sheet-' + index).classList.add('active');
                            };
                            tabsContainer.appendChild(tab);

                            const content = document.createElement('div');
                            content.id = 'sheet-' + index;
                            content.className = 'tab-content' + (index === 0 ? ' active' : '');
                            
                            const table = document.createElement('table');
                            table.className = 'excel-table';
                            
                            sheetData[sheetName].forEach((row, rowIndex) => {
                                const tr = document.createElement('tr');
                                row.forEach((cell, cellIndex) => {
                                    const td = document.createElement('td');
                                    td.textContent = cell !== null ? cell : '';
                                    td.setAttribute('data-row', rowIndex);
                                    td.setAttribute('data-col', cellIndex);
                                    tr.appendChild(td);
                                });
                                table.appendChild(tr);
                            });
                            
                            content.appendChild(table);
                            contentContainer.appendChild(content);
                        });
                    } catch (err) {
                        document.getElementById('xlsx-output').innerHTML = "Không thể hiển thị file Excel.<br><small>" + err.message + "</small>";
                        console.error('Lỗi khi đọc file xlsx:', err);
                    }
                });
            </script>
            <!-- END: xlsx -->
            <!-- BEGIN: excel -->
            <textarea id="editor" class="form-control {DISABLE_CLASS}" name="file_content"
                {DISABLE_ATTR}>{FILE_CONTENT}</textarea>
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
    var textarea = document.getElementById('editor');
    if (textarea) {
        const editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            mode: 'htmlmixed',
            theme: 'monokai',
            readOnly: { READONLY },
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

    function changeMode() {
        const language = document.getElementById('language').value;
        editor.setOption('mode', language);
    }

    function saveContent() {
        const content = editor.getValue();
        console.log("Content to save:", content);
        alert("Content saved successfully (Check console log)");
    }
</script>
<!-- END: main -->