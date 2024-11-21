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
                    <td><input type="checkbox" name="owner_read" value="1" {OWNER_READ}></td>
                    <td><input type="checkbox" name="group_read" value="1" {GROUP_READ}></td>
                    <td><input type="checkbox" name="other_read" value="1" {OTHER_READ}></td>
                </tr>
                <tr>
                    <td>Write</td>
                    <td><input type="checkbox" name="owner_write" value="1" {OWNER_WRITE}></td>
                    <td><input type="checkbox" name="group_write" value="1" {GROUP_WRITE}></td>
                    <td><input type="checkbox" name="other_write" value="1" {OTHER_WRITE}></td>
                </tr>
                <tr>
                    <td>Execute</td>
                    <td><input type="checkbox" name="owner_execute" value="1" {OWNER_EXECUTE}></td>
                    <td><input type="checkbox" name="group_execute" value="1" {GROUP_EXECUTE}></td>
                    <td><input type="checkbox" name="other_execute" value="1" {OTHER_EXECUTE}></td>
                </tr>
            </tbody>
        </table>
        <div class="text-end">
            <button type="button" class="btn btn-outline-primary" onclick="window.history.back();">Cancel</button>
            <button type="submit" name="submit" class="btn btn-success">Change</button>
        </div>
    </form>
</div>
<!-- END: main -->