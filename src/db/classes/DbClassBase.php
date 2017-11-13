<?php
namespace DbTools\db\classes;

use Yii;

class DbClassBase
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