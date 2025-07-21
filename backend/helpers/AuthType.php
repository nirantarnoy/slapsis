<?php

namespace backend\helpers;

class AuthType
{
    const ROLE = 1;
    const RULE = 2;
    private static $data = [
        1 => 'Role',
        2 => 'Permission'
    ];

    private static $dataobj = [
        ['id'=>1,'name' => 'Role'],
        ['id'=>2,'name' => 'Permission'],
    ];
    public static function asArray()
    {
        return self::$data;
    }
    public static function asArrayObject()
    {
        return self::$dataobj;
    }
    public static function getTypeById($idx)
    {
        if (isset(self::$data[$idx])) {
            return self::$data[$idx];
        }

        return 'Unknown Type';
    }
    public static function getTypeByName($idx)
    {
        if (isset(self::$data[$idx])) {
            return self::$data[$idx];
        }

        return 'Unknown Type';
    }
}
