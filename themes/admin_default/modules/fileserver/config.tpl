<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert alert-{MESSAGE_TYPE}">
    {MESSAGE}
</div>
<!-- END: message -->
<form action="{NV_BASE_ADMINURL}index.php?{NV_NAME_VARIABLE}={MODULE_NAME}&{NV_OP_VARIABLE}={OP}" method="post">
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <caption>{LANG.config_elastic}</caption>
            <colgroup>
                <col style="width: 40%" />
                <col style="width: 60%" />
            </colgroup>
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
    </div>
</form>

<script>
    function toggleElasticFields() {
        var useElastic = document.getElementById('use_elastic').checked;
        var elasticFields = ['elas_host', 'elas_port', 'elas_user', 'elas_pass'];

        elasticFields.forEach(function (field) {
            document.getElementById(field).disabled = !useElastic;
        });

    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleElasticFields();
    });
</script>
<!-- END: main -->