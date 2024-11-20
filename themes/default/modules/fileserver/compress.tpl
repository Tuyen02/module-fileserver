<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-warning">{MESSAGE}</div>
<!-- END: message -->
<h1>Nội dung file nén</h1>
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
                <td><i class="fa {CONTENT.TYPE}"></i> {CONTENT.FILENAME}</td>
                <td>{CONTENT.SIZE}</td>
            </tr>
            <!-- END: file -->
        </tbody>
    </table>
    <button type="submit" name="action" value="unzip" class="btn btn-primary">Giải nén</button>
</form>
<p></p>
<!-- END: main-->