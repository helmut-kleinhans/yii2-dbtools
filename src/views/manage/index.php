<?php

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Manage DBs - ' . $active;

$dbmanageasset = \DbTools\DbManageAsset::register($this);

$statusmap = [
    'ok'        => 'success',
    'new'       => 'primary',
    'missing'   => 'danger',
    'different' => 'warning',
    'removed'   => 'default',
];

$specialFlags = [
    'unused',
    'noflags',
];

$mergelyConf = [
    [
        'label'   => 'ignore ws',
        'option'  => 'ignorews',
        'default' => false,
        'style'   => 'default',
    ],
    [
        'label'   => 'linewrap',
        'option'  => 'wrap_lines',
        'default' => false,
        'style'   => 'default',
    ],
    /*
    [
        'label'   => 'ignore case',
        'option'  => 'ignorecase',
        'default' => false,
        'style'   => 'default',
    ],
    [
        'label'   => 'lcs',
        'option'  => 'lcs',
        'default' => true,
        'style'   => 'default',
    ],
    */
];

$jsonDataTable = [];
$types = [];

if (isset($data['data'])) {

    foreach ($data['data'] as $group => $items) {
        $types[] = $group;
        if (empty($items)) {
            continue;
        }
        if (!is_array($items)) {
            continue;
        }

        foreach ($items as $item => $info) {
            $name = $item;
            $info['status'] = \DbTools\db\schemas\DbSchemaBase::getStatus($info['createfile'], $info['createdb']);
            $info['dbName'] = $active;
            $info['group'] = $group;
            $info['name'] = $name;
            $info['key'] = $active . '|' . $group . '|' . $name;
            $jsonDataTable[] = $info;
        }
    }
}
?>

<style type="text/css" media="screen">

    .text-default {
        color: #7f7f7f;
        text-decoration: line-through;
    }
    .cont{
        display: flex;
        flex:1;
        margin: 0px;
    }

    .contMenu,
    .contDetails {
    }

    .contMenu {
        display: block;
        width: 400px;
        order: 1;
        position: relative;
        background: #eee;
    }

    .contDetails {
        margin: 0px 0px 0px 5px;
        padding: 0px;
        flex: 1;
        order: 2;
        background: #eee;
    }
    .contFilter {
        position: absolute;
        top: 0px;
        left: 0;
        right: 0;
        z-index: 1000;
    }

    .contFilter .box,
    .contFilter .box-body {
        margin: 0px;
        padding: 0px;
    }

    #filterReset {
        position: absolute;
        float: right !important;
        right: 9px;
        top: 27px;
    }

    .contItemTable {
        position: absolute;
        top: 56px;
        left: 0;
        right: 0;
    }

    .tab-content {
        height: calc(100vh - 95px);
    }

    .tab-pane {
        padding: 5px;
        margin: 0px;
        overflow-x: hidden;
        overflow-y: auto;
        height: 100%;
        position: relative;
    }

    #A{
        overflow-y: scroll;
    }

    #B{
        overflow-y: hidden;
    }

    html {
        overflow: hidden;
    }

    .headSvn,
    .headMergely,
    .headDb {
        position: absolute;
        top: 0px;
        height: 30px;
    }
    .headSvn {
        padding-left: 50px;
        left: 0px;
    }
    .headMergely {
        left: 0px;
        right: 0px;
        text-align: center;
    }
    .headDb {
        padding-right: 50px;
        right: 0px;
    }

    .compareWrapper {
        flex: 1 1 auto;
        margin-top: 30px;
        position: relative;
    }
    .compareWrapper #compare {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
    }
    
    /* Auto-height fix */
    .mergely-column .CodeMirror {
        height: 100%;
    }

    .corner-ribbon-db,
    .corner-ribbon-svn {
        color: #f0f0f0;
        width: 100px;
        line-height: 25px;
        text-align: center;
        letter-spacing: 1px;
        z-index: 10000;
        position: absolute;
        top: 5px;
        font-weight: bold;
    }

    .corner-ribbon-svn {
        left: -30px;
        background: #f39c12;
        transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
    }

    .corner-ribbon-db {
        right: -30px;
        background: #3c8dbc;
        transform: rotate(45deg);
        -webkit-transform: rotate(45deg);
    }

