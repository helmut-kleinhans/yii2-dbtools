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

    public function indexedSelect(string $select, string $key, array $format = NULL): array
    {
        if (!array_key_exists($select, $this->selectresults)) {
            throw new \Exception('selectresult unknown: ' . $select);
        }

        if (empty($key)) {
            throw new \Exception('key needs to be set');
        }

        $data = $this->selectresults[$select];

        if (empty($data)) {
            return [];
        }

        // check key
        if (!array_key_exists($key, $data[0])) {
            throw new \Exception('key is not available: key(' . $key . ') keys(' . implode(',', array_keys($data[0])) . ')');
        }

        // check format
        if (!empty($format)) {
            foreach ($format as $ok => $nk) {
                if (!array_key_exists($ok, $data[0])) {
                    throw new \Exception('format key is not available: key(' . $ok . ') keys(' . implode(',', array_keys($data[0])) . ')');
                }
            }
        }

        $ret = [];
        if (!empty($format)) {
            foreach ($data as $d) {
                $nd = [];
                foreach ($format as $ok => $nk) {
                    $nd[$nk] = $d[$ok];
                }
                $ret[$d[$key]] = $nd;
            }
        }
        else {
            foreach ($data as $d) {
                $ret[$d[$key]] = $d;
            }
        }

        return $ret;
    }
}
