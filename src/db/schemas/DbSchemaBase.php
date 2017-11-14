<?php
namespace DbTools\db\schemas;

use DbToolsExport\dbvalues\DbValues;
use DbTools\db\values\DbCheckValues;
use DbTools\DbToolsModule;
use Yii;
use yii\helpers\FileHelper;

class DbSchemaBase
{
	public $dbconname;
	public $db;
	public $dir;
    public $doCreate=true;
    public $doFormat=true;
	public $doOnlySvn=false;

    private static $databases = [];

	public function __construct($dbconname, $db, $subdir)
	{
		$this->dbconname = $dbconname;
		$this->db = $db;
        $this->dir = DbToolsModule::getInstance()->exportPath . '/export/' . $dbconname . '/' . $subdir;
    }

    protected function getDbName()
    {
        $key = $this->db->dsn;
        if (isset(self::$databases[$key])) {
            return self::$databases[$key];
        }
        self::$databases[$key] = $this->db->createCommand('SELECT DATABASE()')->queryScalar();

        return self::$databases[$key];
    }

    public function info()
	{
		$ret = [];
		$files = [];

		if(!file_exists($this->dir)) {
            @mkdir($this->dir, 0777,true);
        }


		$fu = FileHelper::findFiles($this->dir, ['only' => ['*.sql']]);
		foreach ($fu as $value)
		{
			$fileName = substr(basename($value), 0, -4);
            $files[$fileName]['createfile'] = trim(str_replace("\r", "", file_get_contents($value)));
			$files[$fileName]['filepath'] = $value;
		}

		try
		{
			$list = $this->getList();
		}
		catch (\Exception $e)
		{
			var_dump($e);
			die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
		}


		if($this->doOnlySvn) {
			foreach ($files as $name => $value) {
				if(!isset($list[$name])) continue;
				$ret[$name] = yii\helpers\ArrayHelper::merge($value, $list[$name]);
			}
			foreach ($list as $name => $value) {
				if(in_array($name,$files)) continue;
				unset($list[$name]);
			}
		} else {
			$ret = yii\helpers\ArrayHelper::merge($files, $list);
		}

		if (!empty($list) && $this->doCreate)
		{
			foreach ($list as $name => $value)
			{
				try
				{
                    $ret[$name]['createdb'] = trim(str_replace("\r", "", $this->getCreate($name)));
				}
				catch (\Exception $e)
				{
					$ret[$name]['warnings'][] = $e->getCode() . '-' . $e->getMessage();
					var_dump($e);
					die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
				}
			}
		}
		$ret = $this->buildInfo($ret);

		return $ret;
	}

	public function getList()
	{
		throw new \Exception('needs to be implemented: ' . __FUNCTION__, 500);
	}

	public function getCreate($name)
	{
		throw new \Exception('needs to be implemented: ' . __FUNCTION__, 500);
	}

	public function buildInfo($data)
	{
		$ret = $data;
		foreach ($data as $name => $value)
		{
			$ret[$name]['parse'] = $this->_doInfo($value);
			if (isset($ret[$name]['parse']['warnings']))
			{
				$ret[$name]['warnings'] = $ret[$name]['parse']['warnings'];
				unset($ret[$name]['parse']['warnings']);
			}
			if (isset($ret[$name]['parse']['body']))
			{
				$ret[$name]['body'] = $ret[$name]['parse']['body'];
				unset($ret[$name]['parse']['body']);
			}
		}

		return $ret;
	}

	protected function _doInfo($data)
	{
		return $data;
	}

	public function findUses($data, $search4uses)
	{
		$result = $data;
		foreach ($data as $name => $value)
		{
			if (!isset($value['body']))
			{
				continue;
			}
			$result[$name]['uses'] = self::findUsesFor($value['body'], $name, $search4uses);
		}

		return $result;
	}

