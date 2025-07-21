<?php

namespace backend\helpers;

class ActivityType
{
    private static $data = [
        '1' => 'ปรับยอด',
        '2' => 'เบิกสินค้า',
        '3' => 'ขาย',
        '4' => 'รับเข้าสินค้า',
        '5' => 'รับเข้าสินค้า PO',
    ];

    private static $dataobj = [
        ['id' => '1', 'name' => 'ปรับยอด'],
        ['id' => '2', 'name' => 'เบิกสินค้า'],
        ['id' => '3', 'name' => 'ขาย'],
        ['id' => '4', 'name' => 'รับเข้าสินค้า'],
        ['id' => '5', 'name' => 'รับเข้าสินค้า PO'],
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
