<?php
namespace DbTools\db;

use mkubenka\dbreconnect\Connection;

use Yii;

class DbConnection extends Connection
{
    /**
     * @var string
     */
    public $commandClass = 'DbTools\db\DbCommand';
    
	/**
	 * ReCreates the PDO instance.
	 *
	 * @return PDO the pdo instance
	 * @throws Exception - Cannot connect to db
	 */
	protected function createPdoInstance()
	{
		try {
			return parent::createPdoInstance();

		} catch (\Exception $e) {
            $this->processException($e);		
			
			return $this->createPdoInstance();
		}
	}
	
	
    public function processException($e)
    {
        if($e->getCode() == 45000 || $e->getCode() == 42000) {
            throw $e;
        }

        if ($this->getTransaction() !== null) {
			Yii::error('is Transaction['.getmypid().'/'.$this->reconnectCurrentCount.']('.$e->getCode().'): ' . $e->getMessage(), __METHOD__);
            throw $e;
        }

        Yii::info('Lost connection['.getmypid().'/'.$this->reconnectCurrentCount.']('.$e->getCode().'): ' . $e->getMessage(), __METHOD__);

        if (true === $this->isMaxReconnect()) {
            Yii::error('ReconnectCounter is max', __METHOD__);
            throw $e;
        }

        $this->incrementReconnectCount();
        $this->reconnect();
    }

    public function setNoReconnect()
    {
        $this->reconnectCurrentCount = $this->reconnectMaxCount;
    }
}
