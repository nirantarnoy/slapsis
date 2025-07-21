<?php

namespace backend\helpers;

class SalaryType
{
    private static $data = [
        '1' => 'รายวัน',
        '2' => 'รายเดือน'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'รายวัน'],
        ['id'=>'2','name' => 'รายเดือน']
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
