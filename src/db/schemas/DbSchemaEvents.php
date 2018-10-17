<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaEvents extends DbSchemaBase
{
	const cType = 'events';

	public function __construct($dbName, $db)
	{
		parent::__construct($dbName, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.events')->where('EVENT_SCHEMA=DATABASE()');
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
            $ret[$item['EVENT_NAME']]['helper'] = $item;
            $ret[$item['EVENT_NAME']]['body'] = $item['EVENT_DEFINITION'];
		}

		return $ret;
	}

	public function getCreate($name)
	{
		$row = $this->db->createCommand('SHOW CREATE EVENT ' . $this->db->quoteTableName($name))->queryOne();

		if (isset($row['Create Event']))
		{
			$sql = $row['Create Event'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.self::getDbName().'`';
        $full[] = 'DROP EVENT /*!50032 IF EXISTS */ `'.$name.'`';
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

        $sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

    protected function _doAdditionalInfo(array $data, array &$brief, array &$ret) {
        if($data['helper']['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
            $ret['warnings'][] = 'DEFINER needs to be "'.DbToolsModule::getInstance()->checkDefiner.'"';
        }
    }

    public function drop($name)
    {
        $sql = 'DROP EVENT /*!50032 IF EXISTS */ `'.$name.'`';
        return $this->executeSql($sql);
    }
}
