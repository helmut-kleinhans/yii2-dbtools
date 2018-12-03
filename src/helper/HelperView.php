<?php

namespace DbTools\helper;

use Yii;

class HelperView
{
    public static function getFancyCheckbox(string $id, string $label, string $style, bool $checked = false, string $onclick = ''): string
    {
        return '<label for="' . $id . '" class="btn btn-' . $style . ' btn-sm">' . $label . '
                    <input type="checkbox" id="' . $id . '" class="badgebox"' . (empty($onclick) ? '' : ' onclick="' . $onclick . '"') . (empty($checked) ? '' : ' checked="checked"') . ' style="display:none;" />
                    <span class="badge" style="left:2px;">&check;</span>
                </label>';
    }

    public static function getSwitchCheckbox(string $id, string $label, string $style): string
    {
        return '<label for="' . $id . '" class="btn btn-' . $style . ' btn-sm">' . $label . '
                    <input type="checkbox" id="' . $id . '" data-jtmulti-state/>
                </label>';
    }

    public static function getCbSettingsLoadBool(string $id, bool $default = true): string
    {
        return '$("#' . $id . '").prop("checked", settingsLoadBool("' . $id . '", ' . $default . '));';
    }

    public static function getCbSettingsSaveBool(string $id): string
    {
        return 'settingsSave("' . $id . '", $("#' . $id . '").prop("checked"));';
    }

    public static function getValSettingsLoad(string $id, string $default = ''): string
    {
        return '$("#' . $id . '").val(settingsLoad("' . $id . '", ' . $default . '));';
    }

    public static function getValSettingsSave(string $id): string
    {
        return 'settingsSave("' . $id . '", $("#' . $id . '").val());';
    }

    public static function getSwitchInit(string $id): string
    {
        return 'var sval = settingsLoad("' . $id . '", -1); 
        if(sval==1) $("#' . $id . '").triSwitch({type: "normal", defaultValue: 1}); 
        else if(sval==0) $("#' . $id . '").triSwitch({type: "normal", defaultValue: 0}); 
        else $("#' . $id . '").triSwitch({type: "normal", defaultValue: -1});';
    }

    public static function getSwitchSettingsSave(string $id): string
    {
        return self::getValSettingsSave($id);
    }
}
