<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaEvents extends DbSchemaBase
{
	const cType = 'events';

    public function __construct(string $dbName, \yii\db\Connection $db)
	{
		parent::__construct($dbName, $db, self::cType);
	}

	protected function getList(): array
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.events')->where('EVENT_SCHEMA=DATABASE()');
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
            $ret[$item['EVENT_NAME']]['helper'] = $item;
            $ret[$item['EVENT_NAME']]['body'] = $item['EVENT_DEFINITION'];
		}

		return $ret;
	}

	protected function getCreate(string $name): string
	{
		$row = $this->db->createCommand('SHOW CREATE EVENT ' . $this->db->quoteTableName($name))->queryOne();

		if (isset($row['Create Event']))
		{
			$sql = $row['Create Event'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.$this->getDbName().'`';
        $full[] = $this->sqlDrop($name);
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

        $sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

    protected function doAdditionalInfo(array $data, array &$brief, array &$ret): void {
        if(isset($data['helper'])) {
            if ($data['helper']['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
                $ret['warnings'][] = 'DEFINER needs to be "' . DbToolsModule::getInstance()->checkDefiner . '"';
            }
        }
    }

    protected function sqlDrop(string $name): string
    {
        return 'DROP EVENT /*!50032 IF EXISTS */ `'.$name.'`';
    }
}