</style>

<div class="cont">
    <div class="contMenu">
        <div class="contFilter">
            <?php
            if (1) {
                \insolita\wgadminlte\CollapseBox::begin([
                                                            'type'             => \insolita\wgadminlte\LteConst::TYPE_SUCCESS,
                                                            'collapseRemember' => true,
                                                            'collapseDefault'  => false,
                                                            'isSolid'          => true,
                                                            'topTemplate'      => '
<div {options}>
    <div {headerOptions}>
        <div class="input-group" style="padding-right: 30px;">
            <span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
            <input type="text" id="i_filter_search" class="form-control" placeholder="Search">
    </div>
    <button id="filterReset" class="btn btn-danger btn-xs" onclick="filterReset()"><i class="fa fa-undo"></i></button>
    {box-tools}
</div>
<div class="box-body">
',

                                                        ]);

                {
                    \insolita\wgadminlte\LteBox::begin([
                                                           'type'     => \insolita\wgadminlte\LteConst::TYPE_PRIMARY,
                                                           'isSolid'  => true,
                                                           'boxTools' => \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_status_all', 'change all', 'info', false, "cbSetAllChecked('cb_filter_status_', $(this).prop('checked') );"),
                                                           'title'    => 'Status',
                                                       ]);
                    foreach ($statusmap as $status => $style) {
                        echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_status_' . $status, $status, $style, false, 'updateItemTable()');
                    }

                    \insolita\wgadminlte\LteBox::end();
                }

                {
                    \insolita\wgadminlte\LteBox::begin([
                                                           'type'     => \insolita\wgadminlte\LteConst::TYPE_PRIMARY,
                                                           'isSolid'  => true,
                                                           'boxTools' => \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_type_all', 'change all', 'info', false, "cbSetAllChecked('cb_filter_type_', $(this).prop('checked') );"),
                                                           'title'    => 'Type',
                                                       ]);
                    foreach ($types as $type) {
                        echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_type_' . $type, $type, 'default', false, 'updateItemTable()');
                    }

                    \insolita\wgadminlte\LteBox::end();
                }

                {
                    $boxTools = '<button class="btn btn-info" onclick="btnResetFlags()">Reset</button>';
                    foreach ($specialFlags as $name) {
                        $boxTools .= '
                        <button class="btn btn-warning" onclick="btn' . ucwords($name) . '()">' . $name . '</button>';
                    }

                    \insolita\wgadminlte\LteBox::begin([
                                                           'type'     => \insolita\wgadminlte\LteConst::TYPE_PRIMARY,
                                                           'isSolid'  => true,
                                                           'boxTools' => $boxTools,
                                                           'title'    => 'Flags',
                                                       ]);
                    foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
                        echo \DbTools\helper\HelperView::getSwitchCheckbox('switch_filter_flags_' . $name, $name, 'default');
                    }

                    \insolita\wgadminlte\LteBox::end();
                }

                \insolita\wgadminlte\CollapseBox::end();
            }

            ?>
        </div>
        <div class="contItemTable">
            <table id="itemtable" class="display compact" width="100%" cellspacing="0"></table>
        </div>
    </div>
    <div class="contDetails">
        <ul class="nav nav-tabs" id="contentTab">
            <li class="nav active"><a href="#A" data-toggle="tab">Info</a></li>
            <li class="nav"><a href="#B" data-toggle="tab">Diff</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane area active" id="A">
                <div class="row">
                    <div class="col-md-3"><strong>Item: </strong><span id="itemname">...</span></div>
                    <div class="col-md-2"><strong>Status: </strong><span id="itemstatus">...</span></div>
                    <div class="col-md-7"><strong>Flags: </strong><span id="itemflags">...</span></div>
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
                <div class="headSvn">
                    <div class="corner-ribbon-svn">SVN</div>
                    <button type="button" id="file2sql" class="btn btn-primary btn-sm"
                            onclick="callHome('file2sql','Are you sure you want to execute it')">file 2 sql
                    </button>
                    <button type="button" id="markAsRemoved" class="btn btn-danger btn-sm"
                            onclick="callHome('markAsRemoved')">mark as removed
                    </button>
                    <button type="button" id="markAsNotRemoved" class="btn btn-warning btn-sm"
                            onclick="callHome('markAsNotRemoved')">mark as not removed
                    </button>
                </div>
                <div class="headMergely">
                    <button type="button" id="diffPrev" title="Previous diff" class="btn btn-info btn-sm" onclick="mergelyChange('scrollToDiff', 'prev')">
                        <span class="glyphicon glyphicon-chevron-up"></span>
                    </button>
                    <button type="button" id="diffNext" title="Next diff" class="btn btn-info btn-sm" onclick="mergelyChange('scrollToDiff', 'next')">
                        <span class="glyphicon glyphicon-chevron-down"></span>
                    </button>

                    <?php
                    foreach($mergelyConf as $c) {
                        echo \DbTools\helper\HelperView::getFancyCheckbox('cb_mergely_'.$c['option'], $c['label'], $c['style'], $c['default'], 'mergelyChange(\'options\', { '.$c['option'].': $(this).prop(\'checked\') } )');
                    }
                    ?>
                </div>
                <div class="headDb">
                    <div class="corner-ribbon-db">DB</div>
                    <button type="button" id="sql2file" class="btn btn-info btn-sm" onclick="callHome('sql2file')">sql 2
                        file
                    </button>
                    <button type="button" id="drop" class="btn btn-danger btn-sm"
                            onclick="callHome('drop','Are you sure you want to drop it?')">drop
                    </button>
                    <button type="button" id="dropAndMarkAsRemoved" class="btn btn-warning btn-sm"
                            onclick="callHome('dropAndMarkAsRemoved','Are you sure you want to drop it?')">drop and
                        mark as removed
                    </button>
                </div>
                <div class="compareWrapper">
                    <div id="compare">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

