<?php

namespace DbTools\db\schemas;

use Yii;

class DbSchemaColumns
{
    /** @var array */
    private static $cols = [];

    public static function get(\yii\db\Connection $db, string $name): array
    {
        self::read($db);
        if (!isset(self::$cols[$db->dsn][$name])) {
            return [];
        }
        else {
            return self::$cols[$db->dsn][$name];
        }
    }

    private static function read(\yii\db\Connection $db): void
    {
        if (isset(self::$cols[$db->dsn])) {
            return;
        }
        self::$cols[$db->dsn] = [];
        $query = (new \yii\db\Query())->select(['*'])->from('information_schema.columns')->where('TABLE_SCHEMA=DATABASE()')->orderBy([
                                                                                                                                         'TABLE_NAME'       => SORT_ASC,
                                                                                                                                         'ORDINAL_POSITION' => SORT_ASC,
                                                                                                                                     ]);
        $rows = $query->createCommand($db)->queryAll();
        foreach ($rows as $item) {
            self::$cols[$db->dsn][$item['TABLE_NAME']][$item['ORDINAL_POSITION']] = $item;
        }
    }
}