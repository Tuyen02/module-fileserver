<!-- BEGIN: main -->
<div class="container mt-4 mb-5 pb-5">
    <h1 class="text-center">{LANG.module_title}</h1>
    <br>
    <!-- BEGIN: error -->
    <div class="alert alert-warning">{ERROR}</div>
    <!-- END: error -->
    <!-- BEGIN: success -->
    <div class="alert alert-warning">{SUCCESS}</div>
    <!-- END: success -->
    <form action="" method="get" id="searchForm" class="form-inline my-2 my-lg-0">
        <input type="hidden" name="lev" value="{ROW.lev}">
        <input type="text" class="form-control" placeholder="{LANG.search}" id="searchInput" name="search"
            value="{SEARCH_TERM}">
        <select class="form-control ml-2" name="search_type">
            <option value="all" {SELECTED_ALL}>{LANG.all}</option>
            <option value="file" {SELECTED_FILE}>{LANG.file}</option>
            <option value="folder" {SELECTED_FOLDER}>{LANG.folder}</option>
        </select>
        <button type="submit" class="btn btn-primary ml-2">{LANG.search_btn}</button>
    </form>

    <br>

    <hr>
    <table class="table table-hover">
        <thead class="thead-dark">
            <tr>
                <th scope="col"><input class="form-check-input" type="checkbox" value="" id="defaultCheck1"></th>
                <th scope="col">{LANG.f_name}</th>
                <th scope="col">{LANG.f_size}</th>
                <th scope="col">{LANG.created_at}</th>
                <!-- <th scope="col">{LANG.module_title}</th>
                <th scope="col">{LANG.module_title}</th> -->
                <th scope="col">{LANG.option}</th>
            </tr>
        </thead>
        <tbody>
            <!-- BEGIN: file_row -->
            <tr>
                <td><input type="checkbox" name="files[]" value="{ROW.file_id}" data-checksess="{ROW.checksess}"></td>
                <td>
                    <a href="{VIEW}">
                        <i class="fa {ROW.icon_class}" aria-hidden="true"></i>
                        {ROW.file_name}
                    </a>
                </td>
                <td>{ROW.file_size}</td>
                <td>{ROW.created_at}</td>
                <!-- <td>
                    <a href="{ROW.url_perm}">{ROW.permissions}</i>
                    </a>
                </td>
                <td>{ROW.username} {ROW.uploaded_by}</td> -->
                <td>
                </td>
            </tr>
            <!-- END: file_row -->
        </tbody>
        <tfoot>
            <tr>
                <td class="gray" colspan="7">
                    <strong>{LANG.full_size}</strong>
                    <span class="badge text-bg-light border-radius-0">{ROW.total_size}</span>
                    <strong>{LANG.file}</strong>
                    <span class="badge badge-secondary">{ROW.total_files}</span>
                    <strong>{LANG.folder}</strong>
                    <span class="badge badge-secondary">{ROW.total_folders}</span>
                </td>
            </tr>
        </tfoot>
    </table>
</div>
<!-- END: main -->