$jsonDataTable = json_encode($jsonDataTable, JSON_PRETTY_PRINT);
$jsonStatusmap = json_encode($statusmap, JSON_PRETTY_PRINT);
$imgRoot = $dbmanageasset->baseUrl . '/css/images';

$imgDanger = Html::img($imgRoot . '/danger.png', [
    'class' => 'datatableicon',
    'alt'   => 'warning',
    'title' => 'warning',
]);

$urlAjax = Yii::$app->getUrlManager()->createUrl('dbtools/manage/ajax');

$settingsLoad = '';
$settingsSave = '';
$settingsLoadMergely = '';
$settingsSaveMergely = '';

foreach ($statusmap as $status => $style) {
    $settingsLoad .= \DbTools\helper\HelperView::getCbSettingsLoadBool('cb_filter_status_' . $status);
    $settingsSave .= \DbTools\helper\HelperView::getCbSettingsSaveBool('cb_filter_status_' . $status);
}
foreach ($types as $type) {
    $settingsLoad .= \DbTools\helper\HelperView::getCbSettingsLoadBool('cb_filter_type_' . $type);
    $settingsSave .= \DbTools\helper\HelperView::getCbSettingsSaveBool('cb_filter_type_' . $type);
}

$settingsLoad .= \DbTools\helper\HelperView::getValSettingsLoad('i_filter_search');
$settingsSave_Filter_Search = \DbTools\helper\HelperView::getValSettingsSave('i_filter_search');

$switchInit = '';
foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
    $switchInit .= \DbTools\helper\HelperView::getSwitchInit('switch_filter_flags_' . $name);
    $settingsSave .= \DbTools\helper\HelperView::getValSettingsSave('switch_filter_flags_' . $name);
}

