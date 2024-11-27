<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-warning">{MESSAGE}</div>
<!-- END: message -->
<h1>Nội dung file nén </h1>
<form method="post">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tên</th>
                <th>Size</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: file -->
            <tr>
                <td><i class="fa {FILE.file_type}"></i> {FILE.file_name}</td>
                <td>{FILE.file_size}</td>
            </tr>
            <!-- END: file -->
        </tbody>
    </table>
    <button type="button" class="btn btn-secondary" id="backButton">
        <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
    </button>
    <button type="submit" name="action" value="unzip" class="btn btn-primary">{LANG.unzip}</button>
</form>
<p></p>
<script>
    $(document).ready(function () {
    $("#backButton").on("click", function (e) {
        e.preventDefault();
        window.history.back();
    });
});
</script>
<!-- END: main-->