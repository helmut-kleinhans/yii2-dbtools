<?php
namespace DbTools\db\classes;

use DbTools\db\schemas\DbSchemaBase;
use DbTools\db\schemas\DbSchemaFunctions;
use DbTools\db\schemas\DbSchemaProcedures;
use DbTools\helper\HelperGlobal;
use DbTools\DbToolsModule;
use Yii;
use yii\helpers\FileHelper;

class DbGenClasses
{
    public $dir;

    public function __construct()
    {
        $this->dir = DbToolsModule::getInstance()->exportPath . '/dbclasses';
        if(!file_exists($this->dir)) {
            @mkdir($this->dir, 0777,true);
        }
    }

    public function create()
    {
        $dbs = HelperGlobal::getDBs();

        foreach ($dbs as $dbName => $db) {
            FileHelper::removeDirectory($this->dir . '/' . $dbName);
            FileHelper::createDirectory($this->dir . '/' . $dbName);
            $this->createDb($dbName, $db);
        }
    }

    private function createDb($dbName, $db)
    {
        $ret = [];
        $classes = [];
        $classes[DbSchemaProcedures::cType] = new DbSchemaProcedures($dbName, $db);
        $classes[DbSchemaFunctions::cType] = new DbSchemaFunctions($dbName, $db);

        foreach ($classes as $type => $c) {
            $c->doFormat = false;
            $c->doCreate = false;
            $c->doOnlySvn = false;
            $ret['data'][$type] = $c->info();
        }
        $search4use = [];
        foreach ($ret['data'] as $type => $v) {
            foreach ($v as $name => $vd) {
                $search4use[$name] = $type;
            }
        }
        foreach ($classes as $type => $c) {
            $ret['data'][$type] = $c->findUses($ret['data'][$type], $search4use);
        }
        $ret['data'] = DbSchemaBase::mergeData($ret['data']);

        foreach ($ret['data'] as $type => $adata) {
            if (empty($adata)) {
                continue;
            }

            foreach ($adata as $aname => $data) {
                if (!isset($data['parse']) || !isset($data['parse']['export']) || empty($data['parse']['export'])) {
                    continue;
                }
                $this->createClass($dbName, $aname, $type, $data);
            }
        }
    }

