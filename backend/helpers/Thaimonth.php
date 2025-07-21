<?php

namespace backend\helpers;

class Thaimonth
{
    private static $data = [
        '1' => 'มกราคม',
        '2' => 'กุมภาพันธ์',
        '3' => 'มีนาคม',
        '4' => 'เมษายน',
        '5' => 'พฤษภาคม',
        '6' => 'มิถุนายน',
        '7' => 'กรกฎาคม',
        '8' => 'สิงหาคม',
        '9' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม',
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
