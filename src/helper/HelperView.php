<?php
namespace DbTools\helper;

use Yii;

class HelperView
{
	public static function getFancyCheckbox($id,$label,$style,$checked=false,$onclick='')
	{
	    return '<label for="'.$id.'" class="btn btn-'.$style.'">'.$label.'<input type="checkbox" id="'.$id.'" class="badgebox"'.(empty($onclick)?'':' onclick="'.$onclick.'"').(empty($checked)?'':' checked="checked"').' /><span class="badge">&check;</span></label>';
	}
}
