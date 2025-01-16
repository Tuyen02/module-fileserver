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
        <h3>Chọn nhóm người được phép truy cập</h3>
        <div class="form-group">
            <div>
                <div>
                    <!-- BEGIN: loop -->
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                <input type="checkbox" name="group_ids[]" value="{ROW.group_id}" id="group_{ROW.group_id}">
                                <label for="group_{ROW.group_id}">{ROW.title}</label>
                            </div>
                        </div>
                    </div>
                    <!-- END: loop -->
                </div>
            </div>
            <br>
            <button type="submit" class="btn btn-primary" value="1" name="submit">Xác nhận</button>
        </div>
    </form>
</div>
<!-- END: main -->