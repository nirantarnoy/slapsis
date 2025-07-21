<?php
?>
<form action="<?= \yii\helpers\Url::to(['product/importproduct'],true) ?>" class="form" enctype="multipart/form-data" method="post">
    <div class="row">
        <div class="col-lg-6">
            <input type="file" name="file_import" class="form-control">
        </div>
    </div>
    <div class="row">
        <div class="col-lg-1">
            <button class="btn btn-success">Import</button>
        </div>
    </div>
</form>
