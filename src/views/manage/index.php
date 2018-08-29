<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Manage DBs';
/*
$this->registerJsFile(Yii::getAlias('@web') . "/js/jquery-2.2.4.min.js", ["position" => $this::POS_HEAD]);
$this->registerJsFile(Yii::getAlias('@web') . "/js/codemirror.js", ["position" => $this::POS_HEAD]);
$this->registerCssFile(Yii::getAlias('@web') . "/css/codemirror.css", ["position" => $this::POS_HEAD]);
$this->registerJsFile(Yii::getAlias('@web') . "/js/mergely.js", ["position" => $this::POS_HEAD]);
$this->registerCssFile(Yii::getAlias('@web') . "/css/mergely.css", ["position" => $this::POS_HEAD]);
$this->registerJsFile(Yii::getAlias('@web') . "/js/bootstrap.js", ["position" => $this::POS_HEAD]);
$this->registerCssFile(Yii::getAlias('@web') . "/css/bootstrap.css", ["position" => $this::POS_HEAD]);
$this->registerCssFile(Yii::getAlias('@web') . "/css/font-awesome.min.css", ["position" => $this::POS_HEAD]);
$this->registerCssFile(Yii::getAlias('@web') . "/css/general.css", ["position" => $this::POS_HEAD]);

*/

$dbmanageasset = \DbTools\DbManageAsset::register($this);