	public function finalize($data)
	{
		$result = [];
		foreach ($data as $name => $value)
		{
			$info = isset($value['info']) ? $value['info'] : [];
			if (empty($info))
			{
				$info = [];
			}
			if (isset($value['parse']) && !empty($value['parse']) && !empty($value['parse']['text']))
			{
				$info[] = $value['parse']['text'];
			}

            $errors = isset($value['merged']['errors'])?$value['merged']['errors']:[];
            $uses = isset($value['merged']['uses'])?$value['merged']['uses']:[];
            $usedBy = isset($value['usedBy'])?$value['usedBy']:[];
            $selects = isset($value['merged']['select'])?$value['merged']['select']:[];
            if (!empty($selects))
            {
                if (count($selects) != count($value['parse']['select']))
                {
                    $value['warnings'][] = 'Select count missmatch! should be ' . count($selects) . ' but is ' . count($value['parse']['select']);
                }
            }
            if (!empty($uses))
            {
                $header = '';
                $body = '';
                foreach ($uses as $ttype => $tval)
                {
                    $header .= '<th>' . $ttype . '</th>';
                    $body .= '<td><ul>';
                    sort($tval);
                    foreach ($tval as $tname)
                    {
                        $body .= '<li>' . self::getLink($this->dbconname, $ttype, $tname) . '</li>';
                    }
                    $body .= '</ul></td>';
                }
                $ret = '<h4>Uses</h4><table class="table table-sm">
<thead class="thead-default"><tr>' . $header . '</tr></thead>
<tbody class="tbody"><tr>' . $body . '
 </tr></tbody>
</table>';
                $info[] = $ret;
            }
            if (!empty($usedBy))
            {
                $header = '';
                $body = '';
                foreach ($usedBy as $ttype => $tval)
                {
                    $header .= '<th>' . $ttype . '</th>';
                    $body .= '<td><ul>';
                    sort($tval);
                    foreach ($tval as $tname)
                    {
                        $body .= '<li>' . self::getLink($this->dbconname, $ttype, $tname) . '</li>';
                    }
                    $body .= '</ul></td>';
                }
                $ret = '<h4>Used By</h4><table class="table table-sm">
 <thead class="thead-default"><tr>' . $header . '</tr></thead>
 <tbody class="tbody"><tr>' . $body . '
     </tr></tbody>
   </table>';
                $info[] = $ret;
            }
            if (!empty($errors))
            {
                $body = '';
                foreach ($errors as $error)
                {
                    $body .= '<tr' . (!empty($error['warning']) ? ' class="alert alert-danger"' : '') . '><td>' . $error['name'] . '</td><td>' . $error['value'] . '</td><td>' . $error['message'] . '</td><td>';
                    $list = '';
                    foreach ($error['uses'] as $ttype => $tval)
                    {
                        $slist = '';
                        foreach ($tval as $tname)
                        {
                            if ($tname == $name)
                            {
                                continue;
                            }
                            $slist .= '<li>' . self::getLink($this->dbconname, $ttype, $tname) . '</li>';
                        }
                        $list .= empty($slist) ? '' : '<li>' . $ttype . '</li><ul>' . $slist . '</ul>';
                    }
                    $body .= (empty($list)) ? '&nbsp;' : '<ul>' . $list . '</ul>';
                    $body .= '</td><td>' . $error['warning'] . '</td></tr>';
                }
                $ret = '<h4>Errors</h4>
<table class="table table-sm">
<thead class="thead-default">
<tr><th>Name</th><th>Value</th><th>Description</th><th>In</th><th>Warning</th></tr>
</thead>
<tbody class="tbody">' . $body . '
 </tbody>
</table>';
                $info[] = $ret;
            }

			$result[$name]['createdb'] = (isset($value['createdb']) ? $value['createdb'] : '');
			$result[$name]['createfile'] = (isset($value['createfile']) ? $value['createfile'] : '');
			$result[$name]['info'] = trim(implode('', $info));
			$result[$name]['filepath'] = (isset($value['filepath']) ? $value['filepath'] : '');
			if (isset($value['warnings']) && !empty($value['warnings']))
			{
				$result[$name]['warnings'] = '<ul><li>' . implode('</li><li>', $value['warnings']) . '</li></ul>';
			}
			else
			{
				$result[$name]['warnings'] = '';
			}
		}

		return $result;
	}

    public function sql2file($name)
    {
        $create = $this->getCreate($name);
        if (!$create)
        {
            throw new \Exception('getCreate failed', 500);
        }
        $filepath = $this->dir . '/' . $name . '.sql';
        if (!file_put_contents($filepath, $create))
        {
            throw new \Exception('failed to write file: ' . $name, 500);
        }
    }

    public function file2sql($name)
    {
        throw new \Exception('not allowed');
    }

	public static function getLink($db, $group, $name)
	{
		return '<button type="button" class="btn btn-link" onclick="selectItem(\'' . $db . '|' . $group . '|' . $name . '\');">' . $name . '</button>';
	}

