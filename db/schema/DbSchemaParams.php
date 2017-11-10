<?php
namespace kleinhans\modules\dbtools\db\schema;

use Yii;

class DbSchemaParams
{
	public static $params = [];

	public static function get($db, $type, $name)
	{
		self::read($db);
		if (!isset(self::$params[$db->dsn][$type]) || !isset(self::$params[$db->dsn][$type][$name]))
		{
			return [];
		}
		else
		{
			return self::$params[$db->dsn][$type][$name];
		}
	}

	private static function read($db)
	{
		if (isset(self::$params[$db->dsn]))
		{
			return;
		}
		self::$params[$db->dsn] = [];
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.parameters')->where('SPECIFIC_SCHEMA=DATABASE()')->orderBy(['ROUTINE_TYPE'     => SORT_ASC,
																																			'SPECIFIC_NAME'    => SORT_ASC,
																																			'ORDINAL_POSITION' => SORT_ASC]);
		$rows = $query->createCommand($db)->queryAll();
		foreach ($rows as $item)
		{
			self::$params[$db->dsn][$item['ROUTINE_TYPE']][$item['SPECIFIC_NAME']][$item['ORDINAL_POSITION']] = $item;
		}
	}
}