<?php
namespace DbTools\db\schemas;

use DbTools\DbToolsModule;
use Yii;

class DbSchemaProcedures extends DbSchemaBase
{
	const cType = 'procedures';
	protected $createdata;

    public function __construct(string $dbName, \yii\db\Connection $db)
	{
		parent::__construct($dbName, $db, self::cType);
	}

    protected function getList(): array
	{
		$query = (new \yii\db\Query())->select(['*'])->from('information_schema.routines')->where('ROUTINE_SCHEMA=DATABASE()')->andWhere(['ROUTINE_TYPE' => 'PROCEDURE']);
		$rows = $query->createCommand($this->db)->queryAll();
		$ret = [];
		foreach ($rows as $item)
		{
			$ret[$item['ROUTINE_NAME']]['helper'] = $item;
			$ret[$item['ROUTINE_NAME']]['body'] = $item['ROUTINE_DEFINITION'];
			$ret[$item['ROUTINE_NAME']]['params'] = DbSchemaParams::get($this->db, 'PROCEDURE', $item['ROUTINE_NAME']);
		}

		return $ret;
	}

	protected function getCreate(string $name): string
	{
		$row = $this->db->createCommand('SHOW CREATE PROCEDURE ' . $this->db->quoteTableName($name))->queryOne();
		if (isset($row['Create Procedure']))
		{
			$sql = $row['Create Procedure'];
		}
		else
		{
			$row = array_values($row);
			$sql = $row[1];
		}

        $full[] = 'DELIMITER ';
        $full[] = 'USE `'.$this->getDbName().'`';
		$full[] = $this->sqlDrop($name);
        $full[] = $sql;
        $full[] = 'DELIMITER ;';

		$sql = implode(DbToolsModule::getInstance()->exportDelimiter,$full);

		return $sql;
	}

    protected function doAdditionalInfo(array $data, array &$brief, array &$ret): void {
	    if(isset($data['helper'])) {
            if ($data['helper']['DEFINER'] != DbToolsModule::getInstance()->checkDefiner) {
                $ret['warnings'][] = 'DEFINER needs to be "' . DbToolsModule::getInstance()->checkDefiner . '"';
            }

            if ($data['helper']['SECURITY_TYPE'] != 'INVOKER') {
                $ret['warnings'][] = 'SECURITY TYPE needs to be "INVOKER"';
            }

            $orgparams = $data['params'];
            $param = $brief['param'];
            unset($brief['param']);

            if (!empty($orgparams) && $this->doFormat) {
                #first word is the parameter name, rest is comment
                $pbriefparams = [];
                if (!empty($param)) {
                    foreach ($param as $bdata) {
                        if (empty($bdata)) {
                            continue;
                        }
                        $line = $bdata[0];
                        $name = trim(substr($line, 0, strpos($line, ' ')));
                        if (empty($name)) {
                            $name = trim($line);
                            unset($bdata[0]);
                        }
                        else {
                            $bdata[0] = trim(substr($line, strlen($name) + 1));
                        }
                        $pbriefparams[$name] = implode('<br/>', $bdata);
                    }
                }

                $pret = '<h4>Params</h4>
<table class="table table-sm">
    <thead class="thead-default">
        <tr><th>InOut</th><th>Name</th><th>Type</th><th>Description</th><th>Warning</th></tr>
    </thead>
    <tbody class="tbody">
';
                $retparam = [];
                foreach ($orgparams as $ppos => $pdata) {
                    if ($ppos == 0) {
                        $retparam = $pdata;
                        continue;
                    }
                    $name = $pdata['PARAMETER_NAME'];
                    $pdesc = '';
                    if (isset($pbriefparams[$name]) && !empty($pbriefparams[$name])) {
                        $pdesc = $pbriefparams[$name];
                    }
                    $pret .= '
    <tr>
        <td><span class="label label-' . (stripos($pdata['PARAMETER_MODE'], 'in') !== false ? 'primary' : 'default') . '">&nbsp;IN&nbsp;</span><span class="label label-' . (stripos($pdata['PARAMETER_MODE'], 'out') !== false ? 'success' : 'default') . '">&nbsp;OUT&nbsp;</span></td>
        <td>' . $name . '</td><td>' . strtoupper($pdata['DTD_IDENTIFIER']) . '</td><td>' . $pdesc . '</td><td>&nbsp;</td>
    </tr>
';
                }
                if (!empty($retparam)) {
                    $pret .= '
    <tr>
        <td><span class="label label-danger">&nbsp;RETURN&nbsp;</span></td>
        <td>&nbsp;</td><td>' . strtoupper($retparam['DTD_IDENTIFIER']) . '</td><td>' . ((!empty($return)) ? $return : '') . '</td><td>&nbsp;</td>
    </tr>
';
                }
                $pret .= '
</tbody>
</table>';
                $brief['additionalInfo'][] = $pret;
            }
        }
    }

    protected function sqlDrop(string $name): string
    {
        return 'DROP PROCEDURE IF EXISTS `'.$name.'`';
    }
}
