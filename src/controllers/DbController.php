<?php
namespace backend\controllers;

use autogen\db\GenDb;
use autogen\dbvalues\GenDbValues;
use common\components\SendMail;
use common\db\DbSchemaBase;
use common\db\DbSchemaEvents;
use common\db\DbSchemaFunctions;
use common\db\DbSchemaProcedures;
use common\db\DbSchemaTables;
use common\db\DbSchemaTriggers;
use common\db\DbSchemaViews;
use common\components\HelperGlobal;
use common\components\Params;
use common\dbevents\DbEvents;
use yii;
use common\components\BaseController;

function tryReplace($search, $replace, $subject)
{
	$new = str_ireplace($search, $replace, $subject);
	if ($new == $subject)
	{
		return false;
	}

	return $new;
}



class DbController extends BaseController
{
	public function behaviors()
	{
		return [];
	}

	public function actions()
	{
		return ['error' => ['class' => 'yii\web\ErrorAction',],];
	}

    public function actionSql2file()
    {
        $dbconname = $this->dbconname;
        $group = self::paramNeeded($_REQUEST, 'group');
        $name = self::paramNeeded($_REQUEST, 'name');
        if (empty($dbconname) || empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }
        $db = Yii::$app->$dbconname;
        if (empty($db))
        {
            throw new \Exception('db failed: ' . $dbconname, 500);
        }
        $c = NULL;
        switch ($group)
        {
            case DbSchemaProcedures::cType:
                $c = new DbSchemaProcedures($dbconname, $db);
                break;
            case DbSchemaTriggers::cType:
                $c = new DbSchemaTriggers($dbconname, $db);
                break;
            case DbSchemaTables::cType:
                $c = new DbSchemaTables($dbconname, $db);
                break;
            case DbSchemaEvents::cType:
                $c = new DbSchemaEvents($dbconname, $db);
                break;
            case DbSchemaViews::cType:
                $c = new DbSchemaViews($dbconname, $db);
                break;
            case DbSchemaFunctions::cType:
                $c = new DbSchemaFunctions($dbconname, $db);
                break;
            default:
                throw new \Exception('Unknown group: ' . $group, 500);
        }
        $c->sql2file($name);
    }

    public function actionFile2sql()
    {
        $dbconname = $this->dbconname;
        $group = self::paramNeeded($_REQUEST, 'group');
        $name = self::paramNeeded($_REQUEST, 'name');
        if (empty($dbconname) || empty($group) || empty($name))
        {
            throw new \Exception('Empty param', 500);
        }
        $db = Yii::$app->$dbconname;
        if (empty($db))
        {
            throw new \Exception('db failed: ' . $dbconname, 500);
        }
        $c = NULL;
        switch ($group)
        {
            case DbSchemaProcedures::cType:
                $c = new DbSchemaProcedures($dbconname, $db);
                break;
            case DbSchemaTriggers::cType:
                $c = new DbSchemaTriggers($dbconname, $db);
                break;
            case DbSchemaTables::cType:
                $c = new DbSchemaTables($dbconname, $db);
                break;
            case DbSchemaEvents::cType:
                $c = new DbSchemaEvents($dbconname, $db);
                break;
            case DbSchemaViews::cType:
                $c = new DbSchemaViews($dbconname, $db);
                break;
            case DbSchemaFunctions::cType:
                $c = new DbSchemaFunctions($dbconname, $db);
                break;
            default:
                throw new \Exception('Unknown group: ' . $group, 500);
        }
        $c->file2sql($name);
    }



    public function actionEvents()
    {
        new DbEvents();
    }


