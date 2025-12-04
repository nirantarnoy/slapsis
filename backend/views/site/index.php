<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $tiktokShops array */
/* @var $shopeeShops array */

$this->title = 'ตั้งค่าการเชื่อมต่อ (Connection Settings)';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="site-index space-y-8">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Channels & Connections</h2>
            <p class="text-gray-500">จัดการการเชื่อมต่อร้านค้าของคุณ</p>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline"><?= Yii::$app->session->getFlash('success') ?></span>
        </div>
    <?php endif; ?>
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg relative" role="alert">
            <span class="block sm:inline"><?= Yii::$app->session->getFlash('error') ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- TikTok Shop Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-black rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg">
                            <i class="fab fa-tiktok"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">TikTok Shop</h3>
                            <p class="text-sm text-gray-500">Video-sharing focused social networking</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= count($tiktokShops) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= count($tiktokShops) > 0 ? 'Connected' : 'Not Connected' ?>
                    </span>
                </div>

                <div class="space-y-4">
                    <?php if (count($tiktokShops) > 0): ?>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Connected Shops</h4>
                            <div class="space-y-3">
                                <?php foreach ($tiktokShops as $shop): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-600">
                                                <i class="fas fa-store"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 text-sm"><?= Html::encode($shop['shop_name'] ?? 'Shop ID: ' . $shop['shop_id']) ?></p>
                                                <p class="text-xs text-emerald-600 flex items-center">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                                    Active
                                                </p>
                                            </div>
                                        </div>
                                        <!-- <button class="text-gray-400 hover:text-red-500 transition-colors">
                                            <i class="fas fa-unlink"></i>
                                        </button> -->
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <p class="text-gray-500 text-sm mb-2">ยังไม่มีร้านค้าที่เชื่อมต่อ</p>
                        </div>
                    <?php endif; ?>

                    <a href="<?= Url::to(['site/connect-tiktok']) ?>" class="block w-full text-center bg-black hover:bg-gray-800 text-white font-medium py-3 px-4 rounded-xl transition-colors duration-200 shadow-sm">
                        <i class="fas fa-plus mr-2"></i> เชื่อมต่อ TikTok Shop
                    </a>
                </div>
            </div>
        </div>

        <!-- Shopee Shop Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Shopee</h3>
                            <p class="text-sm text-gray-500">Leading online shopping platform</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= count($shopeeShops) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' ?>">
                        <?= count($shopeeShops) > 0 ? 'Connected' : 'Not Connected' ?>
                    </span>
                </div>

                <div class="space-y-4">
                    <?php if (count($shopeeShops) > 0): ?>
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Connected Shops</h4>
                            <div class="space-y-3">
                                <?php foreach ($shopeeShops as $shop): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-600">
                                                <i class="fas fa-store"></i>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 text-sm"><?= Html::encode($shop['shop_name'] ?? 'Shop ID: ' . $shop['shop_id']) ?></p>
                                                <p class="text-xs text-emerald-600 flex items-center">
                                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5"></span>
                                                    Active
                                                </p>
                                            </div>
                                        </div>
                                        <!-- <button class="text-gray-400 hover:text-red-500 transition-colors">
                                            <i class="fas fa-unlink"></i>
                                        </button> -->
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <p class="text-gray-500 text-sm mb-2">ยังไม่มีร้านค้าที่เชื่อมต่อ</p>
                        </div>
                    <?php endif; ?>

                    <a href="<?= Url::to(['site/connect-shopee']) ?>" class="block w-full text-center bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-xl transition-colors duration-200 shadow-sm">
                        <i class="fas fa-plus mr-2"></i> เชื่อมต่อ Shopee
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
