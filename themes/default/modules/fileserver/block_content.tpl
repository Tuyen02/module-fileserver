<!-- BEGIN: main -->
<div class="tree">
    {TREE}
</div>
<style>
    .tree {
            list-style-type: none;
            padding: 0 5px 0 5px;
            margin: 0 5px 0 5px;
            border-right: 1px solid #ccc;
            overflow-x: auto;
            word-break: break-all;
            white-space: pre-line;
        }

        .tree li {
            margin: 5px 0;
            cursor: pointer;
        }

        .tree li a {
            color: inherit;
            text-decoration: none;
        }

        .tree li a:hover {
            color: #007bff;
        }

        .tree li.active {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 2px 5px;
        }

        .tree li.active>a {
            color: #007bff;
            font-weight: bold;
        }

        .tree li span {
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .tree ul {
            margin-left: 5px;
            padding-left: 5px;
            border-left: 1px dashed #ccc;
        }

        .tree i {
            font-size: 14px;
        }
</style>
<!-- END: main -->