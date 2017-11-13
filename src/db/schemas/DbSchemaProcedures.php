<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaProcedures extends DbSchemaBase
{
	const cType = 'procedures';
	protected $createdata;

	public function __construct($dbconname, $db)
	{
		parent::__construct($dbconname, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.routines')->where('ROUTINE_SCHEMA=DATABASE()')->andWhere(['ROUTINE_TYPE' => 'PROCEDURE']);
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
			$ret[$item['ROUTINE_NAME']]['helper'] = $item;
			$ret[$item['ROUTINE_NAME']]['body'] = $item['ROUTINE_DEFINITION'];
			$ret[$item['ROUTINE_NAME']]['params'] = DbSchemaParams::get($this->db, 'PROCEDURE', $item['ROUTINE_NAME']);
		}

		return $ret;
	}

	public function getCreate($name)
	{
		$row = $this->db->createCommand('SHOW CREATE PROCEDURE ' . $name)->queryOne();
		if (isset($row['Create Procedure']))
		{
			$sql = $row['Create Procedure'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.self::getDbName().'`';
		$full[] = 'DROP PROCEDURE IF EXISTS `'.$name.'`';
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

		$sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

	protected function _doInfo($data)
	{
		if (!isset($data['helper']))
		{
			return [];
		}
		$helper = $data['helper'];
		$brief = self::parseBrief($data['body'], $data['params'], $this->doFormat);

        $select=[];
        if (isset($brief['select'])) {
            $select = $brief['select'];
            unset($brief['select']);
        }

        $export=!empty($select);
        if (isset($brief['export']))
        {
            $export = true;
            unset($brief['export']);
        }

		$warnings = [];
		if (isset($brief['warnings']))
		{
			$warnings = $brief['warnings'];
			unset($brief['warnings']);
		}

		$declares = self::parseDeclares($data['body']);
		if (isset($declares['warnings']))
		{
			$warnings = yii\helpers\ArrayHelper::merge($warnings, $declares['warnings']);
			unset($declares['warnings']);
		}

        $head = self::parseHead($data['body'],$helper);
        if (isset($head['warnings']))
        {
            $warnings = yii\helpers\ArrayHelper::merge($warnings, $head['warnings']);
            unset($head['warnings']);
        }

		if (empty($brief) || !isset($brief['info']) || empty($brief['info']))
		{
			$brief['info'] = $helper['ROUTINE_COMMENT'];
		}

		$info = $brief['info'];
        unset($brief['info']);

		if(!empty($brief))
        {
            echo "forgot to process:\n";
            var_dump($brief);
            var_dump($data);
            die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
        }

		return ['text'     => $info,
				'declares' => $declares,
				'select' => $select,
                'warnings' => $warnings,
                'export' => $export,];
	}

    public static function parseHead($body,$data)
    {
        $ret = [];
        if($data['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
            $ret['warnings'][] = 'DEFINER needs to be "'.DbToolsModule::getInstance()->checkDefiner.'"';
        }

        if($data['SECURITY_TYPE'] != 'INVOKER') {
            $ret['warnings'][] = 'SECURITY TYPE needs to be "INVOKER"';
        }

        return $ret;
    }


    public function file2sql($name)
    {
        $filepath = $this->dir . '/' . $name . '.sql';

        if(!file_exists($filepath)) {
            throw new \Exception('file does not exist');
        }
        $data = file_get_contents($filepath);
        if(empty($data)) {
            throw new \Exception('empty data');
        }

        return $this->executeSql($data);
    }
}
