<?php

namespace Moloni;

use WHMCS\Config\Setting;

class Tools
{
    public static function getPublicUrl($file = false)
    {
        $url = Setting::getValue('SystemURL');
        if (self::isSecure()) {
            $url = preg_replace('/\bhttp\b/', 'https', $url);
        }
        $url .= '/modules/addons/moloni/public/';

        if ($file) {
            $url .= $file;
        }

        if (defined('MOLONI_ADDON_VERSION')) {
            $url .= '?v=' . MOLONI_ADDON_VERSION;
        }

        return $url;
    }

    public static function genURL($action = '', $command = '')
    {
        $url = (self::isSecure()) ? 'https://' : 'http://';
        $url .= $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?module=moloni';
        $url .= (($action !== '') ? "&action=" . $action : "");
        $url .= (($command !== '') ? "&command=" . $command : "");

        return $url;
    }

    private static function isSecure()
    {
        return
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || $_SERVER['SERVER_PORT'] == 443;
    }

    public static function isSelected($option, $value, $returnSelected = true)
    {
        if (defined($option) && constant($option) == $value) {
            if ($returnSelected) {
                return " selected ";
            }

            return true;
        }

        return false;
    }
}
