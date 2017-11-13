<?php
namespace DbTools\db\values;

use dbtools\dbvalues\DbValues;

use Yii;

class DbCheckValues extends DbValues {

	public static function getError($error)
	{
		if (strpos($error, '::') == false)
		{
			$error = 'static::' . $error;
		}
		if (defined($error))
		{
			return constant($error);
		}
		else
		{
			return false;
		}
	}
	public static function getConst($const)
	{
		if (strpos($const, '::') == false)
		{
			$const = 'static::' . $const;
		}
		if (defined($const))
		{
			return constant($const);
		}
		else
		{
			return false;
		}
	}
	public static function checkError($p)
	{
		$name=$p['name'];
		$type=$p['type'];
		$v=$p['value'];
		$p['message'] = '';
		$wmsg=[];

		if ($type != DbGenValues::cErrorDbType)
		{
			$wmsg[] = 'type needs to be '.DbGenValues::cErrorDbType.'!';
		}

		if (version_compare(PHP_VERSION, '7.0.0') < 0)
		{
			$wmsg[] = 'need php > 7.0.0';
		}
		else
		{
			$ec = self::getError($name);
			if ($ec === false)
			{
				$wmsg[] = 'not defined: ' . $name;
			}
			elseif ($ec != $v)
			{
				$wmsg[] = 'error code missmatch!: ' . $name . ' - is ' . $v . ' but should be ' . $ec;
			}
			else
			{
				$p['message'] = static::MessageByCode($v);
			}
		}

		if ($v >= DbGenValues::iMaxErrorValue)
		{
			$wmsg[] = 'errorcode(' . $v . ') is exceeds limit of ' . DbGenValues::iMaxErrorValue . '"!';
		}

		$p['warning'] = trim(implode('<br/>', $wmsg));

		return $p;
	}
	public static function checkConst($p)
	{
		$name=$p['name'];
		$type=$p['type'];
		$v=$p['value'];
		$wmsg=[];

		if (version_compare(PHP_VERSION, '7.0.0') < 0)
		{
			$wmsg[] = 'need php > 7.0.0';
		}
		else
		{
			$ec = self::getConst($name);
			if ($ec === false)
			{
				$wmsg[] = 'not defined: ' . $name;
			}
			elseif ($ec != $v && "'".$ec."'" != $v)
			{
				$wmsg[] = 'error code missmatch!: ' . $name . ' - is ' . $v . ' but should be ' . $ec;
			}
			elseif(static::DbTypes[$name] != $type)
			{
				$wmsg[] = 'error type missmatch!: ' . $name . ' - is "' . $type . '"" but should be "' . static::DbTypes[$name] . '"';
			}
		}

		$p['warning'] = trim(implode('<br/>', $wmsg));

		return $p;
	}
}