    private static function brief2array($info)
    {
        $ret = [];
        $info = str_replace("\t", " ", $info);
        $infos = explode("\n", $info);
        $currentcat = 'brief';
        $knowncat = [
            '@brief'      => 'brief',
            '/brief'      => 'brief',
            '@param'      => 'param',
            '/param'      => 'param',
            '@returns'    => 'return',
            '/returns'    => 'return',
            '@return'     => 'return',
            '/return'     => 'return',
            '@warnings'   => 'warnings',
            '/warnings'   => 'warnings',
            '@warning'    => 'warnings',
            '/warning'    => 'warnings',
            '@notes'      => 'note',
            '/notes'      => 'note',
            '@note'       => 'note',
            '/note'       => 'note',
            '@select'     => 'select',
            '/select'     => 'select',
            '@export'     => 'export',
            '/export'     => 'export',
            '@todo'       => 'todo',
            '/todo'       => 'todo',
            '@deprecated' => 'deprecated',
            '/deprecated' => 'deprecated',
        ];
        $current = [];
        foreach ($infos as $row) {
            $row = trim($row);
            if (empty($row)) {
                continue;
            }
            if (substr($row, 0, 1) != '@' && substr($row, 0, 1) != '/') {
                $current[] = $row;
                continue;
            }
            $found = false;
            foreach ($knowncat as $k => $d) {
                if (substr($row, 0, strlen($k)) == $k) {
                    $ret[$currentcat][] = $current;
                    $currentcat = $d;
                    $current = [];
                    $current[] = trim(substr($row, strlen($k)));
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }
            $ret[$currentcat][] = $current;
            $currentcat = 'unknown';
            $current[] = $row;
            echo 'Unknown Brief Type!';
            echo '<pre>';
            var_dump($info);
            var_dump($row);
            echo '/<pre>';
            die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
        }
        if (!empty($current)) {
            $ret[$currentcat][] = $current;
        }
        return $ret;
    }
    private static function brief2text($key,&$in, &$out)
    {
        if (!isset($in[$key]) || empty($in[$key])) return false;

        $pret = '';
        foreach ($in[$key] as $bdata) {
            if (empty($bdata)) {
                continue;
            }
            $pret .= implode("\n", $bdata);
        }
        unset($in[$key]);
        if (empty($pret)) {
            return true;
        }
        $out[$key] = $pret;
        return true;
    }
    private static function brief2list($key,&$in, &$out)
    {
        if (!isset($in[$key]) || empty($in[$key])) return false;

        $pret = [];
        foreach ($in[$key] as $bdata) {
            if (empty($bdata)) {
                continue;
            }
            foreach ($bdata as $li) {
                if (empty($bdata)) {
                    continue;
                }
                $pret[] = $li;
            }
        }
        unset($in[$key]);
        if (empty($pret)) {
            return true;
        }
        $out[$key] = $pret;
        return true;
    }

	public static function parseBrief($body, $orgparams, $doFormat=true)
    {
        $brief = [];
        $out = [];
        $param = [];
        $return = '';
        $data = self::getBetween($body, '/**', '*/');
        if (!empty($data) && isset($data[0])) {
            $in = self::brief2array($data[0]);

            //----------------------------------------------------------------------------------------------
            // To Text
            //----------------------------------------------------------------------------------------------
            if(self::brief2text($key='deprecated',$in,$brief))
            {
                if(empty($brief[$key])) {
                    $brief[$key]='deprecated';
                }
                $out['warnings'][] = 'deprecated';
                if($doFormat)$brief[$key]='<h4>Deprecated</h4><div class="alert alert-warning">' . str_replace("\n","</br>\n",$brief[$key]) . '</div>';
            }

            if(self::brief2list($key='todo',$in,$brief)) {
                if($doFormat && !empty($brief[$key])) $brief[$key]='<h4>Todo</h4><div class="alert alert-success"><ul><li>' . implode('</li><li>',$brief[$key]) . '</li></ul></div>';
            }

            if(self::brief2text($key='brief',$in,$brief))
            {
                if($doFormat && !empty($brief[$key])) $brief[$key]='<h4>Brief</h4><div>' . str_replace("\n","</br>\n",$brief[$key]) . '</div>';
            }

            if(self::brief2list($key='note',$in,$brief)) {
                if($doFormat && !empty($brief[$key])) $brief[$key]='<h4>Note</h4><div class="alert alert-info"><ul><li>' . implode('</li><li>',$brief[$key]) . '</li></ul></div>';
            }

            $select=[];
            //cache select and set auto export!
            if (isset($in['select'])) {
                $select = $in['select'];
                if (!isset($in['export'])) {
                    $in['export'][] = '';
                }
            }

            if(self::brief2text($key='export',$in,$brief))
            {
                if(empty($brief[$key])) {
                    $brief[$key]='export';
                }

                $out[$key]=$brief[$key];

                if($doFormat)$brief[$key]='<h4>Export</h4><div>' . str_replace("\n","</br>\n",$brief[$key]) . '</div>';
            }



            if(self::brief2list($key='select',$in,$brief)) {
                if($doFormat && !empty($brief[$key])) $brief[$key]='<h4>Select</h4><div class="alert alert-info"><ul><li>' . implode('</li><li>',$brief[$key]) . '</li></ul></div>';

                $pret = [];
                foreach ($select as $bdata) {
                    $line = $bdata[0];
                    $name = trim(substr($line, 0, strpos($line, ' ')));
                    if (empty($name)) {
                        $name = trim($line);
                    }
                    $pret[] = $name;
                }
                $out['select'] = $pret;
            }

            if (isset($in['param']) && !empty($in['param'])) {
                //used later
                $param = $in['param'];
                unset($in['param']);
            }

            if(self::brief2text($key='return',$in,$out))
            {
                if($doFormat) {
                    $return = str_replace("\n","</br>\n",$out[$key]);
                }
                else {
                    $return = $out[$key];
                }
                unset($out[$key]);
            }

            if (isset($in['warnings']) && !empty($in['warnings'])) {
                foreach ($in['warnings'] as $bdata) {
                    if (empty($bdata)) {
                        continue;
                    }
                    $out['warnings'][] = implode(' ', $bdata);
                }
                unset($in['warnings']);
            }

            if(!empty($in)) {
                echo "forgot to process:\n";
                var_dump($in);
                var_dump($out);
                die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
            }
        }


        if (!empty($orgparams) && $doFormat) {
            $pret = '<h4>Params</h4>
				<table class="table table-sm">
 <thead class="thead-default">
  <tr><th>InOut</th><th>Name</th><th>Type</th><th>Description</th><th>Warning</th></tr>
 </thead>
 <tbody class="tbody">
				';
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
                $pret .= '<tr>
				<td><span class="label label-' . (stripos($pdata['PARAMETER_MODE'], 'in') !== false ? 'primary' : 'default') . '">&nbsp;IN&nbsp;</span><span class="label label-' . (stripos($pdata['PARAMETER_MODE'], 'out') !== false ? 'success' : 'default') . '">&nbsp;OUT&nbsp;</span></td>
				<td>' . $name . '</td><td>' . strtoupper($pdata['DTD_IDENTIFIER']) . '</td><td>' . $pdesc . '</td><td>&nbsp;</td></tr>';
            }
            if (!empty($retparam)) {
                $pret .= '<tr>
				<td><span class="label label-danger">&nbsp;RETURN&nbsp;</span></td>
				<td>&nbsp;</td><td>' . strtoupper($retparam['DTD_IDENTIFIER']) . '</td><td>' . ((!empty($return)) ? $return : '') . '</td><td>&nbsp;</td></tr>';
            }
            $pret .= '
     </tbody>
   </table>';
            $brief['param'] = $pret;
        }

        $out['info'] = (!empty($brief) && $doFormat)?implode("<br>\n",$brief):'';

        return $out;
	}

