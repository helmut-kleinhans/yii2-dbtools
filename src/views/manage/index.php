<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Manage DBs - '.$active;

$dbmanageasset = \DbTools\DbManageAsset::register($this);

$statusmap = [
    'ok' =>'success',
    'new' =>'primary',
    'missing' =>'danger',
    'different' =>'warning',
    'removed' =>'default',
];

$specialFlags=['unused','noflags'];

$jsonDataTable = [];
$types = [];

if(isset($data['data'])) {

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
            $info['status'] = \DbTools\db\schemas\DbSchemaBase::getStatus($info['createfile'],$info['createdb']);
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

    .leftfc,
    .rightfc {
        height: calc(100vh - 49px);
    }

    .leftfc {
        overflow-y: auto;
        background-color: #eee;
    }

    .rightfc {
        padding-right: 30px;
	}
    .tab-content {
        height: calc(100vh - 105px);
    }
    .tab-pane {
        overflow-x: hidden;
        overflow-y: auto;
        height: 100%;
    }

    html {
        overflow: hidden;
    }

    /* Auto-height fix */
    .mergely-column .CodeMirror {
        height: 100%;
    }

    .corner-ribbon-db,
    .corner-ribbon-svn{
        color: #f0f0f0;
        width: 100px;
        line-height: 25px;
        text-align: center;
        letter-spacing: 1px;
        z-index: 10000;
        position: absolute;
        top: 0px;
        font-weight: bold;
    }

    .corner-ribbon-svn{
        left: -15px;
        background: #f39c12;
        transform: rotate(-45deg);
        -webkit-transform: rotate(-45deg);
    }

    .corner-ribbon-db{
        right: -15px;
        background: #3c8dbc;
        transform: rotate(45deg);
        -webkit-transform: rotate(45deg);
    }

</style>

<div class="row">
    <div class="col-md-3 leftfc">
        <div class="col">
            <div class="form-group">
                <div class="input-group">
                    <span class="input-group-addon"><span class="glyphicon glyphicon-search"></span></span>
                    <input type="text" id="i_filter_search" class="form-control" placeholder="Search">
                </div>
            </div>
        </div>
        <div class="col">
            <?php
            {
                \insolita\wgadminlte\CollapseBox::begin([
                                                            'type'             => \insolita\wgadminlte\LteConst::TYPE_PRIMARY,
                                                            'collapseRemember' => true,
                                                            'collapseDefault'  => false,
                                                            'isSolid'          => true,
                                                            'boxTools'         => \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_status_all', 'change all', 'info', false, "cbSetAllChecked('cb_filter_status_', $(this).prop('checked') );"),
                                                            'title'            => 'Status',
                                                        ]);
                foreach ($statusmap as $status => $style) {
                    echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_status_' . $status, $status, $style, false, 'updateItemTable()');
                }

                \insolita\wgadminlte\CollapseBox::end();
            }

            {
                \insolita\wgadminlte\CollapseBox::begin([
                                                            'type'             => \insolita\wgadminlte\LteConst::TYPE_PRIMARY,
                                                            'collapseRemember' => true,
                                                            'collapseDefault'  => false,
                                                            'isSolid'          => true,
                                                            'boxTools'         => \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_type_all', 'change all', 'info', false, "cbSetAllChecked('cb_filter_type_', $(this).prop('checked') );"),
                                                            'title'            => 'Type',
                                                        ]);
                foreach ($types as $type) {
                    echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_type_' . $type, $type, 'default', false, 'updateItemTable()');
                }

                \insolita\wgadminlte\CollapseBox::end();
            }

            {
                $boxTools = '<button class="btn btn-info" onclick="btnResetFlags()">Reset</button>';
                foreach ($specialFlags as $name) {
                    $boxTools .= '
                        <button class="btn btn-warning" onclick="btn' . ucwords($name) . '()">' . $name . '</button>';
                }
                \insolita\wgadminlte\CollapseBox::begin([
                                                            'type'             => \insolita\wgadminlte\LteConst::TYPE_PRIMARY,
                                                            'collapseRemember' => true,
                                                            'collapseDefault'  => false,
                                                            'isSolid'          => true,
                                                            'boxTools'         => $boxTools,
                                                            'title'            => 'Flags',
                                                        ]);
                foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
                    echo \DbTools\helper\HelperView::getSwitchCheckbox('switch_filter_flags_' . $name, $name, 'default');
                }

                \insolita\wgadminlte\CollapseBox::end();
            }

            ?>
        </div>
        <div class="col">
            <table id="itemtable" class="display compact" width="100%" cellspacing="0"></table>
        </div>
    </div>
    <div class="col-md-9 rightfc">
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
                <div class="row">
                    <div class="col-md-1 text-center">
                        <div class="corner-ribbon-svn">SVN</div>
                    </div>
                    <div class="col-md-4 text-left">
                        <button type="button" id="file2sql" class="btn btn-primary" onclick="callHome('file2sql','Are you sure you want to execute it')">file 2 sql</button>
                        <button type="button" id="markAsRemoved" class="btn btn-danger" onclick="callHome('markAsRemoved')">mark as removed</button>
                        <button type="button" id="markAsNotRemoved" class="btn btn-warning" onclick="callHome('markAsNotRemoved')">mark as not removed</button>
                    </div>
                    <div class="col-md-2 text-center">
                        <button type="button" id="diffPrev" class="btn btn-info" onclick="mergelyScrollToDiff('prev')">▲</button>
                        <button type="button" id="diffNext" class="btn btn-info" onclick="mergelyScrollToDiff('next')">▼</button>
                    </div>
                    <div class="col-md-4 text-right">
                        <button type="button" id="sql2file" class="btn btn-info" onclick="callHome('sql2file')">sql 2 file</button>
                        <button type="button" id="drop" class="btn btn-danger" onclick="callHome('drop','Are you sure you want to drop it?')">drop</button>
                        <button type="button" id="dropAndMarkAsRemoved" class="btn btn-warning" onclick="callHome('dropAndMarkAsRemoved','Are you sure you want to drop it?')">drop and mark as removed</button>
                    </div>
                    <div class="col-md-1 text-center">
                        <div class="corner-ribbon-db">DB</div>
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

