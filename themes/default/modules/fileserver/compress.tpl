<!-- BEGIN: main -->
<!-- BEGIN: message -->
<div class="alert {MESSAGE_CLASS}">{MESSAGE}</div>
<!-- END: message -->
<h1>Nội dung file nén</h1>
<div class="tree well">
    {TREE_HTML}
</div>
<form method="post">
    <button type="button" class="btn btn-secondary" id="backButton">
        <i class="fa fa-chevron-circle-left" aria-hidden="true"></i> {LANG.back_btn}
    </button>
    <button type="submit" name="action" value="unzip" class="btn btn-primary">{LANG.unzip}</button>
</form>
<style>
    .tree { list-style-type: none; padding-left: 20px; }
    .tree li { margin: 5px 0; cursor: pointer; }
    .tree li span { font-weight: bold; display: flex; align-items: center; gap: 5px; }
    .tree ul { margin-left: 20px; padding-left: 10px; border-left: 1px dashed #ccc; }
    .tree i { font-size: 14px; }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#backButton').click(function() {
            window.history.back();
        });
    });
</script>
<!-- END: main -->