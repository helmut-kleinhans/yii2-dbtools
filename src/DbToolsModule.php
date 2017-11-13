<?php

namespace DbTools;

use Yii;

class DbToolsModule extends \yii\base\Module {
	public $controllerNamespace = 'DbTools\controllers';

    public $checkDefiner = 'root@%';
    public $exportDelimiter = "\$del$\n\n";
    public $exportPath = "@app/dbtools";
    public $xmlValues = "@app/input.xml";

	public function init() {
		parent::init ();

        $this->exportPath=\Yii::getAlias($this->exportPath);
        $this->xmlValues=\Yii::getAlias($this->xmlValues);

        if(!file_exists($this->exportPath)) {
            throw new \Exception('exportPath ('.$this->exportPath.') does not exist');
        }
        if(!file_exists($this->xmlValues)) {
            throw new \Exception('xmlValues ('.$this->xmlValues.') does not exist');
        }
        Yii::setAlias('@dbtools', $this->exportPath);
	}
	public function getFileList() {
		return $this->fileList;
	}
}
