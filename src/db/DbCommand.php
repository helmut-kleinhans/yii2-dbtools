<?php
namespace DbTools\db;

use mkubenka\dbreconnect\Command;

use Yii;

class DbCommand extends Command
{
    protected function processException($e)
    {
		$this->db->processException($e);
        $this->prepareForReconnect();
    }
}
