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

    protected function getBriefContent(array $data): string {
        return '';
    }

    protected function _doAdditionalInfo(array $data, array &$brief, array &$ret) {
	    if(isset($data['helper'])) {
            if ($data['helper']['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
                $ret['warnings'][] = 'DEFINER needs to be "' . DbToolsModule::getInstance()->checkDefiner . '"';
            }

            if ($data['helper']['SECURITY_TYPE'] != 'INVOKER') {
                $ret['warnings'][] = 'SECURITY TYPE needs to be "INVOKER"';
            }
        }
    }

    public function drop($name)
    {
        $sql = 'DROP VIEW /*!50032 IF EXISTS */ `'.$name.'`';
        return $this->executeSql($sql);
    }
}
