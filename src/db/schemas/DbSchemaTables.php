<?php
namespace DbTools\db\schemas;

use Yii;

class DbSchemaTables extends DbSchemaBase
{
    const cType = 'tables';
    const cDumpHeader = "\n\n#CONSTANT DUMP\n\n";
    private static $schema = [];

    public function __construct($dbName, $db)
    {
        parent::__construct($dbName, $db, self::cType);
    }

    public function getList()
    {
        $query = (new \yii\db\Query())->select(['*'])->from('information_schema.tables')->where('TABLE_SCHEMA=DATABASE()')->andWhere(['TABLE_TYPE' => 'BASE TABLE'])->andWhere('ENGINE!="MEMORY"');
        $rows = $query->createCommand($this->db)->queryAll();
        $ret = [];
        foreach ($rows as $item) {
            $ret[$item['TABLE_NAME']]['helper'] = $item;
            $ret[$item['TABLE_NAME']]['columns'] = DbSchemaColumns::get($this->db, $item['TABLE_NAME']);
            self::$schema[$item['TABLE_NAME']] = $item;
        }

        return $ret;
    }

    public function getCreate($name)
    {
        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $this->db->quoteTableName($name))->queryOne();
        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        }
        else {
            $row = array_values($row);
            $sql = $row[1];
        }
        $pos = strpos($sql, 'AUTO_INCREMENT=');
        if ($pos > 0) {
            $t = substr($sql, 0, $pos);
            $posend = strpos($sql, ' ', $pos + 15);
            if (!$posend) {
                throw new \Exception('AUTO_INCREMENT end not found: ' . $sql, 500);
            }
            $t .= substr($sql, $posend + 1);
            $sql = $t;
        }
        $pos = strpos($sql, 'CONNECTION=');
        if ($pos > 0) {
            $t = substr($sql, 0, $pos);
            $posend = strpos($sql, '\'', $pos + 13);
            if (!$posend) {
                throw new \Exception('CONNECTION end not found: ' . $sql, 500);
            }
            $t .= 'CONNECTION=\'________\'';
            $t .= substr($sql, $posend + 1);
            $sql = $t;
        }

        $sql = trim($sql).';';

        if (isset(self::$schema[$name])) {
            $comment = self::$schema[$name]['TABLE_COMMENT'];
        }
        else {
            $query = (new \yii\db\Query())->select(['TABLE_COMMENT'])->from('information_schema.tables')->where('TABLE_SCHEMA=DATABASE()')->andWhere(['TABLE_TYPE' => 'BASE TABLE'])->andWhere('ENGINE!="FEDERATED"')->andWhere(['TABLE_NAME' => $name]);
            $comment = $query->createCommand($this->db)->queryScalar();
        }
        if (strtoupper(substr($comment, 0, 8)) == 'CONSTANT') {
            $sql .= self::cDumpHeader . self::getDump($this->db, $name);
        }

        return $sql;
    }

    protected function _doInfo($data)
    {
        if (!isset($data['helper'])) {
            return [];
        }

        $declares = [];
        $warnings = [];
        $helper = $data['helper'];
        $header = '';
        $body = '';
        $useheader = [
            'ENGINE'          => 'Engine',
            'ROW_FORMAT'      => 'Format',
            'TABLE_ROWS'      => 'Rows',
            'AVG_ROW_LENGTH'  => 'AvgRowLength',
            'DATA_LENGTH'     => 'DataLength',
            'MAX_DATA_LENGTH' => 'MaxDataLength',
            'INDEX_LENGTH'    => 'IndexLength',
            'DATA_FREE'       => 'DataFree',
            'AUTO_INCREMENT'  => 'AutoIncrement',
            'CREATE_TIME'     => 'Created',
            'UPDATE_TIME'     => 'Updated',
            'CHECK_TIME'      => 'Checked',
            'TABLE_COLLATION' => 'Collation',
            'CREATE_OPTIONS'  => 'CreateOptions',
        ];
        foreach ($useheader as $hkey => $hname) {
            $hval = $helper[$hkey];
            if (empty($hval)) {
                continue;
            }
            $header .= '<th>' . $hname . '</th>';
            $body .= '<td>' . $hval . '</td>';
        }
        $info = '<h4>Table</h4>' . (!empty($helper['TABLE_COMMENT']) ? $helper['TABLE_COMMENT'] . '<br/>' : '') . '<table class="table table-sm">
 <thead class="thead-default"><tr>' . $header . '</tr></thead>
 <tbody class="tbody"><tr>' . $body . '
     </tr></tbody>
   </table>';
        /*
     ["TABLE_CATALOG"]=>    string(3) "def"
    ["TABLE_SCHEMA"]=>    string(8) "pokermem"
    ["TABLE_NAME"]=>    string(11) "tbl_account"
    ["COLUMN_NAME"]=>    string(7) "ACC_iID"
    ["ORDINAL_POSITION"]=>    string(1) "1"
    ["COLUMN_DEFAULT"]=>    NULL
    ["IS_NULLABLE"]=>    string(2) "NO"
    ["DATA_TYPE"]=>    string(3) "int"
    ["CHARACTER_MAXIMUM_LENGTH"]=>    NULL
    ["CHARACTER_OCTET_LENGTH"]=>    NULL
    ["NUMERIC_PRECISION"]=>    string(2) "10"
    ["NUMERIC_SCALE"]=>    string(1) "0"
    ["CHARACTER_SET_NAME"]=>    NULL
    ["COLLATION_NAME"]=>    NULL
    ["COLUMN_TYPE"]=>    string(16) "int(10) unsigned"
    ["COLUMN_KEY"]=>    string(3) "PRI"
    ["EXTRA"]=>    string(14) "auto_increment"
    ["PRIVILEGES"]=>    string(31) "select,insert,update,references"
    ["COLUMN_COMMENT"]=>    string(0) ""
 */
        $info .= '<h4>Columns</h4>
			<table class="table table-sm">
 <thead class="thead-default">
 <tr><th>Key</th><th>Name</th><th>Type</th><th>Default</th><th>Nullable</th><th>Extra</th><th>Comment</th></tr>
 </thead>
 <tbody class="tbody">';
        foreach ($data['columns'] as $column) {
            $colkey = strtoupper($column['COLUMN_KEY']);
            if (!empty($column['COLUMN_KEY'])) {
                switch ($colkey) {
                    case 'PRI':
                        $colclass = 'success';
                        break;
                    case 'UNI':
                        $colclass = 'primary';
                        break;
                    case 'MUL':
                        $colclass = 'warning';
                        break;
                }
                $colkey = '<span class="label label-' . $colclass . '">&nbsp;' . $colkey . '&nbsp;</span>';
            }
            $trclass = '';

            if (!empty($column['COLUMN_COMMENT']) && (stripos($column['COLUMN_COMMENT'], "Warning:") !== false)) {
                $warnings[] = $column['COLUMN_COMMENT'];
                $trclass = ' class="alert alert-danger"';
            }

            $info .= '<tr ' . $trclass . '><td>' . $colkey . '</td><td>' . $column['COLUMN_NAME'] . '</td><td>' . $column['COLUMN_TYPE'] . '</td><td>' . $column['COLUMN_DEFAULT'] . '</td><td>' . $column['IS_NULLABLE'] . '</td><td>' . $column['EXTRA'] . '</td><td>' . $column['COLUMN_COMMENT'] . '</td></tr>';
        }
        $info .= '</tbody>
   </table>';

        return [
            'text'     => $info,
            'declares' => $declares,
            'warnings' => $warnings,
            'body'     => isset($data['createdb']) ? $data['createdb'] : '',
        ];
    }

    public static function getDump($db, $table)
    {
        $primary='';
        $cols = [];
        $ret = '';
        $tablecols = DbSchemaColumns::get($db, $table);

        foreach($tablecols as $col) {
            $cols[] = $col['COLUMN_NAME'];
            if($col['COLUMN_KEY'] == 'PRI') {
                $primary = $col['COLUMN_NAME'];
            }
        }

        $query = (new \yii\db\Query())->select($cols)->from($table);
        if(!empty($primary)) {
            $query->orderBy([$primary => SORT_ASC]);
        }
        $rows = $query->createCommand($db)->queryAll();

        if(empty($rows)) {
            return $ret;
        }

        $bi = [];
        foreach ($rows as $item) {
            $bi[] = array_values($item);
        }
        if(empty($bi)) {
            return $ret;
        }

        $oQuery = $db->createCommand()->batchInsert($table, $cols, $bi);
        $ret=$oQuery->rawSql;

        $ret = str_replace(") VALUES (",") VALUES\n(",$ret);
        $ret = str_replace("), (","),\n(",$ret);


        $cv=[];
        foreach ($cols as $col) {
            $cv[]='`'.$col.'`=VALUES(`'.$col.'`)';
        }

        $ret.="\nON DUPLICATE KEY UPDATE\n".implode(",\n",$cv).';';

        return $ret;
    }

    public function file2sql($name) : array
    {
        throw new \Exception('not allowed');
    }

    public function drop($name)
    {
        $sql = 'DROP TABLE IF EXISTS `'.$name.'`';
        return $this->executeSql($sql);
    }
}
