<?php

namespace backend\helpers;

class TransStatusType
{
    private static $data = [
        '1' => 'Open',
        '2' => 'Waiting',
        '3' => 'Complete',
        '4' => 'Cancel',
    ];

    private static $dataobj = [
        ['id'=>'1','name' => 'Open'],
        ['id'=>'2','name' => 'Waiting'],
        ['id'=>'3','name' => 'Complete'],
        ['id'=>'4','name' => 'Cancel'],
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
