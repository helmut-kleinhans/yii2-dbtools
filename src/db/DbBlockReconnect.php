<?php

namespace DbTools\db;

class DbBlockReconnect
{
    /** @var DbConnection */
    private $db;
    /** @var bool */
    private $oldState;

    public function __construct(DbConnection $db)
    {
        $this->db = $db;
        $this->oldState = $this->db->blockReconnect;
        $this->db->blockReconnect = true;
    }

    public function __destruct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->db->blockReconnect = $this->oldState;
    }
}
