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

if(isset($data['data'])) {
    
    foreach ($data['data'] as $group => $items) {
        if (empty($items)) {
            continue;
        }
        if (!is_array($items)) {
            continue;
        }


        foreach ($items as $item => $info) {
            $name = $item;
            $status = '';
            if(\DbTools\db\schemas\DbSchemaBase::isRemoved($info['createfile'])) {
                $status = 'removed';
            } else {
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
                        }
                    }
                }
            }
            $info['status'] = $status;
            $info['dbName'] = $active;
            $info['group'] = $group;
            $info['name'] = $name;
            $info['key'] = $active . '|' . $group . '|' . $name;
            //echo("<option class='st_" . $status . "' value='" . $name . "'>&nbsp;&nbsp;" . $name . "</option>\n");
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
</style>

<div class="row">
    <div class="col-md-3">
        <fieldset id="templates">
            <div class="col">
                <button href="#panel_filters" class="btn btn-info" data-toggle="collapse">Filters</button>

                <div class="panel-group collapse" id="panel_filters">
                    <div class="panel panel-primary">
                        <div class="panel-heading">Status</div>
                        <div class="panel-body">
                            <?php
                            echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_status_all', 'change all', 'info', false, "cbSetAllChecked('cb_filter_status_', $(this).prop('checked') );") . '<br>';
                            foreach ($statusmap as $status => $style) {
                                echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_status_' . $status, $status, $style, false, 'updateItemTable()');
                            }
                            ?>
                        </div>
                    </div>

                    <div class="panel panel-primary">
                        <div class="panel-heading">Flags</div>
                        <div class="panel-body">
                            <?php
                            echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_flags_default_all', 'change all', 'info', false, "cbSetAllChecked('cb_filter_flags_default_', $(this).prop('checked') );cbSetAllChecked('cb_filter_flags_special_',false);") . '<br>';
                            foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
                                echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_flags_default_' . $name, $name, 'default', false, 'onclickFlagsDefault($(this))');
                            }
                            echo '<br>';
                            foreach ($specialFlags as $name) {
                                echo \DbTools\helper\HelperView::getFancyCheckbox('cb_filter_flags_special_' . $name, $name, 'warning', false, 'onclickFlagsSpecial($(this))');
                            }
                            ?>
                        </div>
                    </div>
                </div>
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
                        <button type="button" id="file2sql" class="btn btn-warning" onclick="callHome('file2sql','Are you sure you want to execute it')">file 2 sql</button>
                        <button type="button" id="markAsRemoved" class="btn btn-danger" onclick="callHome('markAsRemoved')">mark as removed</button>
                    </div>
                    <div class="col-md-6">
                        <label>DB</label>
                        <button type="button" id="sql2file" class="btn btn-primary" onclick="callHome('sql2file')">sql 2 file</button>
                        <button type="button" id="drop" class="btn btn-danger" onclick="callHome('drop','Are you sure you want to drop it?')">drop and mark as removed</button>
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

$cbLoad='';
$cbSave='';

foreach ($statusmap as $status=>$style)
{
    $cbLoad .= \DbTools\helper\HelperView::getCbSettingsLoadBool('cb_filter_status_' . $status);
    $cbSave .= \DbTools\helper\HelperView::getCbSettingsSaveBool('cb_filter_status_' . $status);
}

foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
    $cbLoad .= \DbTools\helper\HelperView::getCbSettingsLoadBool('cb_filter_flags_default_' . $name);
    $cbSave .= \DbTools\helper\HelperView::getCbSettingsSaveBool('cb_filter_flags_default_' . $name);
}

$customer = isset($_REQUEST['customer']) ? $_REQUEST['customer'] : '';

$checkFlagsDefault = [];
foreach (\DbTools\db\schemas\DbSchemaBase::FLAGS_ALL as $name) {
    $checkFlagsDefault[]="if( $('#cb_filter_flags_default_$name').prop(\"checked\")){fd=true; hd = (hd || value['$name'] == 1)}";
}
$checkFlagsDefault='var fd=false;hd=false; '.implode("\n",$checkFlagsDefault).' return !fd || hd;';
$this->registerJs(<<<JS
    var table = null;
    var dataTable = $jsonDataTable;
    var statusMap = $jsonStatusmap;
    var CurItem = null;


    var colGroup = 0;
    var colWarning = 1;
    var colStatus = 2;
    var colName = 3;

    function checkSpecialFlagsUnused(value) { 
        return !value['export'] && !value['select'] && !value['usedBy'] && !value['devel'] && !value['legacy']
        return false;
    }
    function checkSpecialFlagsNoFlags(value) {      
        return value.length == 0;
    }
    function checkFlags(rowData) {
        var value = rowData.flags;
        
        if($('#cb_filter_flags_special_unused').prop("checked")) return checkSpecialFlagsUnused(value);
        if($('#cb_filter_flags_special_noflags').prop("checked")) return checkSpecialFlagsNoFlags(value);
                
        $checkFlagsDefault;
    }
    
    /* Custom filtering function which will search data in column four between two values */
    $.fn.dataTable.ext.search.push(
        function( settings, searchData, index, rowData, counter ) {
            if(!$('#cb_filter_status_'+rowData.status).prop("checked")) return false;
            if (!checkFlags(rowData)) return false;
            return true;
        }
    );

    function onclickFlagsDefault(obj) {
        if(obj.prop("checked")) {
            cbSetAllChecked('cb_filter_flags_special_',false);
        }
        updateItemTable();
    }

    function onclickFlagsSpecial(obj) {
        if(obj.prop("checked")) {
            cbSetAllChecked('cb_filter_flags_default_', false);
            cbSetAllChecked('cb_filter_flags_special_',false);
            obj.prop("checked",true);
        }
        updateItemTable();
    }
    
    function cbSetAllChecked(id,checked) {
        $('[id^='+id+']').each(function( index ) {
            $(this).prop("checked", checked);
        });
        updateItemTable();
    }
    
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
                console.log(task + 'ok');
                var row = table.row({selected: true});
                if(!row) return;
                var data = row.data();
                if(!data) return;
                                
                $( row.node() ).removeClass( 'text-'+statusMap[data.status] );
                
                var newItem = JSON.parse(response);
                if(!newItem) {
                    return;
                }
                data.createfile = newItem.createfile;
                data.createdb = newItem.createdb;
                data.status = newItem.status;
                
                $( row.node() ).addClass( 'text-'+statusMap[data.status] );
                row.data(data);
                row.invalidate();
                row.draw();
                loadData(data);
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
                alert(task + ' failed: ' + xhr.responseText);
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
                $('#markAsRemoved').hide();
                $('#drop').show();
                break;
            case 'missing':
                $('#file2sql').show();
                $('#sql2file').hide();
                $('#markAsRemoved').show();
                $('#drop').hide();
                break;
            case 'new':
                $('#file2sql').hide();
                $('#sql2file').show();
                $('#markAsRemoved').hide();
                $('#drop').show();
                break;
            case 'different':
                $('#file2sql').show();
                $('#sql2file').show();
                $('#markAsRemoved').hide();
                $('#drop').show();
                break;
            case 'removed':
                $('#file2sql').hide();
                if(CurItem.createdb != '') {
                    $('#sql2file').show();
                    $('#drop').show();
                } else {
                    $('#sql2file').hide();                    
                    $('#drop').hide();
                }
                $('#markAsRemoved').hide();
                break;
            default:
                alert('unknown status:'+CurItem.status);
                $('#file2sql').hide();
                $('#sql2file').hide();
                $('#markAsRemoved').hide();
                $('#drop').hide();
        }
        
    }

JS
,$this::POS_END);



?>
