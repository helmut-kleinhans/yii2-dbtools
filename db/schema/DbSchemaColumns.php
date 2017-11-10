<?php
namespace kleinhans\modules\dbtools\db\schema;

use Yii;

class DbSchemaColumns
{
	public static $cols = [];

	public static function get($db, $name)
	{
		self::read($db);
		if (!isset(self::$cols[$db->dsn][$name]))
		{
			return [];
		}
		else
		{
			return self::$cols[$db->dsn][$name];
		}
	}

	private static function read($db)
	{
		if (isset(self::$cols[$db->dsn]))
		{
			return;
		}
		self::$cols[$db->dsn] = [];
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.columns')->where('TABLE_SCHEMA=DATABASE()')->orderBy(['TABLE_NAME'       => SORT_ASC,
																																	  'ORDINAL_POSITION' => SORT_ASC]);
		$rows = $query->createCommand($db)->queryAll();
		foreach ($rows as $item)
		{
			self::$cols[$db->dsn][$item['TABLE_NAME']][$item['ORDINAL_POSITION']] = $item;
		}
	}
}