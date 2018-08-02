<?php
namespace DbTools\db\classes;

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
                $this->db->setNoReconnect();
            }
            $this->return = $oQuery->queryScalar();
		}
        catch (\Throwable $e){
            throw new DbException($e);
        }
	}

	public function getReturn()
	{
		return $this->return;
	}
}
