<?php
namespace DbTools\db\classes;

use DbTools\db\DbBlockReconnect;
use DbTools\db\DbConnection;
use DbTools\db\DbException;

class DbClassProcedure extends DbClassBase
{
	const eP_In = 'IN';
	const eP_Out = 'OUT';
	const eP_InOut = 'INOUT';
	protected $setparams = [];
	protected $setbinds = [];
	protected $executeparams = [];
	protected $executebinds = [];
	protected $selects = [];
	protected $selectresults = [];
	protected $outparams = [];
	protected $outresults = [];

	public function __construct($db, $name)
	{
		parent::__construct($db, $name);
	}

	protected function addSelect($name)
	{
		$this->selects[] = $name;
	}

	protected function addParam($mode, $name, $value = NULL)
	{
		$this->paramsOrg[] = ['mode'  => $mode,
							  'name'  => $name,
							  'value' => $value];
		switch ($mode)
		{
			case self::eP_In:
				$executeparam = ':' . $name;
				$this->executebinds[$executeparam] = $value;
				break;
			case self::eP_Out:
				$executeparam = '@' . $name;
				$this->setparams[] = $executeparam . '=NULL';
				$this->outparams[] = $executeparam;
				break;
			case self::eP_InOut:
				$executeparam = '@' . $name;
				$setparam = ':' . $name;
				$this->setparams[] = $executeparam . '=' . $setparam;
				$this->setbinds[$setparam] = $value;
				$this->outparams[] = $executeparam;
				break;
			default:
			    throw new \Exception('unknown mode:' . $mode);
		}
		$this->executeparams[] = $executeparam;
	}

	public function execute()
	{
		try
		{
			if (!empty($this->setparams))
			{
				$sStatement = "SET " . implode(', ', $this->setparams);
				$oQuery = $this->db->createCommand($sStatement);
				foreach ($this->setbinds as $k => $v)
				{
					$oQuery->bindValue($k, $v);
				}
				$oQuery->execute();
			}
			$sStatement = "CALL " . $this->name . "(" . implode(', ', $this->executeparams) . ")";
			$oQuery = $this->db->createCommand($sStatement);
			foreach ($this->executebinds as $k => $v)
			{
				$oQuery->bindValue($k, $v);
			}
			if (empty($this->selects))
			{
				$oQuery->execute();
			}
			else
			{
                $resultSet = $oQuery->query();
				foreach ($this->selects as $select)
				{
					$this->selectresults[$select] = $resultSet->readAll();
                    $resultSet->nextResult();
				}
			}
			if (!empty($this->outparams))
			{
				$sStatement = "SELECT " . implode(', ', $this->outparams);
				$oQuery = $this->db->createCommand($sStatement);

                if($this->db instanceof DbConnection) {
                    $blockreconnect = new DbBlockReconnect($this->db);
                    try {
                        $this->outresults = $oQuery->queryOne();
                    } finally {
                        unset($blockreconnect);
                    }
                } else {
                    $this->outresults = $oQuery->queryOne();
                }
			}
		}
		catch (\yii\db\Exception $e){
            throw new DbException($e);
        }

    }

    public function getSelects() {
        return $this->selectresults;
    }
}
