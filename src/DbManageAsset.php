<?php

namespace DbTools;

use yii\web\AssetBundle;

class DbManageAsset extends AssetBundle
{
    public $sourcePath = '@vendor/helmut-kleinhans/yii2-dbtools/src/assets';

    public $css = ['css/manage.css'];

    public $depends = [
        //'xxx\yyy',
    ];

    public function init()
    {
        //$this->js[] = YII_DEBUG ? 'js/manage.js' : 'js/manage.min.js';
        $this->js[] = 'js/manage.js';
    }
}
