<?php

namespace DbTools\controllers;

use DbTools\db\classes\DbGenClasses;
use DbTools\db\values\DbGenValues;
use yii\console\Controller;
use Yii;

class AutogenController extends Controller
{
    public function actionAll()
    {
        $this->actionValues();
        $this->actionClasses();
    }

    public function actionValues()
    {
        Yii::info("Values start");
        $c = new DbGenValues();
        $c->create();
        Yii::info("Values end");
    }

    public function actionClasses()
    {
        Yii::info("Classes start");
        $c = new DbGenClasses();
        $c->create();
        Yii::info("Classes end");
    }
}