$urlAjax = Yii::$app->getUrlManager()->createUrl('dbtools/manage/ajax');

$settingsLoad='';
$settingsSave='';

foreach ($statusmap as $status=>$style)
{
    $settingsLoad .= \DbTools\helper\HelperView::getCbSettingsLoadBool('cb_filter_status_' . $status);
    $settingsSave .= \DbTools\helper\HelperView::getCbSettingsSaveBool('cb_filter_status_' . $status);
}
foreach ($types as $type)
{
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

$customer = isset($_REQUEST['customer']) ? $_REQUEST['customer'] : '';

$checkFlags = [];
foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
    $checkFlags[] = "
var fval = $('#switch_filter_flags_$name').val();
if(fval  != -1 && ((fval == 1 && !value['$name']) || (fval == 0 && value['$name']))) {
    return false;
}";
}
$checkFlags=implode("\n",$checkFlags);
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
        $("#switch_filter_flags_usedBy").val(0);
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
            dom: 'lrtip',
            responsive: false,
            select: true,
            stateSave: true,
            stateSaveParams: function(settings, data) {
                data.columns.forEach(function (column) {
                    delete column.visible;
                });
            },
            paging: true,
            scrollY:        "650px",
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
    
    function mergelyScrollToDiff(dir) {
        $('#compare').mergely('scrollToDiff', dir);
    }
    
    $(document).ready(function () {
        $('#compare').mergely({
            width: 'auto',
            editor_width: 'auto',
            editor_height: 'calc(100vh - 170px)',
            cmsettings: {readOnly: true, lineNumbers: true}
        });
        $('#compare').mergely('resize', '');

        initSwitches();
        
        $settingsLoad

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
JS
,$this::POS_END);



?>
