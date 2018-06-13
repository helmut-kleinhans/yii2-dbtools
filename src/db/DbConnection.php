<?php
namespace DbTools\db;

use mkubenka\dbreconnect\Connection;

class DbConnection extends Connection
{
    /**
     * @var string
     */
    public $commandClass = 'DbTools\db\DbCommand';
}
