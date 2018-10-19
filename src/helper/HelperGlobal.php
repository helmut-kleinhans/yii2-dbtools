<?php

namespace DbTools\helper;

use Yii;

class HelperGlobal
{
    public static function getDBs(): array
    {
        $comps = Yii::$app->getComponents();
        $dbs = [];
        foreach ($comps as $name => $comp) {
            if (empty($comp) || !isset($comp['class']) || ($comp['class'] != 'yii\db\Connection' && $comp['class'] != 'DbTools\db\DbConnection')) {
                continue;
            }
            $dbs[$name] = Yii::$app->$name;
        }

        return $dbs;
    }

    public static function paramNeeded(array $array, string $param)
    {
        if (empty($array) || !isset($array[$param])) {
            throw new \Exception('Needed param is missing: ' . $param);
        }

        return $array[$param];
    }

    public static function paramOptional(array $array, string $param, $def)
    {
        if (empty($array) || !isset($array[$param])) {
            return $def;
        }

        return $array[$param];
    }
}
