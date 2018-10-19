<?php

namespace DbTools;

use Yii;

class DbToolsModule extends \yii\base\Module
{
    /** @var string  */
    public $controllerNamespace = 'DbTools\controllers';

    #configurable
    /** @var string  */
    public $checkDefiner = 'root@%';
    /** @var string  */
    public $exportDelimiter = "";
    /** @var string  */
    public $xmlValues = "@app/values.xml";
    /** @var array  */
    public $behaviorsManage = [];

    #set with alias
    /** @var string  */
    public $exportPath = '';

    public function init()
    {
        parent::init();

        $this->exportPath = \Yii::getAlias('@DbToolsExport');
        $this->xmlValues = \Yii::getAlias($this->xmlValues);

        if (!file_exists($this->exportPath)) {
            throw new \Exception('exportPath (' . $this->exportPath . ') does not exist');
        }
        if (!file_exists($this->xmlValues)) {
            throw new \Exception('xmlValues (' . $this->xmlValues . ') does not exist');
        }
        if (empty($this->exportDelimiter)) {
            $this->exportDelimiter = '$$';
        }
        $this->exportDelimiter .= "\n\n";
    }

    public function getFileList()
    {
        return $this->fileList;
    }
}
