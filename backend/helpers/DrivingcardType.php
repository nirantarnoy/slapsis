<?php

namespace backend\helpers;

class DrivingcardType
{
    private static $data = [
        '1' => 'ท.2',
        '2' => 'ท.3',
        '3' => 'ท.4',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'ท.2'],
        ['id'=>'2','name' => 'ท.3'],
        ['id'=>'3','name' => 'ท.4'],
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
