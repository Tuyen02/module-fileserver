<!-- BEGIN: main -->
<div class="row">
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
        method="post" class="col-md-12 confirm-reload">
        <h2>{LANG.main_title}</h2>
        <div class="form-group">
            <label for="group_ids">{LANG.group_user}</label>
            <select name="group_ids[]" id="group_ids" class="form-control" multiple style="width: 100%;">
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
        var $select = $('#group_ids');
        var optionCount = $select.find('option').length;

        $select.select2({
            placeholder: "{LANG.choose_group}",
            allowClear: true
        });
    });
</script>

<!-- END: main -->