<?php
$this->title = 'Import Master Data';
?>
<div class="row">
    <form action="index.php?r=mainconfig/importcustomer2" method="post" enctype="multipart/form-data">
        <div class="col-lg-10">
            <label for="">นำเข้าข้อมูลลูกค้า</label><br/>
            <input type="file" class="file-customer" name="file_customer" accept=".csv">
        </div>
        <div class="col-lg-2">
            <label for=""></label><br/>
            <input type="submit" class="btn btn-primary" value="นำเข้า">
        </div>
    </form>
</div>
<hr style="border-top: 1px dashed">
<div class="row">
    <form action="index.php?r=mainconfig/importemployee" method="post" enctype="multipart/form-data">
        <div class="col-lg-10">
            <label for="">นำเข้าข้อมูลพนักงาน</label><br/>
            <input type="file" class="file-employee" name="file_employee" accept=".csv">
        </div>
        <div class="col-lg-2">
            <label for=""></label><br/>
            <input type="submit" class="btn btn-primary" value="นำเข้า">
        </div>
    </form>
</div>
<br/>
<hr style="border-top: 1px dashed">
<form action="index.php?r=mainconfig/importupdateorderpay" method="post" enctype="multipart/form-data">
<div class="row">

        <div class="col-lg-6">
            <label for="">นำเข้าข้อมูลอัพเดทชำระเงิน</label><br/>
            <input type="file" class="file-employee" name="file_order_pay" accept=".csv">
        </div>
        <div class="col-lg-4">
            <label for="" style="color: white;">x</label>
            <div class="row">
                <div class="col-lg-6">
                    <input type="text" class="form-control" name="from_no" value="">
                </div>
                <div class="col-lg-6">
                    <input type="text" class="form-control" name="to_no" value="">
                </div>
            </div>
        </div>
        <div class="col-lg-2">
            <label for=""></label><br/>
            <input type="submit" class="btn btn-primary" value="นำเข้า">
        </div>

</div> </form>
<br/>
