<?php

namespace DbTools\db;

use Yii;
use yii\db\Connection;

class DbLock
{
    /** @var Connection */
    private $db;
    /** @var string */
    private $key;
    /** @var int */
    private $timeout;
    /** @var bool */
    private $isLocked = false;

    public function __construct(Connection $db, string $key, int $timeout = -1)
    {
        $this->db = $db;
        $this->key = $key;
        $this->timeout = $timeout;
    }

    public function __destruct()
    {
        $this->release();
    }

    public function get(): void
    {
        if ($this->isLocked) {
            return;
        }

        Yii::info('get lock db[' . $this->key . ']', 'dblock');
        $statement = "SELECT GET_LOCK('" . $this->key . "'," . $this->timeout . ")";
        $query = $this->db->createCommand($statement);
        $res = $query->queryScalar();

        var_dump($res);

        if ($res == 1) {
            $this->isLocked = true;

            return;
        }
        else if ($res == 0) {
            throw new \Exception('lock timeout');
        }
        else {
            throw new \Exception('lock failed');
        }
    }

    public function release(): void
    {
        if (!$this->isLocked) {
            return;
        }

        Yii::info('release lock db[' . $this->key . ']', 'dblock');
        $statement = "RELEASE_LOCK('" . $this->key . "')";
        $query = $this->db->createCommand($statement);
        $query->execute();
    }
}
