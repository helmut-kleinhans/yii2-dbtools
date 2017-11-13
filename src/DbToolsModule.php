<?php

namespace DbTools;

use Yii;

class DbToolsModule extends \yii\base\Module {
	public $controllerNamespace = 'DbTools\controllers';

	//configurable
    public $checkDefiner = 'root@%';
    public $exportDelimiter = "\$del$\n\n";
    public $xmlValues = "@app/values.xml";

    //set with alias
    public $exportPath = '';

    public function init() {
		parent::init ();

        $this->exportPath=\Yii::getAlias('@DbToolsExport');
        $this->xmlValues=\Yii::getAlias($this->xmlValues);

        if(!file_exists($this->exportPath)) {
            throw new \Exception('exportPath ('.$this->exportPath.') does not exist');
        }
        if(!file_exists($this->xmlValues)) {
            throw new \Exception('xmlValues ('.$this->xmlValues.') does not exist');
        }
        $this->exportDelimiter = '$'.$this->exportDelimiter."\n\n";

	}
	public function getFileList() {
		return $this->fileList;
	}
}
