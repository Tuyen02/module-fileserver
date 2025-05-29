<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->

<h1>Nội dung file nén: {FILE_NAME}</h1>

<!-- BEGIN: tree_html -->
<div class="tree well">{TREE_HTML}</div>
<!-- END: tree_html -->

<!-- BEGIN: no_content -->
<div class="alert alert-warning">
    {LANG.download_to_view}
</div>
<!-- END: no_content -->

<form method="post" id="unzipForm">
    <button type="button" class="btn btn-info" id="backButton">
        <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
    </button>
    <!-- BEGIN: can_unzip -->   
    <button type="submit" name="action" value="unzip" class="btn btn-primary">
        {LANG.unzip}
    </button>
    <!-- END: can_unzip -->
</form>

<style>
    .tree {
        list-style-type: none;
        padding-left: 20px;
    }
    .tree li {
        margin: 5px 0;
        cursor: pointer;
    }
    .tree li span {
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .tree ul {
        margin-left: 20px;
        padding-left: 10px;
        border-left: 1px dashed #ccc;
    }
    .tree i {
        font-size: 14px;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        $("#backButton").click(function () {
            window.history.back();
        });

        $("#unzipForm").on("submit", function(e) {
            e.preventDefault();
            
            $.ajax({
                type: "POST",
                url: window.location.href,
                data: {
                    action: "unzip"
                },
                dataType: "json",
                success: function(response) {
                    if (response.status === "success") {
                        alert(response.message);
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        }
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    console.log("Lỗi giải nén:", xhr.responseText);
                    alert("Đã xảy ra lỗi khi giải nén file");
                }
            });
        });
    });
</script>
<!-- END: main -->