	public static function parseSelect($body)
	{
		die('NOT FINISHED '.__FUNCTION__ . '::' . __LINE__);

		$ret = [];
		$body = self::removeComments($body);
		$body = trim(str_replace("\t", " ", $body));
		$body = trim(str_replace("\r", "", $body));

		die($body);

		$pos = 0;

		while ($endpos = strpos($body, ';', $pos+1))
		{
			if($endpos<0) {
				echo 'Parse failed';
				var_dump($pos);
				var_dump($endpos);
				var_dump(substr($body, $pos, 40));
				var_dump($body);
				die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}

			$statement = trim(substr($body, $pos, $endpos));

			if(strtoupper(substr($statement,0,6)) == 'SELECT') {
				var_dump($statement);
				die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
			$pos = $endpos;

			/*
			$pos += strlen($find);
			$nextchar = substr($body, $pos, 1);
			$pos += 1;
			if ($nextchar == ' ' || $nextchar == '(' || $nextchar == '`' || $nextchar == '\'' || $nextchar == '"' || $nextchar == "\n" || $nextchar == "\r" || $nextchar == "\t")
			{
				$ret[$type][] = $find;
				break;
			}*/
		}

		return $ret;
	}

    public static function checkHandler($dec)
    {
        $s = array_values(array_filter(explode(' ', $dec), 'strlen'));
        if(!isset($s[3]) || strtoupper($s[1])!="HANDLER") return [];

        $ret['type'] = strtoupper($s[0]);
        $ret['condition']=[];

        unset($s[0]); // Type
        unset($s[1]); // HANDLER
        unset($s[2]); // FOR

        foreach($s as $p) {
            $p = trim($p);
            if(strtoupper($p)=='BEGIN') break;

            $ret['condition'][]=$p;
        }
        if(!empty($ret['condition']))
        {
            $ret['condition'] = implode(' ',$ret['condition']);
        }

        return $ret;
    }

	public static function splitDeclare($dec)
	{
		$s = array_filter(explode(' ', $dec), 'strlen');
		//var_dump($s);

		$ret=['name'=>'',
			  'type'=>[],
			  'value'=>[],];

		$next = 'name';
		foreach($s as $p) {
			switch ($next)
			{
				case 'name':
					$ret['name'] = trim($p);
					$next = 'type';
					break;
				case 'type':
					if(strtoupper($p) == 'DEFAULT') {
						$next = 'value';
					} else {
						$ret['type'][] = trim($p);
					}
					break;
				case 'value':
					$ret['value'][] = trim($p);
					break;
				default:
					var_dump($dec);
					die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);
			}
		}
		$ret['type'] = trim(strtoupper(implode(' ',$ret['type'])));
		if(empty($ret['value'])) {
			$ret['value'] = 'NULL';
		} else {
			$ret['value'] = trim(implode(' ',$ret['value']));
		}
		return $ret;
	}

