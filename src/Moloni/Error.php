<?php

namespace Moloni;

class Error
{

    public static $exists = false;
    public static $error = false;
    public static $success = false;

    public static function create($where, $message, $values_sent = null, $values_receive = null)
    {
        self::$exists = true;
        self::$error = array();
        self::$error['where'] = $where;
        self::$error['message'] = $message;
        self::$error['values_sent'] = (empty($values_sent) ? "" : $values_sent);
        self::$error['values_receive'] = (empty($values_receive) ? "" : $values_receive);
    }

    public static function success($message, $moloniURL = false, $downloadURL = false)
    {
        self::$exists = true;
        self::$success['text'] = $message;
        self::$success['moloniURL'] = $moloniURL;
        self::$success['downloadURL'] = $downloadURL;
    }

}
