<?php

namespace kleinhans\modules\dbtools\controllers;

use kleinhans\modules\dbtools\db\classes\DbGenClasses;
use kleinhans\modules\dbtools\db\schemas\DbSchemaBase;
use kleinhans\modules\dbtools\db\schemas\DbSchemaEvents;
use kleinhans\modules\dbtools\db\schemas\DbSchemaFunctions;
use kleinhans\modules\dbtools\db\schemas\DbSchemaProcedures;
use kleinhans\modules\dbtools\db\schemas\DbSchemaTables;
use kleinhans\modules\dbtools\db\schemas\DbSchemaTriggers;
use kleinhans\modules\dbtools\db\schemas\DbSchemaViews;
use kleinhans\modules\dbtools\db\values\DbGenValues;
use kleinhans\modules\dbtools\helper\HelperGlobal;
use yii\web\Controller;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\AccessControl;
use yii\web\HttpException;

class ManageController extends Controller {

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

    public function actionAutogen()
    {
        $c = new DbGenValues();
        $c->create();
        $c = new DbGenClasses();
        $c->create();
    }

    public function actionSql2file()
    {
        $dbconname = $this->dbconname;
        $group = HelperGlobal::paramNeeded($_REQUEST, 'group');
        $name = HelperGlobal::paramNeeded($_REQUEST, 'name');
        if (empty($dbconname) || empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }
        $db = Yii::$app->$dbconname;
        if (empty($db))
        {
            throw new \Exception('db failed: ' . $dbconname, 500);
        }
        $c = NULL;
        switch ($group)
        {
            case DbSchemaProcedures::cType:
                $c = new DbSchemaProcedures($dbconname, $db);
                break;
            case DbSchemaTriggers::cType:
                $c = new DbSchemaTriggers($dbconname, $db);
                break;
            case DbSchemaTables::cType:
                $c = new DbSchemaTables($dbconname, $db);
                break;
            case DbSchemaEvents::cType:
                $c = new DbSchemaEvents($dbconname, $db);
                break;
            case DbSchemaViews::cType:
                $c = new DbSchemaViews($dbconname, $db);
                break;
            case DbSchemaFunctions::cType:
                $c = new DbSchemaFunctions($dbconname, $db);
                break;
            default:
                throw new \Exception('Unknown group: ' . $group, 500);
        }
        $c->sql2file($name);
    }

    public function actionFile2sql()
    {
        $dbconname = $this->dbconname;
        $group = HelperGlobal::paramNeeded($_REQUEST, 'group');
        $name = HelperGlobal::paramNeeded($_REQUEST, 'name');
        if (empty($dbconname) || empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }
        $db = Yii::$app->$dbconname;
        if (empty($db))
        {
            throw new \Exception('db failed: ' . $dbconname, 500);
        }
        $c = NULL;
        switch ($group)
        {
            case DbSchemaProcedures::cType:
                $c = new DbSchemaProcedures($dbconname, $db);
                break;
            case DbSchemaTriggers::cType:
                $c = new DbSchemaTriggers($dbconname, $db);
                break;
            case DbSchemaTables::cType:
                $c = new DbSchemaTables($dbconname, $db);
                break;
            case DbSchemaEvents::cType:
                $c = new DbSchemaEvents($dbconname, $db);
                break;
            case DbSchemaViews::cType:
                $c = new DbSchemaViews($dbconname, $db);
                break;
            case DbSchemaFunctions::cType:
                $c = new DbSchemaFunctions($dbconname, $db);
                break;
            default:
                throw new \Exception('Unknown group: ' . $group, 500);
        }
        $c->file2sql($name);
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
