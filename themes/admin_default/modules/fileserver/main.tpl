<!-- BEGIN: main -->
<div class="container">
    <!-- BEGIN: message -->
    <div class="alert alert-danger">
        {MESSAGE}
    </div>
    <!-- END: message -->
    <form
        action="{NV_BASE_ADMINURL}index.php?{NV_LANG_VARIABLE}={NV_LANG_DATA}&amp;{NV_NAME_VARIABLE}={MODULE_NAME}&amp;{NV_OP_VARIABLE}={OP}"
        method="post" class="confirm-reload">
        <div class="form-group">
            <div>
                <label><strong>Nhập mã nhóm:</strong></label>
                <input class="form-control" type="text" value="{POST.config_value}" name="config_value">
            </div>
            <br>
            <button type="submit" class="btn btn-primary" value="1" name="submit">Xác nhận</button>
        </div>
    </form>
</div>
<!-- END: main -->