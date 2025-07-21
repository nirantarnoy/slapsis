<?php

namespace backend\helpers;

class CatType
{
    private static $data = [
        '1' => 'สินค้าใหม่',
        '2' => 'สินค้ามือสอง',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'สินค้าใหม่'],
        ['id'=>'2','name' => 'สินค้ามือสอง'],
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