	public static function parseDeclares($body)
	{
		$ret = ['member'  => [],
				'error'   => [],
				'const'   => [],
                'unknown' => [],
                'handler' => [],];
		$body = self::removeComments($body);
		$body = trim(str_replace("\t", " ", $body));

		if (empty($body))
		{
			return $ret;
		}

		$decl['error'] = [];
		$decl['const'] = [];

		$tdec = self::getBetween($body, 'DECLARE', ';');

		$bErrorWarning = false;
		foreach ($tdec as $dec)
		{
			$p = [];
			$dec = str_replace('`','',$dec);
			$dec = trim($dec);
            if (strtoupper(substr($dec, 0, 8) == 'CONTINUE'))
            {
                //ignore
            }
			else if (substr($dec, 0, 2) == 'm_')
			{
				$p = self::splitDeclare($dec);
				$ret['member'][] = $p;
			}
			elseif (substr($dec, 0, 7) == 'eError_')
			{
				$p = DbCheckValues::checkError(self::splitDeclare($dec));
				$ret['error'][] = $p;
				if(isset($p['warning']) && !empty($p['warning'])) {
					$bErrorWarning=true;
				}
				$decl['error'][]=$p['name'];
			}
			elseif (substr($dec, 0, 7) == 'cConst_')
			{
				$p = DbCheckValues::checkConst(self::splitDeclare($dec));
				$ret['const'][] = $p;
				if(isset($p['warning']) && !empty($p['warning'])) {
					$bErrorWarning=true;
					$ret['warnings'][] = 'DECLARE Const Warning: "'.$p['warning'].'"';
				}
				$decl['const'][]=$p['name'];
			}
			else
			{
			    $h = self::checkHandler($dec);
			    if(!empty($h)) {
                    $ret['handler'][] = $h;
                } else {
                    $p = self::splitDeclare($dec);
                    $ret['unknown'][] = $p;
                    $ret['warnings'][] = 'Unknown DECLARE !: "'.$dec.'"';
                    $bErrorWarning=true;
                }
			}
		}
		if ($bErrorWarning)
		{
            $ret['warnings'][] = 'one or more declares have warnings';
		}

		//find used errors and const
		foreach (DbValues::Keys['error'] as $find)
		{
			if(in_array($find,$decl['error'])) continue;
			$pos=0;
			while ($pos = strpos($body, $find, $pos))
			{
				$pos += strlen($find);
				$nextchar = substr($body, $pos, 1);
				$pos += 1;
				if ($nextchar == ' ' || $nextchar == '(' || $nextchar == '`' || $nextchar == '\'' || $nextchar == '"' || $nextchar == "\n" || $nextchar == "\r" || $nextchar == "\t" || $nextchar == "," || $nextchar == ";" || $nextchar == "=")
				{
					$ret['warnings'][] = 'error used but not declared: '.$find;
					break;
				} else {
					/*var_dump($find);
					var_dump($pos);
					var_dump($nextchar);
					var_dump(substr($body,$pos-strlen($find)));
					die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);*/
				}
			}
		}
		foreach (DbValues::Keys['const'] as $find)
		{
			if(in_array($find,$decl['const'])) continue;
			$pos=0;
			while ($pos = strpos($body, $find, $pos))
			{
				$pos += strlen($find);
				$nextchar = substr($body, $pos, 1);
				$pos += 1;
				if ($nextchar == ' ' || $nextchar == '(' || $nextchar == '`' || $nextchar == '\'' || $nextchar == '"' || $nextchar == "\n" || $nextchar == "\r" || $nextchar == "\t" || $nextchar == "," || $nextchar == ";" || $nextchar == "=")
				{
					$ret['warnings'][] = 'const used but not declared: '.$find;
					break;
				} else {
					/*var_dump($find);
					var_dump($pos);
					var_dump($nextchar);
					var_dump(substr($body,$pos-strlen($find)));
					die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);*/
				}
			}
		}

		return $ret;
	}

