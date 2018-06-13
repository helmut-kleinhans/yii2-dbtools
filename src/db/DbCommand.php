<?php
namespace DbTools\db;

use mkubenka\dbreconnect\Command;

use Yii;

class DbCommand extends Command
{
    protected function processException($e)
    {
        if ($this->db->getTransaction() !== null) {
            throw $e;
        }

        Yii::info('Lost connection('.$e->getCode().'): ' . $e->getMessage(), __METHOD__);

        if (true === $this->db->isMaxReconnect()) {
            Yii::error('ReconnectCounter is max', __METHOD__);
            throw $e;
        }

        $this->db->reconnect();
        $this->db->incrementReconnectCount();
        $this->prepareForReconnect();
    }
}
