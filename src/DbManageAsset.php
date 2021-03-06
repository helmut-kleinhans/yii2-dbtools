<?php

namespace DbTools;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class DbManageAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . "/assets";

        $this->depends[] = JqueryAsset::className();

        $this->css[] = 'css/jquery.triSwitch.css';
        $this->css[] = 'css/codemirror.css';
        $this->css[] = 'css/jquery.dataTables.min.css';
        $this->css[] = 'css/select.bootstrap.min.css';
        $this->css[] = 'css/scroller.dataTables.min.css';
        $this->css[] = 'css/mergely.css';
        $this->css[] = 'css/manage.css';

        $this->js[] = 'js/jquery.triSwitch.js';
        $this->js[] = 'js/manage.js';
        $this->js[] = 'js/jquery.dataTables.min.js';
        $this->js[] = 'js/dataTables.scroller.min.js';
        $this->js[] = 'js/dataTables.select.min.js';
        $this->js[] = 'js/dataTables.bootstrap.min.js';
        $this->js[] = 'js/codemirror.js';
        $this->js[] = 'js/mergely.js';

        parent::init();
    }
}
