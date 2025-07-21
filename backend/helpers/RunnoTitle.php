<?php

namespace backend\helpers;

class RunnoTitle
{
    const RUNNO_PR = 1;
    const RUNNO_PO = 2;
    const RUNNO_QT = 3;
    const RUNNO_SO = 4;
    const RUNNO_TRANSFER = 5;
    const RUNNO_ISSUE = 6;
    const RUNNO_ISSUE_RETURN = 7;
    const RUNNO_SO_RETURN = 8;
    const RUNNO_PO_RETURN = 9;
    const RUNNO_COUNT = 10;
    const RUNNO_ADJUST = 11;
    const RUNNO_CUSTOMER = 12;
    const RUNNO_WORKORDER = 13;
    const RUNNO_PRODREC = 14;
    const RUNN0_PDR = 15;
    const RUNNO_INV = 16;

    private static $data = [
        1 => 'ขอซื้อ',
        2 => 'สั่งซื้อ',
        3 => 'เสนอราคา',
        4 => 'ขาย',
        5 => 'ขาย pos',
        6 => 'เบิกขึ้นรถ',
        7 => 'คืนขายหน่วยรถ',
        8 => 'คืนขาย',
        9 => 'คืนซื้อ',
        10 => 'นับสต๊อก',
        11 => 'ปรับสต๊อก',
        12 => 'ลูกค้า',
        13 => 'ใบสั่งผลิต',
        14 => 'ใบรับวัตถุดิบ',
        15 => 'รับเข้าผลิต',
        16 => 'ใบจ่ายเงิน',
        17 => 'ตรวจสอบคุณภาพ',
        18 => 'เบิกเติม',
        19 => 'โอนระหว่างสาขา',
        20 => 'เบิกแปรสภาพ',
        21 => 'รับเข้าแปรสภาพ',
        22 => 'โอนระหว่างรถ',
        23 => 'เสีย',
        24 => 'ขายรถ',
        25 => 'วางบิล',
        26 => 'รับ reprocess รถ',
        27 => 'รับ reprocess',
        28 => 'ยกเลิกรับเข้า',
        29 => 'จองแปรสภาพ',
    ];

    private static $dataobj = [
        ['id' => 1, 'name' => 'ขอซื้อ', 'prefix' => 'PR'],
        ['id' => 2, 'name' => 'สั่งซื้อ', 'prefix' => 'PO'],
        ['id' => 3, 'name' => 'เสนอราคา', 'prefix' => 'QUO'],
        ['id' => 4, 'name' => 'ขายรถ', 'prefix' => 'SO'],
        ['id' => 5, 'name' => 'ขาย pos', 'prefix' => 'TF'],
        ['id' => 6, 'name' => 'เบิกขึ้นรถ', 'prefix' => 'IS'],
        ['id' => 7, 'name' => 'คืนขายหน่วยรถ', 'prefix' => 'RT'],
        ['id' => 8, 'name' => 'คืนขาย', 'prefix' => 'SRT'],
        ['id' => 9, 'name' => 'คืนซื้อ', 'prefix' => 'PRT'],
        ['id' => 10, 'name' => 'นับสต๊อก', 'prefix' => 'CT'],
        ['id' => 11, 'name' => 'ปรับสต๊อก', 'prefix' => 'AJ'],
        ['id' => 12, 'name' => 'ลูกค้า', 'prefix' => 'CU'],
        ['id' => 13, 'name' => 'ใบสั่งผลิต', 'prefix' => 'WO'],
        ['id' => 14, 'name' => 'ใบรับวัตถุดิบ', 'prefix' => 'PDR'],
        ['id' => 15, 'name' => 'รับเข้าผลิต', 'prefix' => 'REP'],
        ['id' => 16, 'name' => 'ใบจ่ายเงิน', 'prefix' => 'INV'],
        ['id' => 17, 'name' => 'ตรวจสอบคุณภาพ', 'prefix' => 'QC'],
        ['id' => 18, 'name' => 'เบิกเติม', 'prefix' => 'IF'],
        ['id' => 19, 'name' => 'โอนระหว่างสาขา', 'prefix' => 'TB'],
        ['id' => 20, 'name' => 'เบิกแปรสภาพ', 'prefix' => 'IST'],
        ['id' => 21, 'name' => 'รับเข้าแปรสภาพ', 'prefix' => 'RT'],
        ['id' => 22, 'name' => 'โอนระหว่างรถ', 'prefix' => 'TC'],
        ['id' => 23, 'name' => 'เสีย', 'prefix' => 'SC'],
        ['id' => 24, 'name' => 'ขายรถ', 'prefix' => 'CO'],
        ['id' => 25, 'name' => 'วางบิล', 'prefix' => 'INV'],
        ['id' => 26, 'name' => 'รับ reprocess รถ', 'prefix' => 'PED'],
        ['id' => 27, 'name' => 'รับ reprocess', 'prefix' => 'PER'],
        ['id' => 28, 'name' => 'ยกเลิกรับเข้า', 'prefix' => 'RTP'],
        ['id' => 29, 'name' => 'จองแปรสภาพ', 'prefix' => 'RIS'],
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