$this->registerJsFile('https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js', ["position" => $this::POS_END, "depends"=>[yii\web\JqueryAsset::className()]]);
$this->registerCssFile('https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('https://cdn.datatables.net/1.10.15/js/dataTables.bootstrap.min.js', ["position" => $this::POS_END, "depends"=>[yii\web\JqueryAsset::className()]]);
$this->registerCssFile('https://cdn.datatables.net/1.10.15/css/dataTables.bootstrap.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('https://cdn.datatables.net/select/1.2.2/js/dataTables.select.min.js', ["position" => $this::POS_END, "depends"=>[yii\web\JqueryAsset::className()]]);
$this->registerCssFile('https://cdn.datatables.net/select/1.2.2/css/select.bootstrap.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('https://cdn.datatables.net/scroller/1.4.2/js/dataTables.scroller.min.js', ["position" => $this::POS_END, "depends"=>[yii\web\JqueryAsset::className()]]);
$this->registerCssFile('https://cdn.datatables.net/scroller/1.4.2/css/scroller.dataTables.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('http://www.mergely.com/Mergely/lib/codemirror.js', ["position" => $this::POS_END, "depends"=>[yii\web\JqueryAsset::className()]]);
$this->registerCssFile('http://www.mergely.com/Mergely/lib/codemirror.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('http://www.mergely.com/Mergely/lib/mergely.js', ["position" => $this::POS_END, "depends"=>[yii\web\JqueryAsset::className()]]);
$this->registerCssFile('http://www.mergely.com/Mergely/lib/mergely.css', ["position" => $this::POS_HEAD]);

$statusmap = [
    'ok' =>'success',
    'new' =>'primary',
    'missing' =>'danger',
    'different' =>'warning',
];


$jsonDataTable = [];
foreach ($data['data'] as $group => $items) {
    if (empty($items)) {
        continue;
    }
    if (!is_array($items)) {
        continue;
    }
    //ksort($items);
    //var_dump($data); die();
    foreach ($items as $item => $info) {
        $name = $item;
        $status = '';
        if ($info['createdb'] == $info['createfile']) {
            $status = 'ok';
        }
        else {
            if (empty($info['createdb'])) {
                $status = 'missing';
            }
            else {
                if (empty($info['createfile'])) {
                    $status = 'new';
                }
                else {
                    $status = 'different';
                    //var_dump($info['createdb']);
                    //var_dump($info['createfile']);
                }
            }
        }
        $info['status'] = $status;
        $info['dbconname'] = $active;
        $info['group'] = $group;
        $info['name'] = $name;
        $info['key'] = $active . '|' . $group . '|' . $name;
        //echo("<option class='st_" . $status . "' value='" . $name . "'>&nbsp;&nbsp;" . $name . "</option>\n");
        $jsonDataTable[] = $info;
    }
}

?>

<div class="row">
    <div class="col-md-3">
        <div class="row">
            <ul class="nav nav-tabs">
                <?php
                    foreach ($dbs as $dbconname => $db) {
                        echo '<li'.(($dbconname==$active)?' class="active"':'').'><a href="'.Yii::$app->getUrlManager()->createUrl(['dbtools/manage','dbconname'=>$dbconname]).'">'.$dbconname.'</a></li>';
                    }
                ?>
            </ul>
        </div>
        <fieldset id="templates">
            <div class="col">

                <?php
                foreach ($statusmap as $status=>$style)
                {
                    echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_'.$status,$status,$style,false,'updateItemTable()');
                }
                ?>

            </div>
            <div class="col">
                <table id="itemtable" class="display compact" width="100%" cellspacing="0"></table>
            </div>
        </fieldset>
    </div>
    <div class="col-md-9">
        <ul class="nav nav-tabs" id="contentTab">
            <li class="nav active"><a href="#A" data-toggle="tab">Info</a></li>
            <li class="nav"><a href="#B" data-toggle="tab">Diff</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane area active" id="A">
                <div class="row">
                    <div class="col-md-6"><strong>Item: </strong><span id="itemname">...</span></div>
                    <div class="col-md-3"><strong>Status: </strong><span id="itemstatus">...</span></div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div id="itemwarnings" class="alert alert-danger"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <span id="iteminfo">...</span>
                    </div>
                </div>
            </div>
            <div class="tab-pane area" id="B">
                <div class="row">
                    <div class="col-md-6">
                        <label>SVN</label>
                        <button type="button" id="file2sql" class="btn btn-warning" onclick="file2sql()">file 2 sql</button>
                    </div>
                    <div class="col-md-6">
                        <label>DB</label>
                        <button type="button" id="sql2file" class="btn btn-info" onclick="sql2file()">sql 2 file</button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div id="compare">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

$jsonDataTable = json_encode($jsonDataTable, JSON_PRETTY_PRINT);
$jsonStatusmap = json_encode($statusmap, JSON_PRETTY_PRINT);
$imgRoot = $dbmanageasset->baseUrl.'/css/images';

$imgDanger = Html::img($imgRoot.'/danger.png', [
                                'class' => 'datatableicon',
                                'alt'   => 'warning',
                                'title'   => 'warning',
                            ]);

$urlSql2File = Yii::$app->getUrlManager()->createUrl('dbtools/manage/sql2file');
$urlFile2Sql = Yii::$app->getUrlManager()->createUrl('dbtools/manage/file2sql');

$cbLoad='';
$cbSave='';

foreach ($statusmap as $status=>$style)
{
    $cbLoad.='$("#cb_filter_'.$status.'").prop("checked", settingsLoadBool("filter.'.$status.'", true));';
    $cbSave.='settingsSave("filter.'.$status.'", $("#cb_filter_'.$status.'").prop("checked"));';
}

$this->registerJs(<<<JS
    var table = null;
    var dataTable = $jsonDataTable;
    var statusMap = $jsonStatusmap;
    var CurItem = null;


    var colGroup = 0;
    var colWarning = 1;
    var colStatus = 2;
    var colName = 3;

    /* Custom filtering function which will search data in column four between two values */
    $.fn.dataTable.ext.search.push(
        function( settings, searchData, index, rowData, counter ) {
            if(!$('#cb_filter_'+rowData.status).prop("checked")) return false;
            return true;
        }
    );

    function initItemTable() {

        table = $('#itemtable').DataTable( {
            responsive: false,
            select: true,
            stateSave: true,
            'stateSaveParams': function(settings, data) {
                data.columns.forEach(function (column) {
                    delete column.visible;
                });
            },
            paging: true,
            scrollY:        "600px",
            scroller: true,
            sScrollX: false,
            data: dataTable,
            rowId: 'key',
            columns: [
                { title: "Type", data: "group" },
                { title: "", data: "warnings" },
                { title: "Status", data: "status"},
                { title: "Name", data: "name"}
            ],
            order: [[ colGroup, 'asc' ]],

            displayLength: 25,
            drawCallback: function ( settings ) {
                var api = this.api();
                var rows = api.rows( {page:'current'} ).nodes();
                var last=null;

                api.column(colGroup, {page:'current'} ).data().each( function ( group, i ) {
                    if ( last !== group ) {
                        $(rows).eq( i ).before(
                            '<tr class="group"><td colspan="4"><img src="$imgRoot/'+group+'.png" class="datatableicon">&nbsp;'+group+'</td></tr>'
                        );

                        last = group;
                    }
                } );
            },
            columnDefs: [
                { targets: colGroup, visible: false },
                { targets: colStatus, visible: false },
                {
                    targets: colWarning,
                    width: "10px",
                    className: "dt-center",

                    render: function ( data, type, row ) {
                        if(data) {
                            return '$imgDanger';
                        } else {
                            return '';
                        }
                    }
                }
            ],
            createdRow: function( row, data, dataIndex ) {
                if(!data || !data.status) return;
                $(row).addClass( 'text-'+statusMap[data.status] );
            }
        });

        $('#itemtable tbody').on('click', 'tr', function () {
            var data = table.row( this ).data();
            if(!data) return;
            loadData(data);
        } );


        // Order by the grouping
        $('#itemtable tbody').on( 'click', 'tr.group', function () {
            var currentOrder = table.order()[0];
            if ( currentOrder[0] === colGroup && currentOrder[1] === 'asc' ) {
                table.order( [ colGroup, 'desc' ] ).draw();
            }
            else {
                table.order( [ colGroup, 'asc' ] ).draw();
            }
        } );

        var v = settingsLoad('$active|itemtable');
        if(v) {
            selectItem(v);
        }
    }

    function settingsSave(key,val) {
        localStorage.setItem('db|'+key,val);
    }

    function settingsLoad(key,defval = undefined) {
        var val = localStorage.getItem('db|' + key)

        if (val==null) {
            return defval;
        } else {
            return val;
        }
    }

    function settingsLoadBool(key,defval = undefined) {
        var val = settingsLoad(key,defval);
        if(val == defval) return val;
        return val=='true';
    }

    $(document).ready(function () {
        $('#compare').mergely({
            width: 'auto',
            height: 800,
            cmsettings: {readOnly: true, lineNumbers: true}
        });
        $('#compare').mergely('resize', '');

        $cbLoad

        $('#contentTab a[href="' + settingsLoad('activeTab','#A') + '"]').tab('show');

        initItemTable();

        $('#compare').mergely('resize', '');

    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        settingsSave('activeTab', $(e.target).attr('href'));
        $('#compare').mergely('resize', '');
    });

    function sql2file() {
        if (!CurItem) return;

        var formData = new FormData();
        formData.append('dbconname', CurItem.dbconname);
        formData.append('group', CurItem.group);
        formData.append('name', CurItem.name);

        $.ajax({
            url: '$urlSql2File',
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            success: function () {
                console.log('ok');
                var row = table.row({selected: true});
                if(!row) return;
                var data = row.data();
                if(!data) return;
                $( row.node() ).removeClass( 'text-'+statusMap[data.status] );
                data.createfile = data.createdb;
                data.status = 'ok';
                $( row.node() ).addClass( 'text-'+statusMap[data.status] );
                row.data(data);
                row.invalidate();
                row.draw();
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
                alert('sql2file failed: ' + xhr.responseText);
            }
        });
    }

    function file2sql() {
        if (!CurItem) return;

        var r = confirm("Are you sure you want to execute '"+CurItem.name+"' now?");

        //cancel clicked : stop button default action
        if (r === false) {
            return false;
        }

        var formData = new FormData();
        formData.append('dbconname', CurItem.dbconname);
        formData.append('group', CurItem.group);
        formData.append('name', CurItem.name);

        $.ajax({
            url: '$urlFile2Sql',
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            success: function () {
                console.log('ok');
                var row = table.row({selected: true});
                if(!row) return;
                var data = row.data();
                if(!data) return;
                $( row.node() ).removeClass( 'text-'+statusMap[data.status] );
                data.createdb = data.createfile;
                data.status = 'ok';
                $( row.node() ).addClass( 'text-'+statusMap[data.status] );
                row.data(data);
                row.invalidate();
                row.draw();
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
                alert('file2sql failed: ' + xhr.responseText);
            }
        });
    }

    function selectItem(val) {
        //alert('select-'+val);
        if(val==null) return true;
        var row = table.row('#'+val);
        if(!row) return true;
        table.rows({selected: true}).deselect();
        row.select();
        row.scrollTo();
        loadData(row.data());

        return true;
    }

    function updateItemTable() {
        if(!table) return;

        $cbSave

        table.draw();
    }

    function loadData(data) {
        CurItem = data;

        if(CurItem == undefined) return;

        settingsSave('$active|itemtable', CurItem.key);

        $('#itemname').html(CurItem.name);

        if (CurItem.status) {
            $('#itemstatus').html(CurItem.status);
            $('#itemstatus').attr('class', 'label label-'+statusMap[CurItem.status]);
        }
        else {
            $('#itemstatus').html('');
        }

        if (CurItem.warnings) {
            $('#itemwarnings').show();
            $('#itemwarnings').html(CurItem.warnings);
        }
        else {
            $('#itemwarnings').hide();
            $('#itemwarnings').html('');
        }
        if (CurItem.info) {
            $('#iteminfo').html(CurItem.info);
        }
        else {
            $('#iteminfo').html('');
        }

        if (CurItem.createdb) {
            $('#compare').mergely('rhs', CurItem.createdb);
        } else {
            $('#compare').mergely('rhs', '');
        }
        if (CurItem.createfile) {
            $('#compare').mergely('lhs', CurItem.createfile);
        } else {
            $('#compare').mergely('lhs', '');
        }

        switch(CurItem.status)
        {
            case 'ok':
                $('#file2sql').hide();
                $('#sql2file').hide();
                break;
            case 'missing':
                $('#file2sql').show();
                $('#sql2file').hide();
                break;
            case 'new':
                $('#file2sql').hide();
                $('#sql2file').show();
                break;
            case 'different':
                $('#file2sql').show();
                $('#sql2file').show();
                break;
            default:
                alert('unknown status:'+CurItem.status);
                $('#file2sql').hide();
                $('#sql2file').hide();
        }
    }

JS
,$this::POS_END);
?>