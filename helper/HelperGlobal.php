<?php
namespace kleinhans\modules\dbtools\helper;

use Yii;

class HelperGlobal
{
	public static function getDBs()
	{
		$comps = Yii::$app->getComponents();
		$dbs= [];
		foreach ($comps as $name=>$comp)
		{
			if(empty($comp) || !isset($comp['class']) || $comp['class'] != 'yii\db\Connection') continue;
			$dbs[$name]=Yii::$app->$name;
		}
		return $dbs;
	}

    public static function paramOptional($array, $param, $def)
    {
        if (empty($array) || !isset($array[$param]))
        {
            return $def;
        }

        return $array[$param];
    }
}
