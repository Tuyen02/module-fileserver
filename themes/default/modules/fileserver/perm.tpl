<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->

<div class="container mt-4">
    <h3>{LANG.perm_title}: <strong>{FILE_NAME}</strong></h3>
    <p>{LANG.f_path}: <code>{FILE_PATH}</code></p>
    <form id="changePermissionsForm" method="post" action="">
        <input type="hidden" name="file_id" value="{FILE_ID}">
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">{LANG.group}</th>
                    <th class="text-center">{LANG.other}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{LANG.read}</td>
                    <td><input type="checkbox" name="group_read" value="1" {GROUP_READ_CHECKED}></td>
                    <td><input type="checkbox" name="other_read" value="1" {OTHER_READ_CHECKED}></td>
                </tr>
                <tr>
                    <td>{LANG.write}</td>
                    <td><input type="checkbox" name="group_write" value="2" {GROUP_WRITE_CHECKED}></td>
                    <td><input type="checkbox" name="other_write" value="2" {OTHER_WRITE_CHECKED}></td>
                </tr>
            </tbody>
        </table>
        <button type="button" class="btn btn-secondary" id="backButton">
            <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
        </button>
        <button type="submit" name="submit" value="1" class="btn btn-success">{LANG.save_btn}</button>
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