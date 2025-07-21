<?php

namespace backend\helpers;

class ProductStatus
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 2;

    private static $data = [
        '1' => 'ใช้งาน',
        '0' => 'ไม่ใช้งาน'
    ];

    /**
     * @var \string[][]
     */
    private static $dataobj = array(
        array('id'=>'1','name' => 'ใช้งาน'),
        array('id'=>'0','name' => 'ไม่ใช้งาน')
    );
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

        return 'Unknown';
    }
    public static function getTypeByName($idx)
    {
        if (isset(self::$data[$idx])) {
            return self::$data[$idx];
        }

        return 'Unknown';
    }
}