	public static function findUsesFor($body, $name, $search4uses)
	{
		$ret = [];
		$body = self::removeComments($body);
		$body = trim(str_replace("\t", " ", $body));
		if (empty($body))
		{
			return $ret;
		}
		foreach ($search4uses as $find => $type)
		{
			if ($find == $name)
			{
				continue;
			}
			$pos = 0;
			while ($pos = strpos($body, $find, $pos))
			{
				$pos += strlen($find);
				$nextchar = substr($body, $pos, 1);
				$pos += 1;
				if ($nextchar == ' ' || $nextchar == '(' || $nextchar == '`' || $nextchar == '\'' || $nextchar == '"' || $nextchar == "\n" || $nextchar == "\r" || $nextchar == "\t" || $nextchar == ";")
				{
					$ret[$type][] = $find;
					break;
				}
			}
		}

		return $ret;
	}

	public static function getBetween($content, $start, $end)
	{
		if (empty($content))
		{
			return [];
		}
		$r = explode($start, $content);
		if (isset($r[1]))
		{
			array_shift($r);
			$help_fun = function ($arr) use ($end)
			{
				$r = explode($end, $arr);

				return $r[0];
			};
			$r = array_map($help_fun, $r);

			return $r;
		}
		else
		{
			return [];
		}
	}

    public static function mergeData($data)
    {
        foreach ($data as $type => $list)
        {
            foreach ($list as $name => $value)
            {
                self::mergeUses($type, $name, $data);
            }
        }

        return $data;
    }

    public static function setUsedBy($data)
    {
        foreach ($data as $type => $list)
        {
            foreach ($list as $name => $value)
            {
                if(empty($value['uses'])) continue;
                foreach ($value['uses'] as $ctype => $cval) {
                    foreach ($cval as $cname) {
                        $data[$ctype][$cname]['usedBy'][$type][] = $name;
                    }
                }
            }
        }

        return $data;
    }

