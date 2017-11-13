<?php
namespace DbTools\db\schemas;

use Yii;

class DbSchemaViews extends DbSchemaTables
{
	const cType = 'views';

	public function __construct($dbconname, $db)
	{
		DbSchemaBase::__construct($dbconname, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.tables')->where('TABLE_SCHEMA=DATABASE()')->andWhere(['TABLE_TYPE' => 'VIEW']);
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
		$row = $this->db->createCommand('SHOW CREATE VIEW ' . $this->db->quoteValue($name))->queryOne();
		if (isset($row['Create View']))
		{
			$sql = $row['Create View'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

		return $sql;
	}
}
