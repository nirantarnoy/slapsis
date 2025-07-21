<?php

namespace backend\helpers;

class StockType
{
    private static $data = [
        '1' => 'IN',
        '2' => 'OUT',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'IN'],
        ['id'=>'2','name' => 'OUT'],
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
