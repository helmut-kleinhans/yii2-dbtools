<?php

namespace kleinhans\modules\dbtools;

class Module extends \yii\base\Module {
	public $controllerNamespace = 'kleinhans\modules\dbtools\controllers';
	public $path;
	public $fileList;
	public function init() {
		parent::init ();
		if (\Yii::$app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'kleinhans\modules\dbtools\commands';
		}
		// custom initialization code goes here
	}
	public function getFileList() {
		return $this->fileList;
	}
}
