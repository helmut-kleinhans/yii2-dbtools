<?php
namespace DbTools\db;

use DbToolsExport\dbvalues\DbValues;

class DbException extends \yii\db\Exception
{
    private $dbMsg = NULL;
    private $dbCode = NULL;

    public function __construct(\yii\db\Exception $e = NULL, $code = -1, $msg = '')
    {
        $this->dbCode=$code;
		$errorinfo = empty($e) ? [] : $e->errorInfo;
        $this->dbMsg = isset($errorinfo[2]) ? $errorinfo[2] : $msg;

        if (!empty($e)) {
            if (isset($errorinfo[1])) {
                $code = $errorinfo[1];
            }
            else {
                $code = $e->getCode();
            }

            if(!isset($errorinfo[2])) {
                $msg = $e->getMessage();
            } else {
                $msg = DbValues::MessageByCode($code);
                if(!empty($errorinfo[2])) {
                    $msg .= ' - '.$errorinfo[2];
                }
            }
        }

		parent::__construct($msg,$errorinfo, $code, $e);
	}

    public function getDbCode()
    {
        return $this->dbCode;
    }

    public function getDbMsg()
    {
        return $this->dbMsg;
    }
}
