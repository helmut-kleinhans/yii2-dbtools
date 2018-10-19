<?php

namespace DbTools\db\schemas;

use Yii;

class DbSchemaParams
{
    /** @var array */
    private static $params = [];

    public static function get(\yii\db\Connection $db, string $type, string $name): array
    {
        self::read($db);
        if (!isset(self::$params[$db->dsn][$type]) || !isset(self::$params[$db->dsn][$type][$name])) {
            return [];
        }
        else {
            return self::$params[$db->dsn][$type][$name];
        }
    }

    private static function read(\yii\db\Connection $db): void
    {
        if (isset(self::$params[$db->dsn])) {
            return;
        }
        self::$params[$db->dsn] = [];
        $query = (new \yii\db\Query())->select(['*'])->from('information_schema.parameters')->where('SPECIFIC_SCHEMA=DATABASE()')->orderBy([
                                                                                                                                               'ROUTINE_TYPE'     => SORT_ASC,
                                                                                                                                               'SPECIFIC_NAME'    => SORT_ASC,
                                                                                                                                               'ORDINAL_POSITION' => SORT_ASC,
                                                                                                                                           ]);
        $rows = $query->createCommand($db)->queryAll();
        foreach ($rows as $item) {
            self::$params[$db->dsn][$item['ROUTINE_TYPE']][$item['SPECIFIC_NAME']][$item['ORDINAL_POSITION']] = $item;
        }
    }
}