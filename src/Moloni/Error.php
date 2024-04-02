<?php

namespace Moloni;

class Error
{
    public static $exists = false;
    public static $error = false;
    public static $success = false;
    public static $warning = false;

    public static function create($where, $message, $data = [])
    {
        self::$exists = true;
        self::$error = [];
        self::$error['message'] = $message;
        self::$error['data'] = $data;
        self::$error['where'] = $where;
    }

    public static function success($message, $moloniURL = false, $downloadURL = false)
    {
        self::$exists = true;
        self::$success = [];
        self::$success['message'] = $message;
        self::$success['moloniURL'] = $moloniURL;
        self::$success['downloadURL'] = $downloadURL;
    }

    public static function warning($message, $data = [])
    {
        self::$exists = true;
        self::$warning = [];
        self::$warning['message'] = $message;
        self::$warning['data'] = $data;
    }
}