	public static function mergeUses($type, $name, &$infos)
	{
		if (empty($name))
		{
			return NULL;
		}
		if (isset($infos[$type]) && isset($infos[$type][$name]) && isset($infos[$type][$name]['merged']))
		{
			return true;
		}
		if (!isset($infos[$type][$name]))
		{
			return NULL;
		}
		$infos[$type][$name]['merged']['errors'] = [];
		$infos[$type][$name]['merged']['uses'] = [];
		$infos[$type][$name]['merged']['select'] = [];

		if (isset($infos[$type][$name]['parse']))
		{
			if (isset($infos[$type][$name]['parse']['select']) && !empty($infos[$type][$name]['parse']['select']))
			{
				$infos[$type][$name]['merged']['select'] = $infos[$type][$name]['parse']['select'];
			}
			if (isset($infos[$type][$name]['parse']['declares']['error']) && !empty($infos[$type][$name]['parse']['declares']['error']))
			{
				$infos[$type][$name]['merged']['errors'] = $infos[$type][$name]['parse']['declares']['error'];
				foreach ($infos[$type][$name]['merged']['errors'] as $id => $cval)
				{
					$infos[$type][$name]['merged']['errors'][$id]['uses'][$type][] = $name;
				}
			}
		}
		//$bDump = ($name=='GlobBook');
		//if($bDump) var_dump($infos[$type][$name]['merged']);
		if (isset($infos[$type][$name]['uses']) && !empty($infos[$type][$name]['uses']))
		{
			$infos[$type][$name]['merged']['uses'] = $infos[$type][$name]['uses'];
			foreach ($infos[$type][$name]['uses'] as $ctype => $cval)
			{
				foreach ($cval as $cname)
				{
					$tret = self::mergeUses($ctype, $cname, $infos);
					if (empty($tret))
					{
						continue;
					}

					if (!empty($infos[$ctype][$cname]['merged']['select']))
					{
						if(empty($infos[$type][$name]['merged']['select'])) {
							$infos[$type][$name]['merged']['select'] = $infos[$ctype][$cname]['merged']['select'];
						} else {
							foreach ($infos[$ctype][$cname]['merged']['select'] as $mname) {
								if (in_array($mname, $infos[$type][$name]['merged']['select']))
								{
									continue;
								}
								$infos[$type][$name]['merged']['select'][] = $mname;
							}
						}
					}
					if (!empty($infos[$ctype][$cname]['merged']['errors']))
					{
						foreach ($infos[$ctype][$cname]['merged']['errors'] as $mval)
						{
							$found = false;
							foreach ($infos[$type][$name]['merged']['errors'] as $oid => $oval)
							{
								if ($mval['name'] == $oval['name'] && $mval['value'] == $oval['value'])
								{
									foreach ($mval['uses'] as $mtype => $mcval)
									{
										if (!isset($infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype]))
										{
											$infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype] = $mcval;
										}
										else
										{
											foreach ($mcval as $mname)
											{
												if (in_array($mname, $infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype]))
												{
													continue;
												}
												$infos[$type][$name]['merged']['errors'][$oid]['uses'][$mtype][] = $mname;
											}
										}
									}
									$found = true;
									break;
								}
							}
							if (!$found)
							{
								$infos[$type][$name]['merged']['errors'][] = $mval;
							}
						}
					}
					if (!empty($infos[$ctype][$cname]['merged']['uses']))
					{
						foreach ($infos[$ctype][$cname]['merged']['uses'] as $mtype => $mval)
						{
							if (!isset($infos[$type][$name]['merged']['uses'][$mtype]))
							{
								$infos[$type][$name]['merged']['uses'][$mtype] = $mval;
							}
							else
							{
								foreach ($mval as $mname)
								{
									if (in_array($mname, $infos[$type][$name]['merged']['uses'][$mtype]))
									{
										continue;
									}
									$infos[$type][$name]['merged']['uses'][$mtype][] = $mname;
								}
							}
						}
					}
				}
			}
		}

		//if($bDump) {var_dump($infos[$type][$name]['merged']); die(__FILE__ . '::' . __FUNCTION__ . '::' . __LINE__);}
		return true;
	}

	public static function  removeComments($source) {
		$RXSQLComments = '@(--[^\r\n]*)|(\#[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/)@ms';
		return ((empty($source)) ?  '' : preg_replace( $RXSQLComments, '', $source ));
	}

    protected function executeSql($data)
    {
        if (empty($data)) {
            return;
        }
        $data = str_replace("\r", '', $data);
        $sqls = array_filter(explode(DbToolsModule::getInstance()->exportDelimiter, $data), 'strlen');

        $transaction = $this->db->beginTransaction();
        try {
            foreach ($sqls as $sql) {
                $sql = trim($sql);
                if (strtoupper(substr($sql, 0, 9)) == 'DELIMITER' || strtoupper(substr($sql, 0, 4)) == 'USE ') {
                    continue;
                }
                $this->db->createCommand($sql)->execute();
            }
            $transaction->commit();
        }
        catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
