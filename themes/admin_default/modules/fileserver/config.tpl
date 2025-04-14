<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-{MESSAGE_TYPE}">
    {MESSAGE}
</div>
<!-- END: message -->
<form action="{NV_BASE_ADMINURL}index.php?{NV_NAME_VARIABLE}={MODULE_NAME}&{NV_OP_VARIABLE}={OP}" method="post">
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <caption>{LANG.config}</caption>
            <tbody>
                <tr class="row">
                    <td class="col-sm-6">{LANG.use_captcha}</td>
                    <td class="col-sm-18">
                        <label>
                            <input type="checkbox" name="use_captcha" id="use_captcha" value="1"
                                {USE_CAPTCHA_CHECKED} />
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <caption>{LANG.config_elastic}</caption>
            <tbody>
                <tr>
                    <td>{LANG.use_elastic}</td>
                    <td>
                        <label>
                            <input type="checkbox" name="use_elastic" id="use_elastic" value="1" {USE_ELASTIC_CHECKED}
                                onchange="toggleElasticFields()" /> {LANG.enable_elastic}
                        </label>
                    </td>
                </tr>
                <tr>
                    <td>{LANG.elas_host}</td>
                    <td><input type="text" name="elas_host" id="elas_host" value="{CONFIG.elas_host}"
                            class="form-control" /></td>
                </tr>
                <tr>
                    <td>{LANG.elas_port}</td>
                    <td><input type="text" name="elas_port" id="elas_port" value="{CONFIG.elas_port}"
                            class="form-control" /></td>
                </tr>
                <tr>
                    <td>{LANG.elas_user}</td>
                    <td><input type="text" name="elas_user" id="elas_user" value="{CONFIG.elas_user}"
                            class="form-control" /></td>
                </tr>
                <tr>
                    <td>{LANG.elas_pass}</td>
                    <td><input type="password" name="elas_pass" id="elas_pass" value="{CONFIG.elas_pass}"
                            class="form-control" /></td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="text-center">
        <input type="hidden" name="submit" value="1" />
        <input type="submit" value="{LANG.save}" class="btn btn-primary" />
        <input type="button" value="{LANG.sync_elastic}" id="sync_elastic_btn" class="btn btn-info"
            onclick="syncElastic()" disabled />
    </div>
</form>

<script>
    function toggleElasticFields() {
        var useElastic = document.getElementById('use_elastic').checked;
        document.getElementById('elas_host').disabled = !useElastic;
        document.getElementById('elas_port').disabled = !useElastic;
        document.getElementById('elas_user').disabled = !useElastic;
        document.getElementById('elas_pass').disabled = !useElastic;
        document.getElementById('sync_elastic_btn').disabled = !useElastic;
    }

    function syncElastic() {
        if (confirm('{LANG.confirm_sync_elastic}')) {
            var form = document.createElement('form');
            form.method = 'post';
            form.action = '{NV_BASE_ADMINURL}index.php?{NV_NAME_VARIABLE}={MODULE_NAME}&{NV_OP_VARIABLE}={OP}';

            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'sync_elastic';
            input.value = '1';
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleElasticFields();
    });
</script>
<!-- END: main -->