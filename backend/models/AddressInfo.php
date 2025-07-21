<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "address_info".
 *
 * @property int $id
 * @property int|null $party_type
 * @property int|null $party_id
 * @property string|null $address
 * @property string|null $street
 * @property int|null $district_id
 * @property int|null $city_id
 * @property int|null $province_id
 * @property string|null $zipcode
 * @property int|null $status
 */
class AddressInfo extends \common\models\AddressInfo
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'address_info';
    }

    /**
     * {@inheritdoc}
     */
//    public function rules()
//    {
//        return [
//            [['address', 'district_id','city_id', 'province_id', 'zipcode'], 'required'],
//            [['party_type', 'party_id', 'district_id', 'city_id', 'province_id', 'status'], 'integer'],
//            [['address', 'street', 'zipcode'], 'string', 'max' => 255],
//        ];
//    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'party_type' => 'Party Type',
            'party_id' => 'Party ID',
            'address' => 'Address',
            'street' => 'Street',
            'district_id' => 'District ID',
            'city_id' => 'City ID',
            'province_id' => 'Province ID',
            'zipcode' => 'Zipcode',
            'status' => 'Status',
        ];
    }

    public static function findDistrict($id,$type)
    {
        $c_district = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if ($c_district){
            $model = District::find()->where(['DISTRICT_ID' => $c_district->district_id])->one();
            return $model != null ? $model->DISTRICT_NAME : '-';
        }else{
            return '-';
        }

    }

    public static function findDistrictId($id, $type)
    {
        $c_district = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if ($c_district){
            return $c_district != null ? $c_district->district_id : 0;
        }else{
            return 0;
        }

    }

    public static function findAmphur($id, $type)
    {
        $c_amphur = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if ($c_amphur){
            $model = Amphur::find()->where(['AMPHUR_ID' => $c_amphur->city_id])->one();
            return $model != null ? $model->AMPHUR_NAME : '-';
        } else {
            return '-';
        }

    }

    public static function findAmphurId($id,$type)
    {
        $c_district = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if ($c_district){
            return $c_district != null ? $c_district->city_id : 0;
        }else {
            return 0;
        }

    }

    public static function findProvince($id,$type)
    {
        $c_province = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if ($c_province){
            $model = Province::find()->where(['PROVINCE_ID' => $c_province->province_id])->one();
            return $model != null ? $model->PROVINCE_NAME : '-';
        }else {
         return '-';
        }
    }

    public static function findProvinceId($id,$type)
    {
        $c_province = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if ($c_province){
            return $c_province != null ? $c_province->province_id : 0;
        }else{
            return 0;
        }
    }

    public static function findZipcode($id,$type)
    {
        $model = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        return $model != null ? $model->zipcode : '-';
    }

    public static function findStreet($id,$type)
    {
        $model = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        return $model != null ? $model->street : '-';
    }

    public static function findAddress($id,$type)
    {
        $model = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        return $model != null ? $model->address : '-';
    }

    public static function findCustomerAddress($id,$type)
    {
        $model = null;
        $city_name = null;
        $province_name = null;
        $zipcode = null;
        $c_address = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if($c_address!=null){
            $model = District::find()->where(['DISTRICT_ID' => $c_address->district_id])->one();
            $model_2 = Amphur::find()->where(['AMPHUR_ID' => $c_address->city_id])->one();
            $model_3 = Province::find()->where(['PROVINCE_ID' => $c_address->province_id])->one();
            $zipcode = $c_address->zipcode;
        }

        if($model_2!=null){
            $city_name = $model_2->AMPHUR_NAME;
        }
        if($model_3!=null){
            $province_name = $model_3->AMPHUR_NAME;
        }

        return $model != null ? $model->DISTRICT_NAME .' '.$city_name.' '.$province_name.' '.$zipcode : '';
    }

    public static function findProvinceShowname($id)
    {
            $model = Province::find()->where(['PROVINCE_ID' => $id])->one();
            return $model != null ? $model->PROVINCE_NAME : '-';

    }
    public static function findAmphurShowname($id)
    {

            $model = Amphur::find()->where(['AMPHUR_ID' => $id])->one();
            return $model != null ? $model->AMPHUR_NAME : '-';

    }

    public static function findVendorAddress($id,$type)
    {
        $model = null;
        $zipcode = '';
        $city_name = "";
        $province_name = "";
        $c_address = \common\models\AddressInfo::find()->where(['party_id'=>$id,'party_type_id'=>$type])->one();
        if($c_address!=null){
            $model = District::find()->where(['DISTRICT_ID' => $c_address->district_id])->one();
            $model_2 = Amphur::find()->where(['AMPHUR_ID' => $c_address->city_id])->one();
            $model_3 = Province::find()->where(['PROVINCE_ID' => $c_address->province_id])->one();
            $zipcode = $c_address->zipcode;
        }
        if($model_2!=null){
            $city_name = $model_2->AMPHUR_NAME;
        }
        if($model_3!=null){
            $province_name = $model_3->PROVINCE_NAME;
        }
        return $model != null ? $model->DISTRICT_NAME .' '.$city_name.' '.$province_name.' '.$zipcode : '';
    }
}
