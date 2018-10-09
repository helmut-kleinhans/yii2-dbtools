<?php
namespace DbTools\db\classes;

use DbTools\db\DbBlockReconnect;
use DbTools\db\DbConnection;
use DbTools\db\DbException;

class DbClassFunction extends DbClassBase
{
	protected $executeparams = [];
	protected $executebinds = [];
	protected $return = NULL;

	public function __construct($db, $name)
	{
		parent::__construct($db, $name);
	}

	protected function addParam($name, $value = NULL)
	{
		$this->paramsOrg[] = ['name'  => $name,
							  'value' => $value];
		$executeparam = ':' . $name;
		$this->executebinds[$executeparam] = $value;
		$this->executeparams[] = $executeparam;
	}

	public function execute()
	{
		try
		{
			$sStatement = "SET @fret = " . $this->name . "(" . implode(', ', $this->executeparams) . ")";
			$oQuery = $this->db->createCommand($sStatement);
			foreach ($this->executebinds as $k => $v)
			{
				$oQuery->bindValue($k, $v);
			}
			$oQuery->execute();

			$sStatement = "SELECT @fret";
			$oQuery = $this->db->createCommand($sStatement);

            if($this->db instanceof DbConnection) {
                $blockreconnect = new DbBlockReconnect($this->db);
                try {
                    $this->return = $oQuery->queryScalar();
                } finally {
                    unset($blockreconnect);
                }
            } else {
                $this->return = $oQuery->queryScalar();
            }
		}
        catch (\yii\db\Exception $e){
            throw new DbException($e);
        }
	}

	public function getReturn()
	{
		return $this->return;
	}
}
