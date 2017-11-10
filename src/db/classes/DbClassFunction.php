<?php
namespace kleinhans\modules\dbtools\db\classes;

use Yii;
use common\db\DbException;

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
			$this->return = $oQuery->queryScalar();
		}
		catch (\Exception $e)
		{
			throw new DbException($e);
		}
	}

	public function getReturn()
	{
		return $this->return;
	}
}