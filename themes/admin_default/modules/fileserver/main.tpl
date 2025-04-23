<!-- BEGIN: main -->
<div class="card" style="width: 500px;">
    <!-- BEGIN: message -->
    <div class="alert alert-success">
        {MESSAGE}
    </div>
    <!-- END: message -->
    <!-- BEGIN: error -->
    <div class="alert alert-danger">
        {ERROR}
    </div>
    <!-- END: error -->
    <form action="{NV_BASE_ADMINURL}index.php?{NV_LANG_VARIABLE}={NV_LANG_DATA}&amp;{NV_NAME_VARIABLE}={MODULE_NAME}&amp;{NV_OP_VARIABLE}={OP}"
        method="post" class="confirm-reload">
        <h2>{LANG.main_title}</h2>
        <div class="form-group">
            <label for="group_ids">{LANG.group_user}</label>
            <select name="group_ids[]" id="group_ids" class="form-control select2" multiple>
                <!-- BEGIN: loop -->
                <option value="{ROW.group_id}" {CHECKED}>{ROW.title}</option>
                <!-- END: loop -->
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" value="1" name="submit">{LANG.submit}</button>
        </div>
    </form>
</div>
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        var selectedGroups = $('#group_ids').val();
        var placeholderText = selectedGroups.length > 0 ? '' : "{LANG.choose_group}";

        $('.select2').select2({
            placeholder: placeholderText,
            allowClear: true
        });
    });
</script>

<!-- END: main -->