<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaFunctions extends DbSchemaProcedures
{
	const cType = 'functions';

    public function __construct(string $dbName, \yii\db\Connection $db)
	{
		DbSchemaBase::__construct($dbName, $db, self::cType);
	}

	protected function getList(): array
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

	protected function getCreate(string $name): string
	{
		$row = $this->db->createCommand('SHOW CREATE FUNCTION ' . $this->db->quoteTableName($name))->queryOne();
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
        $full[] = 'USE `'.$this->getDbName().'`';
        $full[] = $this->sqlDrop($name);
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

        $sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

    protected function sqlDrop(string $name): string
    {
        return 'DROP FUNCTION IF EXISTS `'.$name.'`';
    }
}
