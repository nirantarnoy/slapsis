<?php

namespace backend\helpers;

class ViewstatusType
{
    const STATUS_OPEN= 1;
    const STATUS_CLOSE = 2;
    const STATUS_CANCEL = 3;

    private static $data = [
        '0' => 'ทั้งหมด',
        '1' => 'ใช้งาน',
        '2' => 'ไม่ใช้งาน'
    ];

    /**
     * @var \string[][]
     */
    private static $dataobj = array(
        array('id'=>'0','name' => 'ทั้งหมด'),
        array('id'=>'1','name' => 'ใช้งาน'),
        array('id'=>'2','name' => 'ไม่ใช้งาน')
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
