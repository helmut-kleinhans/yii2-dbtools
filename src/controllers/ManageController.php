<?php

namespace DbTools\controllers;

use DbTools\db\schemas\DbSchemaBase;
use DbTools\db\schemas\DbSchemaEvents;
use DbTools\db\schemas\DbSchemaFunctions;
use DbTools\db\schemas\DbSchemaProcedures;
use DbTools\db\schemas\DbSchemaTables;
use DbTools\db\schemas\DbSchemaTriggers;
use DbTools\db\schemas\DbSchemaViews;
use DbTools\DbToolsModule;
use DbTools\helper\HelperGlobal;
use yii\web\Controller;
use Yii;

class ManageController extends Controller {

    const DEFAULT_DB_NAME = 'db';

    /** @var string */
    private $dbName = self::DEFAULT_DB_NAME;

    /** @var \yii\db\Connection */
    private $db;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->setDb();
        $this->enableCsrfValidation = false;
    }

    private function setDb()
    {
        $db = HelperGlobal::paramOptional($_REQUEST, 'dbName',NULL);
        if($db===NULL) {
            $db = HelperGlobal::paramOptional($_REQUEST, 'db',NULL);
            if($db===NULL) {
                $this->setDefaultDb();

                return;
            }
        }

        $this->dbName = $db;

        try {
            $this->db = Yii::$app->$db;
        } catch(\Throwable $e) {
            throw new \Exception("given db is unknown/wrong: $db");
        }
    }

    private function setDefaultDb()
    {
        $db = $this->dbName;
        try {
            $this->db = Yii::$app->$db;
            return;
        } catch(\Throwable $e) {
            //ignore
        }

        $dbs = HelperGlobal::getDBs();
        foreach($dbs as $n=>$c) {
            $this->dbName = $n;
            $this->db = $c;
            return;
        }

        throw new \Exception("No db connection found in config!");
    }

    public function behaviors()
    {
        return DbToolsModule::getInstance()->behaviorsManage;
    }

    public function actionIndex()
    {
        return $this->render('index', [
            'active' => $this->dbName,
            'data'   => $this->_getInfo(),
        ]);
    }

    public function actionSql2file()
    {
        $group = HelperGlobal::paramNeeded($_REQUEST, 'group');
        $name = HelperGlobal::paramNeeded($_REQUEST, 'name');
        if (empty($group) || empty($name))
        {
            throw new \Exception('Empty param');
        }

        $c = $this->group2class($group);
        $c->sql2file($name);
    }

    public function actionFile2sql()
    {
        $group = HelperGlobal::paramNeeded($_REQUEST, 'group');
        $name = HelperGlobal::paramNeeded($_REQUEST, 'name');
        if (empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }

        $c = $this->group2class($group);
        $c->file2sql($name);
    }

    public function actionMarkAsRemoved()
    {
        $group = HelperGlobal::paramNeeded($_REQUEST, 'group');
        $name = HelperGlobal::paramNeeded($_REQUEST, 'name');
        if (empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }

        $c = $this->group2class($group);
        $c->markAsRemoved($name);
    }

    public function actionDrop()
    {
        $group = HelperGlobal::paramNeeded($_REQUEST, 'group');
        $name = HelperGlobal::paramNeeded($_REQUEST, 'name');
        if (empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }

        $c = $this->group2class($group);
        $c->drop($name);
        $c->markAsRemoved($name);
    }

    private function group2class(string $group) : DbSchemaBase
    {
        switch ($group)
        {
            case DbSchemaProcedures::cType:
                return new DbSchemaProcedures($this->dbName, $this->db);
                break;
            case DbSchemaTriggers::cType:
                return new DbSchemaTriggers($this->dbName, $this->db);
                break;
            case DbSchemaTables::cType:
                return new DbSchemaTables($this->dbName, $this->db);
                break;
            case DbSchemaEvents::cType:
                return new DbSchemaEvents($this->dbName, $this->db);
                break;
            case DbSchemaViews::cType:
                return new DbSchemaViews($this->dbName, $this->db);
                break;
            case DbSchemaFunctions::cType:
                return new DbSchemaFunctions($this->dbName, $this->db);
                break;
            default:
                throw new \Exception('Unknown group: ' . $group, 500);
        }
    }

    private function _getInfo()
    {
        $ret = [];
        $ret['info']['dbName'] = $this->dbName;

        $classes[DbSchemaTables::cType] = new DbSchemaTables($this->dbName, $this->db);
        $classes[DbSchemaProcedures::cType] = new DbSchemaProcedures($this->dbName, $this->db);
        $classes[DbSchemaViews::cType] = new DbSchemaViews($this->dbName, $this->db);
        $classes[DbSchemaFunctions::cType] = new DbSchemaFunctions($this->dbName, $this->db);
        $classes[DbSchemaTriggers::cType] = new DbSchemaTriggers($this->dbName, $this->db);
        $classes[DbSchemaEvents::cType] = new DbSchemaEvents($this->dbName, $this->db);

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
