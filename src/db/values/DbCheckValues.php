<?php
namespace DbTools\db\values;

use DbToolsExport\dbvalues\DbValues;

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


        $ec = self::getError($name);
        if ($ec === false)
        {
            $wmsg[] = 'not defined';
        }
        elseif ($ec != $v)
        {
            $wmsg[] = 'value missmatch: is "' . $v . '"" but should be "' . $ec.'"';
        }
        else
        {
            $p['message'] = static::MessageByCode($v);
        }

        if ($type != DbGenValues::cErrorDbType)
        {
            $wmsg[] = 'type missmatch: - is "' . $type . '" but should be "' . DbGenValues::cErrorDbType . '"';
        }

        if ($v >= DbGenValues::iMaxErrorValue)
        {
            $wmsg[] = 'errorcode(' . $v . ') is exceeds limit of ' . DbGenValues::iMaxErrorValue . '"!';
        }

        $p['warnings'] = $wmsg;

        return $p;
    }

	public static function checkConst($p)
	{
		$name=$p['name'];
		$type=$p['type'];
		$v=$p['value'];
		$wmsg=[];

        $ec = self::getConst($name);
        if ($ec === false)
        {
            $wmsg[] = 'not defined';
        }
        else {
            if ($ec != $v && "'".$ec."'" != $v)
            {
                $wmsg[] = 'value missmatch: is "' . $v . '" but should be "' . $ec.'"';
            }

            if(static::DbTypes[$name] != $type)
            {
                $wmsg[] = 'type missmatch: - is "' . $type . '" but should be "' . static::DbTypes[$name] . '"';
            }
        }

        $p['warnings'] = $wmsg;

		return $p;
	}
}