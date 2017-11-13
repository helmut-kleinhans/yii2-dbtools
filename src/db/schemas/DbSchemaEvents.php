<?php
namespace DbTools\db\schemas;

use Yii;

class DbSchemaEvents extends DbSchemaBase
{
	const cType = 'events';

	public function __construct($dbconname, $db)
	{
		parent::__construct($dbconname, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.Events')->where('EVENT_SCHEMA=DATABASE()');
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
			$ret[$item['EVENT_NAME']]['helper'] = $item;
			var_dump($ret);
			die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}

		return $ret;
	}

	public function getCreate($name)
	{
		$row = $this->db->createCommand('SHOW CREATE EVENT ' . $this->db->quoteValue($name))->queryOne();
		var_dump($row);
		die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
		if (isset($row['Create Event']))
		{
			$sql = $row['Create Event'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

		return $sql;
	}
}
