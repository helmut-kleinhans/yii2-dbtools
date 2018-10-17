<?php
namespace DbTools\helper;

use Yii;

class HelperView
{
	public static function getFancyCheckbox($id,$label,$style,$checked=false,$onclick='')
	{
	    return '<label for="'.$id.'" class="btn btn-'.$style.'" style="padding: 0.2em 0.5em; margin:0.2em;">'.$label.'<input type="checkbox" id="'.$id.'" class="badgebox"'.(empty($onclick)?'':' onclick="'.$onclick.'"').(empty($checked)?'':' checked="checked"').' style="display:none;" /><span class="badge" style="left:2px;">&check;</span></label>';
	}

    public static function getCbSettingsLoadBool($id, $default = true)
    {
        return '$("#' . $id . '").prop("checked", settingsLoadBool("' . $id . '", ' . $default . '));';
    }

    public static function getCbSettingsSaveBool($id)
    {
        return 'settingsSave("' . $id . '", $("#' . $id . '").prop("checked"));';
    }
}