foreach ($mergelyConf as $c) {
    $settingsLoadMergely .= \DbTools\helper\HelperView::getCbSettingsLoadBool('cb_mergely_' . $c['option']);
    $settingsLoadMergely .= '$(\'#compare\').mergely(\'options\', { '.$c['option'].': $(\'#cb_mergely_' . $c['option'].'\').prop(\'checked\') } );';

    $settingsSaveMergely .= \DbTools\helper\HelperView::getCbSettingsSaveBool('cb_mergely_' . $c['option']);
    #$settingsSaveMergely .= 'console.log("save mergely '.$c['option'].' value "+ $(\'#cb_mergely_' . $c['option'].'\').prop(\'checked\'));';
}
$customer = isset($_REQUEST['customer']) ? $_REQUEST['customer'] : '';

$checkFlags = [];
foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
    $checkFlags[] = "
var fval = $('#switch_filter_flags_$name').val();
if(fval  != -1 && ((fval == 1 && !value['$name']) || (fval == 0 && value['$name']))) {
    return false;
}";
}
$checkFlags = implode("\n", $checkFlags);
$this->registerJs(<<<JS
    var table = null;
    var dataTable = $jsonDataTable;
    var statusMap = $jsonStatusmap;
    var CurItem = null;
    var initSwitchesInProgress = false;

    var curCol = 0;
    var colGroup = curCol++;
    var colWarning = curCol++;
    var colStatus = curCol++;
    var colName = curCol++;

    function  filterReset() {
        $('#i_filter_search').val('');
        table.search('').draw() ;
        cbSetAllChecked('cb_filter_status_',true);
        cbSetAllChecked('cb_filter_type_',true);
        btnResetFlags();
    }
    
    function checkRemoved(rowData) { 
        return rowData.status!='removed' || rowData.createdb !='';
    }    
    
    function checkFlags(rowData) {
        var value = rowData.flags;
        
        $checkFlags
        
        return true;
    }
    
    /* Custom filtering function which will search data in column four between two values */
    $.fn.dataTable.ext.search.push(
        function( settings, searchData, index, rowData, counter ) {
            /*if(!checkRemoved(rowData)) return false;*/
            if(!$('#cb_filter_status_'+rowData.status).prop("checked")) return false;
            if(!$('#cb_filter_type_'+rowData.group).prop("checked")) return false;
            if(!checkFlags(rowData)) return false;
            return true;
        }
    );
         
    function cbSetAllChecked(id,checked) {
        $('[id^='+id+']').each(function( index ) {
            $(this).prop("checked", checked);
        });
        updateItemTable();
    }
    
    function btnResetFlags() {
        $("[id^=switch_filter_flags_]").val(-1);
        updateItemTable();
    }
    
    function btnUnused() {      
        $("[id^=switch_filter_flags_]").val(-1);
        $("#switch_filter_flags_export").val(0);
        $("#switch_filter_flags_select").val(0);
        $("#switch_filter_flags_used_by").val(0);
        $("#switch_filter_flags_devel").val(0);
        $("#switch_filter_flags_legacy").val(0);
        
        //events are allways used
        $("#cb_filter_type_events").prop("checked", false);
        $("#cb_filter_type_triggers").prop("checked", false);
        
        updateItemTable();
    }
    
    function btnNoflags() {
        $("[id^=switch_filter_flags_]").val(0);
        updateItemTable();
    }
    
    function initItemTable() {

        table = $('#itemtable').DataTable( {
            dom: 'lrt',
            responsive: true,
            select: true,
            stateSave: true,  
            stateSaveParams: function(settings, data) {
                data.columns.forEach(function (column) {
                    delete column.visible;
                });
            },
            paging: true,
            scrollCollapse: false,
            scrollY:        "calc(100vh - 137px)",
            scroller: true,
            sScrollX: false,
            data: dataTable,
            rowId: 'key',
            columns: [
                { title: "Type", data: "group" },
                { title: "", data: "flags" },
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
                        if(data && data.warning) {
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
    
    function initSwitches() {        
        initSwitchesInProgress = true;
        
        $switchInit
        
        $("[id^='switch_']").on("change", function () {
                updateItemTable();
            });
        
        initSwitchesInProgress = false;
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
        
    function mergelyChange(key, value) {
        $('#compare').mergely(key, value);
        $settingsSaveMergely
    }
        
    $(document).ready(function () {
        $('#compare').mergely({
            width: 'auto',
            editor_width: 'auto',
            editor_height: 'calc(100vh - 144px)',
            cmsettings: {readOnly: true, lineNumbers: true}
        });
        $('#compare').mergely('resize', '');

        initSwitches();
        
        $settingsLoad
        $settingsLoadMergely

        $('#contentTab a[href="' + settingsLoad('activeTab','#A') + '"]').tab('show');

        initItemTable();


        $('#compare').mergely('resize', '');

        $('#i_filter_search').keyup(function(){
            $settingsSave_Filter_Search
            table.search($(this).val()).draw() ;
        });
        table.search($('#i_filter_search').val()).draw() ;
        
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        settingsSave('activeTab', $(e.target).attr('href'));
        $('#compare').mergely('resize', '');
    });
    
    function callHome(task, confirmMsg) {
        if (!CurItem) return;

        if(confirmMsg) {
            var r = confirm(CurItem.name + '\\n\\n' + confirmMsg);
    
            //cancel clicked : stop button default action
            if (r === false) {
                return false;
            }
        }

        var formData = new FormData();
        formData.append('dbName', CurItem.dbName);
        formData.append('group', CurItem.group);
        formData.append('name', CurItem.name);
        formData.append('customer', '$customer');

        $.ajax({
            url: '$urlAjax&task='+task,
            data: formData,
            processData: false,
            contentType: false,
            type: 'POST',
            success: function (response) {
                console.log(task + ' ok');
                var row = table.row({selected: true});
                if(!row) return;
                var data = row.data();
                if(!data) return;
                                
                $( row.node() ).removeClass( 'text-'+statusMap[data.status] );
                
                var newItem = JSON.parse(response);
                if(!newItem) {
                    return;
                }
                
                if(newItem.status=='') {
                    removeRow(row);
                    return;
                }
                data.createfile = newItem.createfile;
                data.createdb = newItem.createdb;
                data.status = newItem.status;                
                
                var nrow = nearestVisibleRow(row,false);
                    
                $( row.node() ).addClass( 'text-'+statusMap[data.status] );
                row.data(data);
                row.invalidate();
                row.draw();
                
                
                var nextrow = table.row({selected: true, filter: 'applied'});
                //check if last selected row has been filtered out
                if((!nextrow || nextrow.indexes().length == 0) && nrow) {
                    selectRow(table.row("#"+nrow.id()));
                    return;
                }               
                
                loadData(data);
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
                alert(task + ' failed: ' + xhr.responseText);
            }
        });
    }
    
    function removeRow(row) {
        var nrow = nearestVisibleRow(row,false);
        selectRow(nrow);
        row.remove();
        row.invalidate();
        row.draw();
    }
    
    function nearestVisibleRow(row,useself=true) {
        var myFilteredRows = table.rows({filter: 'applied'});
        
        var last = null;
        var found=false;

        var indexes = myFilteredRows.indexes();
        
        for (i = 0; i < indexes.length; i++) {
            var index = indexes[i];
            if(row.index() == index) {
                found=true;
                if(useself) {
                    return row;
                }
                continue;
            }
            
            if(!found) {
                last=index;
            } else {
                return table.row(index);
            }
        } 
        
        return table.row(last);
    }

    function selectItem(val) {
        //alert('select-'+val);
        if(val==null) return true;
        return selectRow(table.row('#'+val));
    }

    function selectRow(row) {
        if(!row) return true;
        table.rows({selected: true}).deselect();
        row.select();
        row.scrollTo();
        loadData(row.data());
        return true;
    }

    function updateItemTable() {
        if(!table) return;
        
        $settingsSave

        var row = table.row({selected: true});
        var nrow = nearestVisibleRow(row,false);
            
        table.draw();

        var nextrow = table.row({selected: true, filter: 'applied'});

        //check if last selected row has been filtered out
        if((!nextrow || nextrow.indexes().length == 0) && nrow) {
            selectRow(table.row("#"+nrow.id()));
            return;
        }
    }
    
    function buttonEnable(item) {
        item.prop('disabled', false);
    }
    
    function buttonDisable(item) {
        item.prop('disabled', true);        
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
        
        var htmlFlags='';
        Object.keys(CurItem.flags).forEach(function(key,index) {
            htmlFlags+='<span class="label label-default">'+key+'</span>&nbsp;';
        });
        $('#itemflags').html(htmlFlags);

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
                buttonDisable($('#file2sql'));
                buttonDisable($('#sql2file'));
                buttonEnable($('#markAsRemoved'));
                buttonDisable($('#markAsNotRemoved'));
                buttonEnable($('#drop'));
                buttonEnable($('#dropAndMarkAsRemoved'));  
                buttonDisable($('#diffPrev'));
                buttonDisable($('#diffNext'));
                break;
            case 'missing':
                buttonEnable($('#file2sql'));
                buttonDisable($('#sql2file'));
                buttonEnable($('#markAsRemoved'));
                buttonDisable($('#markAsNotRemoved'));
                buttonDisable($('#drop'));
                buttonDisable($('#dropAndMarkAsRemoved'));
                buttonDisable($('#diffPrev'));
                buttonDisable($('#diffNext'));
                break;
            case 'new':
                buttonDisable($('#file2sql'));
                buttonEnable($('#sql2file'));
                buttonDisable($('#markAsRemoved'));
                buttonDisable($('#markAsNotRemoved'));
                buttonEnable($('#drop'));
                buttonEnable($('#dropAndMarkAsRemoved'));
                buttonDisable($('#diffPrev'));
                buttonDisable($('#diffNext'));
                break;
            case 'different':
                buttonEnable($('#file2sql'));
                buttonEnable($('#sql2file'));
                buttonEnable($('#markAsRemoved'));
                buttonDisable($('#markAsNotRemoved'));
                buttonEnable($('#drop'));
                buttonEnable($('#dropAndMarkAsRemoved'));
                buttonEnable($('#diffPrev'));
                buttonEnable($('#diffNext'));
                break;
            case 'removed':
                buttonEnable($('#file2sql'));
                if(CurItem.createdb != '') {
                    buttonEnable($('#sql2file'));
                    buttonEnable($('#drop'));
                    buttonEnable($('#dropAndMarkAsRemoved'));
                    buttonEnable($('#diffPrev'));
                    buttonEnable($('#diffNext'));
                } else {
                    buttonDisable($('#sql2file'));                    
                    buttonDisable($('#drop'));
                    buttonDisable($('#dropAndMarkAsRemoved'));
                    buttonDisable($('#diffPrev'));
                    buttonDisable($('#diffNext'));
                }
                buttonDisable($('#markAsRemoved'));
                buttonEnable($('#markAsNotRemoved'));
                break;
            default:
                alert('unknown status:'+CurItem.status);
                buttonDisable($('#file2sql'));
                buttonDisable($('#sql2file'));
                buttonDisable($('#markAsRemoved'));
                buttonDisable($('#markAsNotRemoved'));
                buttonDisable($('#drop'));
                buttonDisable($('#dropAndMarkAsRemoved'));
                buttonDisable($('#diffPrev'));
                buttonDisable($('#diffNext'));
        }
        
    }
    
    var resizeTimer;

    $(window).on('resize', function(e) {
          console.log('resize');
    
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() { 
          console.log('resized');
        $('#compare').mergely('resize', '');
      }, 250);
    
    });
JS
    , $this::POS_END);

?>
