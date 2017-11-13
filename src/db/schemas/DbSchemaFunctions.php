<?php
namespace kleinhans\modules\dbtools\db\schemas;

use kleinhans\modules\dbtools\DbToolsModule;
use Yii;

class DbSchemaFunctions extends DbSchemaProcedures
{
	const cType = 'functions';

	public function __construct($dbconname, $db)
	{
		DbSchemaBase::__construct($dbconname, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.routines')->where('ROUTINE_SCHEMA=DATABASE()')->andWhere(['ROUTINE_TYPE' => 'FUNCTION']);
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
			$ret[$item['ROUTINE_NAME']]['helper'] = $item;
			$ret[$item['ROUTINE_NAME']]['body'] = $item['ROUTINE_DEFINITION'];
			$ret[$item['ROUTINE_NAME']]['params'] = DbSchemaParams::get($this->db, 'FUNCTION', $item['ROUTINE_NAME']);
		}

		return $ret;
	}

	public function getCreate($name)
	{
		$row = $this->db->createCommand('SHOW CREATE FUNCTION ' . $name)->queryOne();
		if (isset($row['Create Function']))
		{
			$sql = $row['Create Function'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.self::getDbName().'`';
        $full[] = 'DROP FUNCTION IF EXISTS `'.$name.'`';
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

        $sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

    public function file2sql($name)
    {
        $filepath = $this->dir . '/' . $name . '.sql';

        if(!file_exists($filepath)) {
            throw new \Exception('file does not exist');
        }
        $data = file_get_contents($filepath);
        if(empty($data)) {
            throw new \Exception('empty data');
        }

        return $this->executeSql($data);
    }
}