	public function actionCreatefederated()
	{
		$tbls = ['tbl_playerdetails'=>'fed_playerdetails',
				 'tbl_language'=>'fed_language',
				 'tbl_configdomainset4'=>'fed_configdomainset4',
				 'tbl_configdomain4'=>'fed_configdomain4',
                 'tbl_bonuscodes'=>'fed_bonuscodes',
                 'tbl_jobqueue'=>'fed_jobqueue',
				];
		/*
		//get all tbls from db
		$t = Yii::$app->dbSD->createCommand('SHOW TABLES')->queryAll() ;
		foreach ($t as $v)
		{
			$v = $v['Tables_in_pokerdisc'];
			if (substr($v, 0, 4) != 'tbl_')
			{
				continue;
			}
			//exclude view tables!
			switch ($v)
			{
				case 'tbl_affiliate':
				case 'tbl_country':
				case 'tbl_player': break;
				default: $tbls[] = $v;
			}
		}
		*/
		$results = [];
		$tableSD = new DbSchemaTables('dbSD', Yii::$app->dbSD);
		$tableMM = new DbSchemaTables('dbMM', Yii::$app->dbMM);
		foreach ($tbls as $tbl=>$tbl_fed)
		{
			/*
			$prefix = substr($tbl, 0, 4);
			if ($prefix != 'tbl_')
			{
				echo 'tblname wrong';
				var_dump($tbl);
				die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$tbl_fed = 'fed_' . substr($tbl, 4);
			*/
			$error = '';
			$ret = $tableSD->getCreate($tbl);

            if (!$ret)
            {
                echo 'SD SQL ERROR: ' . $error;
                var_dump($tbl);
                die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
            }

			$posc = strpos($ret,DbSchemaTables::cDumpHeader);
			if($posc!==false)
            {
                $ret=trim(substr($ret,0,$posc-1));
            } else {
                $ret=trim(substr($ret,0,strlen($ret)-1));
            }


            if (empty($ret))
			{
				echo 'SD return was empty';
				var_dump($tbl);
				die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			$results[$tbl] = 'OK';
			$res_org = $ret;
			$res_org = str_replace('= ', '=', $res_org);
			$res_org = str_replace(' =', '=', $res_org);
			$res_fed = $res_org;
			$res_fed = tryReplace($tbl, $tbl_fed, $res_fed);
			if (!$res_fed)
			{
				echo 'replace tablename failed';
				var_dump($res_fed);
				die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$tmp = tryReplace('ENGINE=INNODB', 'ENGINE=FEDERATED', $res_fed);
			if (!$tmp)
			{
				$tmp = tryReplace('ENGINE=MYISAM', 'ENGINE=FEDERATED', $res_fed);
				if (!$tmp)
				{
					$tmp = tryReplace('ENGINE=MRG_MyISAM', 'ENGINE=FEDERATED', $res_fed);
					if (!$tmp)
					{
						echo 'replace ENGINE failed ' . $res_fed;
						var_dump($res_fed);
						die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}
			$res_fed = $tmp;
			$pos = strrpos($res_fed, ') ENGINE');
			if (!$pos)
			{
				$pos = strrpos($res_fed, ')ENGINE');
				if (!$pos)
				{
					echo 'last params not found';
					var_dump($res_fed);
					die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
			$params = trim(substr($res_fed, $pos + 1));
			if (substr($params, 0, 6) != 'ENGINE')
			{
				echo 'params wrong?(' . $pos . ')"' . $params . '"';
				var_dump($res_fed);
				die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$compos = strpos($params, 'COMMENT=');
			if ($compos > 0)
			{
				$t = substr($params, 0, $compos);
				$composend = strpos($res_fed, '\'', $compos + 10);
				if (!$composend)
				{
					echo 'comment end not found';
					var_dump($res_fed);
					die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
				$t .= substr($params, $composend + 1);
				$params = trim($t);
			}
			$parts = explode(' ', $params);
			$params = [];
			foreach ($parts as $param)
			{
				if (empty($param))
				{
					continue;
				}
				$p = explode('=', $param);
				if (count($p) != 2)
				{
					switch ($param)
					{
						case 'DEFAULT':
							$params[] = $param;
							break;
						default:
							echo ('UNKNOWN param: "' . $param . '"');
							die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
				else
				{
					$key = strtoupper($p[0]);
					switch ($key)
					{
						case 'ENGINE':
							$params[] = 'ENGINE=FEDERATED';
							break;
						case 'COLLATE':
						case 'CHECKSUM':
						case 'DELAY_KEY_WRITE':
						case 'ROW_FORMAT':
						case 'PACK_KEYS':
						case 'CHARSET':
							$params[] = $param;
							break;
						case 'INSERT_METHOD':
						case 'UNION':
						case 'AUTO_INCREMENT':
							break;
						default:
							echo ('UNKNOWN key: ' . $param . "\n\n\n" . $res_fed);
							die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
					}
				}
			}

			$params[] = 'CONNECTION=\''.Params::get(Params::eDbFedConnection) . $tbl . '\'';
			$params = implode(" ", $params);
			$res_fed = substr($res_fed, 0, $pos + 1) . ' ' . $params;

			try
			{
				$ret = $tableMM->getCreate($tbl_fed);
			}
			catch (\Exception $e)
			{
				$ret = NULL;
			}

			if (!empty($ret))
			{

                $ret=trim(substr($ret,0,$posc-1));

				if ($ret == $res_fed)
				{
					$results[$tbl] = 'OK';
					continue;
				}
				//echo "Missmatch!\nSlave:$res_fed\nMaster:\n$ret";

				try
				{
					$ret = Yii::$app->dbMM->createCommand('DROP TABLE ' . $tbl_fed)->execute();
				}
				catch (\Exception $e)
				{
					$results[$tbl] = 'Drop error: ' . $e->getMessage();
					continue;
				}
			}
			else
			{
				//$results[$tbl] = 'New';//print_r($res_fed,true);
			}
			try
			{
				$ret = Yii::$app->dbMM->createCommand($res_fed)->execute();
			}
			catch (\Exception $e)
			{
				$results[$tbl] = 'Create error: ' . $e->getMessage();
				continue;
			}
			$results[$tbl] = 'Created';
		}
		echo "<pre>\n";
		foreach ($results as $tbl => $desc)
		{
			echo str_pad($tbl, 40, ' ') . " - $desc\n";
		}
		echo "</pre>";
		echo "<h1>CHECK SLAVE!!!! IT HAS STOPPED</h1>";
	}

	public function actionSendmail()
    {
        //$domainset = ArConfigDomainSet::findOne(1096);
        $sendmail = new SendMail([
                                     SendMail::IP_Domainset => 1096,
                                     SendMail::IP_PlayerId => 2001788,
                                     SendMail::IP_StandardMailPath =>'/player/registration'
                                 ]);
        //$sendmail->setStandardMailById(5427);
        //$sendmail->setLngId(AutoGenDbValues::cConst_Language_German);
        $ret = $sendmail->sendStandardMail();
        var_dump($ret);
    }
}
