<?php
$this->title = 'Starter Page';
$this->params['breadcrumbs'] = [['label' => $this->title]];

if (Yii::$app->user->isGuest)
    Yii::$app->response->redirect(['site_/login']);
?>