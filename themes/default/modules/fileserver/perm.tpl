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
                    <td>{LANG.permission_level}</td>
                    <td>
                        <select name="group_permission" class="form-control">
                            <option value="1" {GROUP_LEVEL_1}>{LANG.level_1}</option>
                            <option value="2" {GROUP_LEVEL_2}>{LANG.level_2}</option>
                            <option value="3" {GROUP_LEVEL_3}>{LANG.level_3}</option>
                        </select>
                    </td>
                    <td>
                        <select name="other_permission" class="form-control">
                            <option value="1" {OTHER_LEVEL_1}>{LANG.level_1}</option>
                            <option value="2" {OTHER_LEVEL_2}>{LANG.level_2}</option>
                        </select>
                    </td>
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