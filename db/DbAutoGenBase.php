<?php
namespace xxxautogen\db;

use Yii;

class DbAutoGenBase
{
	protected $db = NULL;
	protected $name = NULL;

	protected $paramsOrg = [];

	public function __construct($db,$name)
	{
		$this->db = $db;
		$this->name = $name;
	}

	public function getParams() {
		return $this->paramsOrg;
	}
}