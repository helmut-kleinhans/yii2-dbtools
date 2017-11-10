<?php
namespace xxxcommon\db;

use autogen\dbvalues\AutoGenDbValues;
use Yii;

class DbException extends \yii\db\Exception
{
    private $dbMsg = NULL;
    private $dbCode = NULL;

    public function __construct(\yii\db\Exception $e = NULL, $code = AutoGenDbValues::eError_General_NoError, $msg = '')
    {
        $this->dbCode=$code;
		$errorinfo = empty($e) ? [] : $e->errorInfo;
        $this->dbMsg = isset($errorinfo[2]) ? $errorinfo[2] : $msg;

        $code = empty($e)
			? $code
			: isset($errorinfo[1])
				? $errorinfo[1]
				: $e->getCode();

		$msg = empty($e)
			? $msg
			: isset($errorinfo[2])
				? empty($errorinfo[2])
					? AutoGenDbValues::MessageByCode($code)
					: AutoGenDbValues::MessageByCode($code).' - '.$errorinfo[2]
				: $e->getMessage();

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
