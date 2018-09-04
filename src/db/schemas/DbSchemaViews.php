<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaViews extends DbSchemaTables
{
	const cType = 'views';

	public function __construct($dbName, $db)
	{
		DbSchemaBase::__construct($dbName, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.views')->where('TABLE_SCHEMA=DATABASE()');
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
			$ret[$item['TABLE_NAME']]['helper'] = $item;
			$ret[$item['TABLE_NAME']]['columns'] = DbSchemaColumns::get($this->db, $item['TABLE_NAME']);
		}

		return $ret;
	}

	public function getCreate($name)
	{
		$row = $this->db->createCommand('SHOW CREATE VIEW ' . $this->db->quoteTableName($name))->queryOne();
		if (isset($row['Create View']))
		{
			$sql = $row['Create View'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.self::getDbName().'`';
        $full[] = 'DROP VIEW /*!50032 IF EXISTS */ `'.$name.'`';
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

        $sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

        return $sql;
	}

    protected function _doInfo($data)
    {
        if (!isset($data['helper']))
        {
            return [];
        }

        $warnings = [];

        if($data['helper']['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
            $warnings[] = 'DEFINER needs to be "'.DbToolsModule::getInstance()->checkDefiner.'"';
        }

        if($data['helper']['SECURITY_TYPE'] != 'INVOKER') {
            $warnings[] = 'SECURITY TYPE needs to be "INVOKER"';
        }

        $info = '';

        return ['text'     => $info,
                'warnings' => $warnings,];
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
