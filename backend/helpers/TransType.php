<?php

namespace backend\helpers;

class TransType
{
    private static $data = [
        '1' => 'ปรับยอดยกมา',
        '2' => 'ปรับยอด',
        '3' => 'ขาย',
        '4' => 'คืนขาย',
        '5' => 'ยืม',
        '6' => 'คืนยืม',
        '7' => 'เบิกส่งช่าง',
        '8' => 'คืนส่งช่าง',
        '9' => 'ขาย Dropship',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'ปรับยอดยกมา'],
        ['id'=>'2','name' => 'ปรับยอด'],
        ['id'=>'3','name' => 'ขาย'],
        ['id'=>'4','name' => 'คืนขาย'],
        ['id'=>'5','name' => 'ยืม'],
        ['id'=>'6','name' => 'คืนยืม'],
        ['id'=>'7','name' => 'เบิกส่งช่าง'],
        ['id'=>'8','name' => 'คืนส่งช่าง'],
        ['id'=>'9','name' => 'ขาย Dropship'],
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
