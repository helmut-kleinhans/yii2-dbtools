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

$this->registerJsFile('https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js', ["position" => $this::POS_HEAD]);
$this->registerCssFile('https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('https://cdn.datatables.net/1.10.15/js/dataTables.bootstrap.min.js', ["position" => $this::POS_HEAD]);
$this->registerCssFile('https://cdn.datatables.net/1.10.15/css/dataTables.bootstrap.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('https://cdn.datatables.net/select/1.2.2/js/dataTables.select.min.js', ["position" => $this::POS_HEAD]);
$this->registerCssFile('https://cdn.datatables.net/select/1.2.2/css/select.bootstrap.min.css', ["position" => $this::POS_HEAD]);

$this->registerJsFile('https://cdn.datatables.net/scroller/1.4.2/js/dataTables.scroller.min.js', ["position" => $this::POS_HEAD]);
$this->registerCssFile('https://cdn.datatables.net/scroller/1.4.2/css/scroller.dataTables.min.css', ["position" => $this::POS_HEAD]);
*/
\kleinhans\modules\dbtools\DbManageAsset::register($this);

$statusmap = [
    'ok' =>'success',
    'missingonserver' =>'primary',
    'missingonsvn' =>'danger',
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
                $status = 'missingonserver';
            }
            else {
                if (empty($info['createfile'])) {
                    $status = 'missingonsvn';
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
$jsonDataTable = json_encode($jsonDataTable, JSON_PRETTY_PRINT);

?>

<div class="tools">
    <?php
    if (YII_DEBUG) {
        echo '<button type="button" id="autogen" class="btn-warning btn-md" onclick="autogen()">autogen</button>';
    }
    ?>
</div>

<div class="row">
    <div class="col-md-3" id="leftf">
        <div class="row">
            <ul class="nav nav-tabs">
                <?php
                    foreach ($dbs as $dbconname => $db) {
                        echo '<li'.(($dbconname==$active)?' class="active"':'').'><a href="'.Yii::$app->getUrlManager()->createUrl(['db','dbconname'=>$dbconname]).'">'.$dbconname.'</a></li>';
                    }
                ?>
            </ul>
        </div>
        <fieldset id="templates">
            <div class="col">

                <?php
                foreach ($statusmap as $status=>$style)
                {
                    echo \kleinhans\modules\dbtools\helper\HelperView::getFancyCheckbox('cb_filter_'.$status,$status,$style,false,'updateItemTable()');
                }
                ?>

            </div>
            <div class="col">
                <table id="itemtable" class="display compact" width="100%" cellspacing="0"></table>
            </div>
        </fieldset>
    </div>
    <div class="col-md-9" id="rightf">
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