    private function createClass($dbName, $name, $type, $data)
    {
        $filepath = $this->dir . '/' . $dbName . '/';
        $classname = $dbName;

        if ($type == DbSchemaProcedures::cType) {
            $extends = 'DbClassProcedure';
            $classname .= 'Proc';
        }
        else if ($type == DbSchemaFunctions::cType) {
            $extends = 'DbClassFunction';
            $classname .= 'Func';
        }
        else {
            throw new \Exception('unknown type:' . $type);
        }
        $classname .= $name;
        $filepath .= $classname . '.php';

        $addparam = [];
        $cparams = [];
        $members = [];
        $getout = [];
        $selects = [];
        $errors = [];
        $constselects = [];
        $functions = [];

        $retparam = [];
        foreach ($data['params'] as $ppos => $pdata) {
            if ($ppos == 0) {
                $retparam = $pdata;
                continue;
            }
            $pname = $pdata['PARAMETER_NAME'];
            $pmode = $pdata['PARAMETER_MODE'];
            //$ptype = $pdata['PARAMETER_MODE'];

            $addparam[] = self::toAddParam(($type == DbSchemaFunctions::cType) ? 'NONE' : $pmode, $pname);

            switch ($pmode) {
                case 'IN':
                    $cparams[] = self::toConstructorParam($pname);
                    break;
                case 'OUT':
                    $members[] = self::toMember($pname);
                    $getout[] = self::toGetParam($pname);
                    break;
                case 'INOUT':
                    $cparams[] = self::toConstructorParam($pname);
                    $members[] = self::toMember($pname);
                    $getout[] = self::toGetParam($pname);
                    break;
            }
        }

        /*
        foreach ($data['merged']['select'] as $select) {
            $selects[] = self::toSelect($select);
            $constselects[] = self::toConstSelect($select);
            $functions[] = self::toFuncSelect($select);
        }*/
        if (isset($data['parse']['select']) && !empty($data['parse']['select'])) {
            foreach ($data['parse']['select'] as $select) {
                $selects[] = self::toSelect($select);
                $constselects[] = self::toConstSelect($select);
                $functions[] = self::toFuncSelect($select);
            }
        }

        foreach ($data['merged']['errors'] as $k => $v) {
            $ename = $v['name'];
            $evalue = $v['value'];
            if (isset($errors[$evalue])) {
                continue;
            }

            $errors[$evalue] = self::toError($ename, $evalue);
        }

        ksort($errors);

        $out[] = '<?php
        
namespace DbToolsExport\\dbclasses\\' . $dbName . ';

use Yii;
use DbTools\\db\classes\\' . $extends . ';

class ' . $classname . ' extends ' . $extends . '
{';
        if (!empty($errors)) {
            foreach ($errors as $v) {
                $out[] = "\t" . $v;
            }
            $out[] = '';
        }
        if (!empty($constselects)) {
            foreach ($constselects as $v) {
                $out[] = "\t" . $v;
            }
            $out[] = '';
        }
        if (!empty($members)) {
            foreach ($members as $v) {
                $out[] = "\t" . $v;
            }
            $out[] = '';
        }

        $out[] = "\tconst ErrorCodes = [";
        if (!empty($errors)) {
            foreach ($errors as $k => $v) {
                $out[] = "\t\t" . $k . ',';
            }
        }
        $out[] = "\t];";
        $out[] = '';

        $out[] = '	public function __construct(' . implode(",", $cparams) . ')
	{
		parent::__construct(Yii::$app->' . $dbName . ',\'' . $name . '\');';

        foreach ($addparam as $v) {
            $out[] = "\t\t" . $v;
        }
        foreach ($selects as $v) {
            $out[] = "\t\t" . $v;
        }
        $out[] = '	}';

        if (!empty($getout)) {
            $out[] = '	public function execute()
	{
		parent::execute();';
            foreach ($getout as $v) {
                $out[] = "\t\t" . $v;
            }
            $out[] = '	}';
        }

        if (!empty($functions)) {
            foreach ($functions as $v) {
                $out[] = $v;
            }
            $out[] = '';
        }

        $out[] = '}';

        $out = implode("\n", $out);
        file_put_contents($filepath, $out);
    }

    private static function toMember($name)
    {
        return 'public $' . $name . ' = NULL;';
    }

    private static function toSelect($name)
    {
        return '$this->addSelect(\'' . strtolower($name) . '\');';
    }

    private static function toAddParam($mode, $name)
    {
        switch ($mode) {
            case 'NONE':
                return '$this->addParam(\'' . $name . '\',$' . $name . ');';
            case 'IN':
                return '$this->addParam(self::eP_In,\'' . $name . '\',$' . $name . ');';
            case 'OUT':
                return '$this->addParam(self::eP_Out,\'' . $name . '\');';
            case 'INOUT':
                return '$this->addParam(self::eP_InOut,\'' . $name . '\',$' . $name . ');';
            default:
                throw new \Exception('unknown inouttype:' . $mode);
        }
    }

    private static function toConstructorParam($name)
    {
        return '$' . $name;
    }

    private static function toGetParam($name)
    {
        return '$this->' . $name . ' = $this->outresults[\'@' . $name . '\'];';
    }

    private static function toError($name, $value)
    {
        return 'const ' . $name . ' = ' . $value . ';';
    }

    private static function toConstSelect($name)
    {
        $cname = strtoupper(substr($name, 0, 1)) . substr($name, 1);

        return 'const eSelect_' . $cname . ' = \'' . strtolower($name) . '\';';
    }

    private static function toFuncSelect($name)
    {
        $cname = strtoupper(substr($name, 0, 1)) . substr($name, 1);

        return '	public function getSelect' . $cname . '() {
		return $this->selectresults[self::eSelect_' . $cname . '];
	}';
    }
}
