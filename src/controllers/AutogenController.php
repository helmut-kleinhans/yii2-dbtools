<?php

namespace DbTools\controllers;

use common\components\Logfile;
use DbTools\db\classes\DbGenClasses;
use DbTools\db\values\DbGenValues;
use yii\console\Controller;

class AutogenController extends Controller
{
    public function actionAll()
    {
        $this->actionValues();
        $this->actionClasses();
    }

    public function actionValues()
    {
        Logfile::writeLog("Values start");
        $c = new DbGenValues();
        $c->create();
        Logfile::writeLog("Values end");
    }

    public function actionClasses()
    {
        Logfile::writeLog("Classes start");
        $c = new DbGenClasses();
        $c->create();
        Logfile::writeLog("Classes start");
    }
}
