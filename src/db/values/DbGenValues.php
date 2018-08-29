<?php
namespace DbTools\db\values;

use DbTools\db\schemas\DbSchemaColumns;
use DbTools\DbToolsModule;
use Yii;

class DbGenValues
{
    const iMaxErrorValue = 65535;
    const cErrorDbType = 'SMALLINT UNSIGNED';

    public $dir;

    public function __construct()
    {
        $this->dir = DbToolsModule::getInstance()->exportPath . '/dbvalues';
        if(!file_exists($this->dir)) {
            @mkdir($this->dir, 0777,true);
        }
    }

    private static function camelCaseExplode($string, $lowercase = true) : string {
        /*
        /([A-Z][^A-Z]*)/ gives "My X M L Parsing Engine"
        /([A-Z][^A-Z]+)/ gives "My XML Parsing Engine"
        /([A-Z]+[^A-Z]*)/ gives "My XMLParsing Engine"
        */
        $array = preg_split('/([A-Z]+[^A-Z]*)/', $string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        if ($lowercase) array_map('strtolower', $array);
        return preg_replace('/\s\s+/', ' ',implode(' ',$array));
    }

    public function create()
    {
        $final = [];
        $xml = simplexml_load_file(DbToolsModule::getInstance()->xmlValues);
        // Find the customer
        $params = [
            'nextvalue' => 0,
            'cat'       => '',
            'cats'      => [],
        ];

        self::parseCat($final, $xml, $params);

        $msg = '';
        $dbtypes = '';
        $dblines = '';
        $keys = [
            'error' => '',
            'const' => '',
        ];

        $out = '<?php
namespace DbToolsExport\dbvalues;

class DbValues
{
';
        foreach ($final as $cat => $types) {
            $out .= "\t//{$cat}\n";
            $msg .= "\t\t\t//{$cat}\n";
            if (isset($types['error'])) {
                foreach ($types['error'] as $key) {
                    $out .= "\tconst eError_{$cat}_{$key['name']} = {$key['value']};\n";
                    $msg .= "\t\t\tself::eError_{$cat}_{$key['name']} => '{$key['msg']}',\n";
                    //$dbtypes .= "\t\t\t'eError_{$cat}_{$key['name']}' => '{$key['type']}',\n";
                    $dblines .= "DECLARE eError_{$cat}_{$key['name']} " . self::cErrorDbType . " DEFAULT {$key['value']};\n";
                    $keys['error'] .= "\t\t\t'eError_{$cat}_{$key['name']}',\n";
                }
            }
            if (isset($types['const'])) {
                foreach ($types['const'] as $key) {

                    if(strtolower($key['type'])=='string') {
                        $key['type'] = 'CHAR('.strlen($key['value']).')';
                        $key['value'] = "'".$key['value']."'";
                    }
                    $cout = "\tconst cConst_{$cat}_{$key['name']} = {$key['value']};";

                    if (!empty($key['msg'])) {
                        $cout = str_pad($cout, 80, ' ', STR_PAD_RIGHT) . "//{$key['msg']}";
                    }
                    $out .= $cout . "\n";
                    $datatype = self::shortType($key['type']);
                    $dbtypes .= "\t\t\t'cConst_{$cat}_{$key['name']}' => '{$datatype}',\n";
                    $dblines .= "DECLARE cConst_{$cat}_{$key['name']} {$datatype} DEFAULT {$key['value']};\n";
                    $keys['const'] .= "\t\t\t'cConst_{$cat}_{$key['name']}',\n";
                }
            }
        }
        $out .= "
\tconst ErrorMessages = [
{$msg}\t];

\tconst DbTypes = [
{$dbtypes}\t];

\tconst Keys = [
\t\t'error'=>[
{$keys['error']}\t\t],
\t\t'const'=>[
{$keys['const']}\t\t],];
";
        $out .= '
	public static function MessageByCode($errorcode)
	{
		if (!array_key_exists($errorcode, static::ErrorMessages))
		{
			return \'Unknown errorcode: \' . $errorcode;
		}

		return static::ErrorMessages[$errorcode];
	}
	';

        $out .= "
}
/*
{$dblines}
*/
";

        file_put_contents($this->dir . '/DbValues.php', $out);
    }

    private static function error($e, $p, $msg)
    {
        $name = (!empty($e) && isset($e['name'])) ? $e['name'] : 'UNKNOWN';
        $type = !empty($e) ? $e->getName() : 'UNKNOWN';
        throw new \Exception("Cat\t\t| " . implode(" / ", $p['cats']) . "
Node\t| $type
Name\t| $name
Error\t| $msg");
    }

    private static function dbvalue2name($v)
    {
        //$normal_characters = "a-zA-Z0-9\s`~!@#$%^&*()_+-={}|:;<>?,.\/\"\'\\\[\]";
        $normal_characters = "a-zA-Z0-9_";
        $n = preg_replace("/[^$normal_characters]/", '_', $v);

        $p = array_filter(explode('_', $n), 'strlen');

        $n = '';
        foreach ($p as $np) {
            $n .= strtoupper(substr($np, 0, 1)) . substr($np, 1);
        }

        return $n;
    }

    private static function parseCat(&$out, $in, $params)
    {
        if (!empty($in['name'])) {
            $params['cats'][] = trim($in['name'] . '');
        }
        if (!empty($params['cats'])) {
            $params['cat'] = implode('_', $params['cats']);
        }
        if (!empty($in['type'])) {
            $params['type'] = trim($in['type'] . '');
        }
        foreach ($in->enum as $node) {
            self::parseEnum($out, $node, $params);
        }
        foreach ($in->sql as $node) {
            self::parseSql($out, $node, $params);
        }
        foreach ($in->sqlenum as $node) {
            self::parseSqlEnum($out, $node, $params);
        }
        foreach ($in->cat as $node) {
            self::parseCat($out, $node, $params);
        }
        foreach ($in->error as $node) {
            self::parseError($out, $node, $params);
            $params['nextvalue']++;
        }
        foreach ($in->const as $node) {
            self::parseConst($out, $node, $params);
            $params['nextvalue']++;
        }
    }

    private static function parseEnum(&$out, $in, $params)
    {
        $params['nextvalue'] = intval($in['value']);
        if (!empty($in['type'])) {
            $params['type'] = trim($in['type'] . '');
        }
        self::parseCat($out, $in, $params);
    }

    private static function parseSql(&$out, $in, $params)
    {
        if (!isset($in['table'])) {
            self::error($in, $params, 'missing table');
        }
        $table = $in['table'] . '';

        if (!isset($in['name'])) {
            self::error($in, $params, 'missing name');
        }
        $name = $in['name'] . '';

        if (!isset($in['db'])) {
            self::error($in, $params, 'missing db');
        }
        $dbname = $in['db'] . '';
        $db = Yii::$app->$dbname;
        if (empty($db)) {
            self::error($in, $params, 'db does not exist: ' . $dbname);
        }

        if (!isset($in['colvalue'])) {
            self::error($in, $params, 'missing colvalue');
        }
        $colvalue = $in['colvalue'] . '';

        if (!isset($in['colname'])) {
            self::error($in, $params, 'missing colname');
        }
        $colname = $in['colname'] . '';

        $cols = DbSchemaColumns::get($db, $table);
        $colv = NULL;
        $coln = NULL;
        foreach ($cols as $col) {
            if ($col['COLUMN_NAME'] == $colname) {
                $coln = $col;
            }
            if ($col['COLUMN_NAME'] == $colvalue) {
                $colv = $col;
            }
        }
        if (empty($coln)) {
            self::error($in, $params, 'wrong colname: ' . $colname);
        }
        if (empty($colv)) {
            self::error($in, $params, 'wrong colvalue: ' . $colvalue);
        }

        //var_dump($coln);
        //var_dump($colv);

        $datatype = strtoupper($colv['COLUMN_TYPE']);
        $isstring = ($colv['NUMERIC_PRECISION'] === NULL);

        //var_dump($datatype);
        //var_dump($isstring);

        $query = (new \yii\db\Query())->select([
                                                   $colname,
                                                   $colvalue,
                                               ])->from($table)->orderBy([$colname => SORT_ASC]);
        $rows = $query->createCommand($db)->queryAll();
        //var_dump($rows);
        $ret = [];
        $params['cats'][] = $name;
        $params['cat'] = implode("_", $params['cats']);
        foreach ($rows as $item) {
            $n = self::dbvalue2name($item[$colname]);

            //var_dump($n);

            $v = $item[$colvalue];
            $v = str_replace("'", "\'", $v);
            if ($isstring) {
                $v = "'" . $v . "'";
            }

            $out[$params['cat']]['const'][] = [
                'value' => $v,
                'name'  => trim($n),
                'msg'   => $item[$colname],
                'type'  => $datatype,
            ];
        }
    }

    private static function parseSqlEnum(&$out, $in, $params)
    {
        if (!isset($in['table'])) {
            self::error($in, $params, 'missing table');
        }
        $table = $in['table'] . '';

        if (!isset($in['name'])) {
            self::error($in, $params, 'missing name');
        }
        $name = $in['name'] . '';

        if (!isset($in['db'])) {
            self::error($in, $params, 'missing db');
        }
        $dbname = $in['db'] . '';
        $db = Yii::$app->$dbname;
        if (empty($db)) {
            self::error($in, $params, 'db does not exist: ' . $dbname);
        }

        if (!isset($in['colenum'])) {
            self::error($in, $params, 'missing colenum');
        }
        $colenum = $in['colenum'] . '';

        $cols = DbSchemaColumns::get($db, $table);
        $cole = NULL;
        foreach ($cols as $col) {
            if ($col['COLUMN_NAME'] == $colenum) {
                $cole = $col;
            }
        }
        if (empty($cole)) {
            self::error($in, $params, 'wrong colname: ' . $colenum);
        }

        if ($cole['DATA_TYPE'] != 'enum') {
            self::error($in, $params, 'col is no enum: ' . $colenum);
        }

        $enums = substr($cole['COLUMN_TYPE'], 6, strlen($cole['COLUMN_TYPE']) - 8);
        if (empty($enums)) {
            self::error($in, $params, 'no enums found: ' . $colenum);
        }
        $enums = explode("','", $enums);
        if (empty($enums)) {
            self::error($in, $params, 'cant split enums: ' . $colenum);
        }

        $params['cats'][] = $name;
        $params['cat'] = implode("_", $params['cats']);
        foreach ($enums as $enum) {
            $n = self::dbvalue2name($enum);

            $out[$params['cat']]['const'][] = [
                'value' => "'" . $enum . "'",
                'name'  => trim($n),
                'msg'   => '',
                'type'  => 'CHAR(' . (strlen($enum) + 1) . ')',
            ];
        }
    }

    private static function parseError(&$out, $in, $params)
    {
        if (isset($in['value'])) {
            $value = $in['value'] . '';
        }
        else if (isset($params['nextvalue'])) {
            $value = $params['nextvalue'];
        }
        else {
            self::error($in, $params, 'missing error value');
        }
        if ($value >= self::iMaxErrorValue) {
            self::error($in, $params, 'value is too large');
        }
        $pkt = [
            'value' => $value,
            'name'  => trim($in['name'] . ''),
            'msg'   => trim($in . ''),
            'type'  => self::cErrorDbType,
        ];

        if(empty($pkt['msg'])) {
            $pkt['msg'] = self::camelCaseExplode(str_replace("_"," ",$params['cat'])) . ': '.self::camelCaseExplode(str_replace("_"," ",$pkt['name']));
        }

        $out[$params['cat']]['error'][] = $pkt;
    }

    private static function parseConst(&$out, $in, $params)
    {
        if (isset($in['value'])) {
            $value = $in['value'] . '';
        }
        else if (isset($in['bit'])) {
            $bits = array_filter(explode(',', $in['bit']), 'strlen');
            $value = 0;
            foreach ($bits as $bit) {
                $value = $value + pow(2, intval(trim($bit)));
            }
        }
        else if (isset($params['nextvalue'])) {
            $value = $params['nextvalue'];
        }
        else {
            self::error($in, $params, 'missing const value');
        }
        if (isset($in['type'])) {
            $type = $in['type'] . '';
        }
        else if (isset($params['type'])) {
            $type = $params['type'];
        }
        else {
            self::error($in, $params, 'missing type');
        }

        $out[$params['cat']]['const'][] = [
            'value' => $value,
            'name'  => trim($in['name'] . ''),
            'msg'   => trim($in . ''),
            'type'  => $type,
        ];
    }

    private static function shortType($type)
    {
        switch ($type) {
            case 'TINYINT(3)':
                return 'TINYINT';
            case 'TINYINT(3) UNSIGNED':
                return 'TINYINT UNSIGNED';
            case 'SMALLINT(5)':
                return 'SMALLINT';
            case 'SMALLINT(5) UNSIGNED':
                return 'SMALLINT UNSIGNED';
            case 'INT(10)':
                return 'INT';
            case 'INT(10) UNSIGNED':
                return 'INT UNSIGNED';
            case 'BIGINT(20)':
                return 'BIGINT';
            case 'BIGINT(20) UNSIGNED':
                return 'BIGINT UNSIGNED';
        }

        return $type;
    }
}
