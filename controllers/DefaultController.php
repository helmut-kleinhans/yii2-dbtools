<?php

namespace kleinhans\modules\dbtools\controllers;

use kleinhans\modules\dbtools\db\schema\DbSchemaBase;
use kleinhans\modules\dbtools\db\schema\DbSchemaEvents;
use kleinhans\modules\dbtools\db\schema\DbSchemaFunctions;
use kleinhans\modules\dbtools\db\schema\DbSchemaProcedures;
use kleinhans\modules\dbtools\db\schema\DbSchemaTables;
use kleinhans\modules\dbtools\db\schema\DbSchemaTriggers;
use kleinhans\modules\dbtools\db\schema\DbSchemaViews;
use kleinhans\modules\dbtools\helper\HelperGlobal;
use yii\web\Controller;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\web\HttpException;

class DefaultController extends Controller {

    private $dbconname;


    public function __construct($id, $module, $config = [])
    {
        $this->dbconname = HelperGlobal::paramOptional($_REQUEST, 'dbconname','');
        parent::__construct($id, $module, $config);
        $this->enableCsrfValidation = false;
    }

    public function actionIndex()
    {
        $dbs = HelperGlobal::getDBs();
        $ret = [];
        $db = NULL;

        $dbconname = $this->dbconname;
        //$dbconname = 'dbEM';

        if(!empty($dbconname) && isset($dbs[$dbconname])) {
            $db = $dbs[$dbconname];
        } else {
            foreach ($dbs as $dbcn => $dbx) {
                $dbconname = $dbcn;
                $db = $dbx;
                break;
            }
        }

        if(!empty($dbconname) && !empty($db)) {
            $ret = $this->_getInfo($dbconname, $db);
        }

        return $this->render('index', [
            'dbs'    => $dbs,
            'active' => $dbconname,
            'data'   => $ret,
        ]);
/*
        $content = \Yii::$app->view->renderFile('@backend/views/test/dbdiff.php', [
            'dbs' => $dbs,
            'active' => $dbconname,
            'data' => $ret]);
        $view = \Yii::$app->view->renderFile('@backend/views/layouts/empty.php', ['content' => $content]);
        return $view;*/
    }

    private function _getInfo($dbconname, $db)
    {
        $ret = [];
        //echo '<hr><h1>'.$dbconname.'</h1>';
        $ret['info']['dbconname'] = $dbconname;

        $classes[DbSchemaTables::cType] = new DbSchemaTables($dbconname, $db);
        $classes[DbSchemaProcedures::cType] = new DbSchemaProcedures($dbconname, $db);
        $classes[DbSchemaViews::cType] = new DbSchemaViews($dbconname, $db);
        $classes[DbSchemaFunctions::cType] = new DbSchemaFunctions($dbconname, $db);
        $classes[DbSchemaTriggers::cType] = new DbSchemaTriggers($dbconname, $db);
        $classes[DbSchemaEvents::cType] = new DbSchemaEvents($dbconname, $db);

        foreach ($classes as $type => $c)
        {
            $ret['data'][$type] = $c->info();
        }
        $search4use = [];
        foreach ($ret['data'] as $type => $v)
        {
            foreach ($v as $name => $vd)
            {
                if (isset($search4use[$name]))
                {
                    $ret['data'][$type][$name]['warnings'][] = 'Duplicate name in: ' . $search4use[$name];
                    $ret['data'][$search4use[$name]][$name]['warnings'][] = 'Duplicate name in: ' . $type;
                }
                else
                {
                    $search4use[$name] = $type;
                }
            }
        }
        foreach ($classes as $type => $c)
        {
            $ret['data'][$type] = $c->findUses($ret['data'][$type], $search4use);
        }
        $ret['data'] = DbSchemaBase::mergeData($ret['data']);
        $ret['data'] = DbSchemaBase::setUsedBy($ret['data']);
        foreach ($classes as $type => $c)
        {
            $ret['data'][$type] = $c->finalize($ret['data'][$type]);
        }

        return $ret;
    }

}
