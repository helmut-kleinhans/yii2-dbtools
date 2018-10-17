<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaTriggers extends DbSchemaBase
{
	const cType = 'triggers';

	public function __construct($dbName, $db)
	{
		parent::__construct($dbName, $db, self::cType);
	}

	public function getList()
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.triggers')->where('TRIGGER_SCHEMA=DATABASE()');
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
			$ret[$item['TRIGGER_NAME']]['helper'] = $item;
			$ret[$item['TRIGGER_NAME']]['body'] = $item['ACTION_STATEMENT'];
		}

		return $ret;
	}

	public function getCreate($name)
	{
		$row = $this->db->createCommand('SHOW CREATE TRIGGER ' . $this->db->quoteTableName($name))->queryOne();
		if (isset($row['SQL Original Statement']))
		{
			$sql = $row['SQL Original Statement'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[2];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.self::getDbName().'`';
        $full[] = 'DROP TRIGGER /*!50032 IF EXISTS */ `'.$name.'`';
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

        $sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

	public function findUses($data, $search4uses)
	{
		$result = parent::findUses($data, $search4uses);
		foreach ($result as $name => $value)
		{
			/*
			if(!isset($value['uses'][DbSchemaTables::cType]) || !in_array($value['helper']['EVENT_OBJECT_TABLE'], $value['uses'][DbTables::cType]))
			{
				$result[$name]['uses'][DbSchemaTables::cType][]=$value['helper']['EVENT_OBJECT_TABLE'];
			}
			*/
			if (isset($value['uses'][DbSchemaTables::cType]))
			{
				$result[$name]['uses'][DbSchemaTables::cType] = array_diff($value['uses'][DbSchemaTables::cType], [$value['helper']['EVENT_OBJECT_TABLE']]);
				if (empty($result[$name]['uses'][DbSchemaTables::cType]))
				{
					unset($result[$name]['uses'][DbSchemaTables::cType]);
				}
			}
		}

		return $result;
	}

    protected function _doAdditionalInfo(array $data, array &$brief, array &$ret)
    {
        if(isset($data['helper'])) {
            if ($data['helper']['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
                $ret['warnings'][] = 'DEFINER needs to be "' . DbToolsModule::getInstance()->checkDefiner . '"';
            }

            if ($this->doFormat) {

                $brief['additionalInfo'][] = '
<h4>Trigger</h4>
<table class="table table-sm">
     <thead class="thead-default">
        <tr><th>Event</th><th>Action</th><th>On</th></tr>
    </thead>
    <tbody class="tbody">
        <tr><td>' . $data['helper']['EVENT_MANIPULATION'] . '</td><td>' . $data['helper']['ACTION_TIMING'] . '</td><td>' . self::getLink($this->dbName, DbSchemaTables::cType, $data['helper']['EVENT_OBJECT_TABLE']) . '</td></tr>
    </tbody>
 </table>';
            }
        }
    }

    public function drop($name)
    {
        $sql = 'DROP TRIGGER /*!50032 IF EXISTS */ `'.$name.'`';
        return $this->executeSql($sql);
    }
}
