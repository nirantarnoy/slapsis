<?php

namespace backend\helpers;

class IssueviewType
{
    private static $data = [
        '1' => 'จ่ายครบ',
        '2' => 'จ่ายไม่ครบ'
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'จ่ายครบ'],
        ['id'=>'2','name' => 'จ่ายไม่ครบ']
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
