<?php

namespace kleinhans\modules\dbtools;

class Module extends \yii\base\Module {
	public $controllerNamespace = 'kleinhans\modules\dbtools\controllers';

    public $checkDefiner = 'root@%';
    public $exportDelimiter = "\$del$\n\n";
    public $exportPath = "@app/dbtools";

    public $dir;
	public function init() {
		parent::init ();

		$this->exportPath=\Yii::getAlias($this->exportPath);

		if(!file_exists($this->exportPath)) {
		    throw new \Exception('exportPath ('.$this->exportPath.') does not exist');
        }
        //user did not define the Navbar?
        /*
        if (!$this->pluginsDir) {
            throw new InvalidConfigException('"pluginsDir" must be set');
        }

*/


		/*
		if (\Yii::$app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'kleinhans\modules\dbtools\commands';
		}*/
		// custom initialization code goes here
	}
	public function getFileList() {
		return $this->fileList;
	}
}
