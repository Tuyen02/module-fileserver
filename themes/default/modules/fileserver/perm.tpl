<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-warning">{MESSAGE}</div>
<!-- END: message -->

<div class="container mt-4">
    <h3>Change Permissions for: <strong>{FILE_NAME}</strong></h3>
    <p>Full path: <code>{FILE_PATH}</code></p>
    <form id="changePermissionsForm" method="post" action="">
        <input type="hidden" name="file_id" value="{FILE_ID}">
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">Owner</th>
                    <th class="text-center">Group</th>
                    <th class="text-center">Other</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Read</td>
                    <td><input type="checkbox" name="owner_read" value="1" {OWNER_READ_CHECKED}></td>
                    <td><input type="checkbox" name="group_read" value="1" {GROUP_READ_CHECKED}></td>
                    <td><input type="checkbox" name="other_read" value="1" {OTHER_READ_CHECKED}></td>
                </tr>
                <tr>
                    <td>Write</td>
                    <td><input type="checkbox" name="owner_write" value="1" {OWNER_WRITE_CHECKED}></td>
                    <td><input type="checkbox" name="group_write" value="1" {GROUP_WRITE_CHECKED}></td>
                    <td><input type="checkbox" name="other_write" value="1" {OTHER_WRITE_CHECKED}></td>
                </tr>
                <tr>
                    <td>Execute</td>
                    <td><input type="checkbox" name="owner_execute" value="1" {OWNER_EXECUTE_CHECKED}></td>
                    <td><input type="checkbox" name="group_execute" value="1" {GROUP_EXECUTE_CHECKED}></td>
                    <td><input type="checkbox" name="other_execute" value="1" {OTHER_EXECUTE_CHECKED}></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" id="backButton">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> Quay lại
        </button>
        <button type="submit" name="submit" value="1" class="btn btn-success">Save</button>
    </form>
</div>
<br>
<script>
        $(document).ready(function () {
        $("#backButton").on("click", function (e) {
            e.preventDefault();
            window.history.back();
        });
    });
</script>
<!-- END: main -->