<?php

namespace Moloni;

class Curl
{

    private static $apiUrl = "https://api.moloni.pt/v1/";

    public static function simple($action, $values = false, $print = false)
    {
        $con = curl_init();
        $url = self::$apiUrl . $action . "/?access_token=" . ACCESS;

        if ($values) {
            $send = http_build_query($values);
        } else {
            $send = false;
        }

        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $send);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);

        $curl = curl_exec($con);
        curl_close($con);

        $result = json_decode($curl, true);

        if ($print) {
            echo $url;
            echo "<pre>";
            print_r($result);
            echo "</pre>";
            exit;
        }

        return $result;

    }

    public static function login($user, $pass)
    {
        $pass = urlencode($pass);

        $con = curl_init();
        $url = self::$apiUrl . "grant/?grant_type=password&client_id=devapi&client_secret=53937d4a8c5889e58fe7f105369d9519a713bf43&username=$user&password=$pass";
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, FALSE);
        curl_setopt($con, CURLOPT_POSTFIELDS, FALSE);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);


        $curl = curl_exec($con);
        curl_close($con);

        $result = json_decode($curl, true);
        if (!isset($result['error'])) {
            return $result;
        } else {
            return false;
        }
    }

    public static function refresh($refresh)
    {
        $con = curl_init();
        $url = self::$apiUrl . "grant/?grant_type=refresh_token&client_id=devapi&client_secret=53937d4a8c5889e58fe7f105369d9519a713bf43&refresh_token=$refresh";
        curl_setopt($con, CURLOPT_URL, $url);
        curl_setopt($con, CURLOPT_POST, FALSE);
        curl_setopt($con, CURLOPT_POSTFIELDS, FALSE);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);

        $res_curl = curl_exec($con);
        curl_close($con);

        $res_txt = json_decode($res_curl, true);
        if (!isset($res_txt['error'])) {
            return $res_txt;
        } else {
            return false;
        }
    }
}
