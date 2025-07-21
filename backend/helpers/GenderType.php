<?php

namespace backend\helpers;

class GenderType
{
    private static $data = [
        '1' => 'ชาย',
        '2' => 'หญิง'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'ชาย'],
        ['id'=>'2','name' => 'หญิง']
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
