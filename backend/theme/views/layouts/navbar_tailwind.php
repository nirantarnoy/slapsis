<?php
use yii\helpers\Html;
use yii\helpers\Url;

// Helper to check active state
$isActive = function($controller) {
    return Yii::$app->controller->id === $controller;
};
$isGroupActive = function($controllers) {
    return in_array(Yii::$app->controller->id, $controllers);
};
?>
<header class="sticky top-0 z-50 w-full bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm transition-all duration-300" x-data="{ mobileMenuOpen: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
                <a href="<?= Url::to(['site/index']) ?>" class="flex items-center space-x-2 font-bold text-xl tracking-wider text-indigo-600 hover:text-indigo-500 transition">
                    <img src="<?= Yii::$app->request->baseUrl ?>/uploads/logo/slapsis_logo.jpg" alt="SLAPSIS" class="h-14 w-auto rounded-lg shadow-sm">
                </a>
            </div>

            <!-- Right Side: Menu & Profile -->
            <div class="flex items-center space-x-4">
                
                <!-- Desktop Menu -->
                <div class="hidden lg:flex lg:space-x-1 items-center">
                    
                    <!-- Settings -->
                    <a href="<?= Url::to(['site/index']) ?>" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $isActive('site') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                        ตั้งค่า
                    </a>

                    <!-- Products -->
                    <a href="<?= Url::to(['product/index']) ?>" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $isActive('product') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                        สินค้า
                    </a>

                    <!-- Orders -->
                    <a href="<?= Url::to(['order/index']) ?>" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $isActive('order') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                        คำสั่งซื้อ
                    </a>

                    <!-- Single Links -->
                    <a href="<?= Url::to(['expense/index']) ?>" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $isActive('expense') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                        ค่าใช้จ่าย
                    </a>

                    <!-- Income Dropdown (Grouped for cleaner UI) -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 inline-flex items-center <?= $isGroupActive(['income-compare', 'shopee-income', 'tiktok-income']) ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                            <span>รายได้ & ค่าธรรมเนียม</span>
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div x-show="open" class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" style="display: none;">
                            <div class="py-1">
                                <a href="<?= Url::to(['income-compare/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">เปรียบเทียบรายได้</a>
                                <a href="<?= Url::to(['shopee-income/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">ค่าธรรมเนียม Shopee</a>
                                <a href="<?= Url::to(['tiktok-income/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">ค่าธรรมเนียม Tiktok</a>
                            </div>
                        </div>
                    </div>
                    
                    <a href="<?= Url::to(['sync-log/index']) ?>" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 <?= $isActive('sync-log') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                        Sync Log
                    </a>

                    <!-- Stock Management -->
                    <?php if(Yii::$app->user->can('productgroup/index')||Yii::$app->user->can('product/index')||Yii::$app->user->can('warehouse/index')||Yii::$app->user->can('stocksum/index')):?>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 inline-flex items-center <?= $isGroupActive(['productgroup', 'unit', 'productbrand', 'warehouse', 'stocksum']) ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                            <span>สต๊อก</span>
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div x-show="open" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" style="display: none;">
                            <div class="py-1">
                                <?php if (Yii::$app->user->can('productgroup/index')): ?>
                                <a href="<?= Url::to(['productgroup/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">กลุ่มสินค้า</a>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('product/index')): ?>
                                <a href="<?= Url::to(['unit/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">หน่วยนับ</a>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('productbrand/index')): ?>
                                <a href="<?= Url::to(['productbrand/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">ยี่ห้อสินค้า</a>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('warehouse/index')): ?>
                                <a href="<?= Url::to(['warehouse/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">คลังสินค้า</a>
                                <?php endif; ?>
                                <?php if (Yii::$app->user->can('stocksum/index')): ?>
                                <a href="<?= Url::to(['stocksum/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">สินค้าคงเหลือ</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Admin -->
                    <?php if (\backend\models\User::findName(Yii::$app->user->id) == 'slapsis'): ?>
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 inline-flex items-center <?= $isGroupActive(['usergroup', 'user', 'authitem']) ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                            <span>Admin</span>
                            <i class="fas fa-chevron-down ml-1 text-xs"></i>
                        </button>
                        <div x-show="open" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" style="display: none;">
                            <div class="py-1">
                                <a href="<?= Url::to(['usergroup/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">กลุ่มผู้ใช้งาน</a>
                                <a href="<?= Url::to(['user/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">ผู้ใช้งาน</a>
                                <a href="<?= Url::to(['authitem/index']) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">สิทธิ์การใช้งาน</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <!-- Mobile Menu Button -->
                <div class="flex lg:hidden">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false">
                        <span class="sr-only">Open main menu</span>
                        <!-- Icon when menu is closed. -->
                        <svg x-show="!mobileMenuOpen" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Icon when menu is open. -->
                        <svg x-show="mobileMenuOpen" class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Profile -->
                <div class="flex items-center pl-4 border-l border-gray-200">
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false" class="flex items-center space-x-2 text-gray-700 hover:text-indigo-600 focus:outline-none transition-colors">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold border border-indigo-200">
                                <?= substr(Yii::$app->user->identity->username ?? 'U', 0, 1) ?>
                            </div>
                            <span class="hidden md:block font-medium text-sm"><?= Yii::$app->user->identity->username ?? 'Guest' ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        
                        <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 border border-gray-100 z-50" style="display: none;">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600">Profile</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'block']) ?>
                                <?= Html::submitButton('Sign out', ['class' => 'w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 font-medium bg-transparent border-0 cursor-pointer']) ?>
                            <?= Html::endForm() ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenuOpen" class="lg:hidden bg-white border-t border-gray-200" id="mobile-menu" style="display: none;">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <a href="<?= Url::to(['site/index']) ?>" class="block px-3 py-2 rounded-md text-base font-medium <?= $isActive('site') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">ตั้งค่า</a>
            <a href="<?= Url::to(['product/index']) ?>" class="block px-3 py-2 rounded-md text-base font-medium <?= $isActive('product') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">สินค้า</a>
            <a href="<?= Url::to(['order/index']) ?>" class="block px-3 py-2 rounded-md text-base font-medium <?= $isActive('order') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">คำสั่งซื้อ</a>
            <a href="<?= Url::to(['expense/index']) ?>" class="block px-3 py-2 rounded-md text-base font-medium <?= $isActive('expense') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">ค่าใช้จ่าย</a>
            
            <!-- Mobile Dropdown: Income -->
            <div x-data="{ open: false }" class="space-y-1">
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base font-medium <?= $isGroupActive(['income-compare', 'shopee-income', 'tiktok-income']) ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                    <span>รายได้ & ค่าธรรมเนียม</span>
                    <i class="fas fa-chevron-down text-xs transform transition-transform" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" class="pl-4 space-y-1">
                    <a href="<?= Url::to(['income-compare/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">เปรียบเทียบรายได้</a>
                    <a href="<?= Url::to(['shopee-income/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">ค่าธรรมเนียม Shopee</a>
                    <a href="<?= Url::to(['tiktok-income/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">ค่าธรรมเนียม Tiktok</a>
                </div>
            </div>

            <a href="<?= Url::to(['sync-log/index']) ?>" class="block px-3 py-2 rounded-md text-base font-medium <?= $isActive('sync-log') ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">Sync Log</a>

            <!-- Mobile Dropdown: Stock -->
            <?php if(Yii::$app->user->can('productgroup/index')||Yii::$app->user->can('product/index')||Yii::$app->user->can('warehouse/index')||Yii::$app->user->can('stocksum/index')):?>
            <div x-data="{ open: false }" class="space-y-1">
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base font-medium <?= $isGroupActive(['productgroup', 'unit', 'productbrand', 'warehouse', 'stocksum']) ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                    <span>สต๊อก</span>
                    <i class="fas fa-chevron-down text-xs transform transition-transform" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" class="pl-4 space-y-1">
                    <?php if (Yii::$app->user->can('productgroup/index')): ?>
                    <a href="<?= Url::to(['productgroup/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">กลุ่มสินค้า</a>
                    <?php endif; ?>
                    <?php if (Yii::$app->user->can('product/index')): ?>
                    <a href="<?= Url::to(['unit/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">หน่วยนับ</a>
                    <?php endif; ?>
                    <?php if (Yii::$app->user->can('productbrand/index')): ?>
                    <a href="<?= Url::to(['productbrand/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">ยี่ห้อสินค้า</a>
                    <?php endif; ?>
                    <?php if (Yii::$app->user->can('warehouse/index')): ?>
                    <a href="<?= Url::to(['warehouse/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">คลังสินค้า</a>
                    <?php endif; ?>
                    <?php if (Yii::$app->user->can('stocksum/index')): ?>
                    <a href="<?= Url::to(['stocksum/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">สินค้าคงเหลือ</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Mobile Dropdown: Admin -->
            <?php if (\backend\models\User::findName(Yii::$app->user->id) == 'slapsis'): ?>
            <div x-data="{ open: false }" class="space-y-1">
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base font-medium <?= $isGroupActive(['usergroup', 'user', 'authitem']) ? 'text-indigo-600 bg-indigo-50' : 'text-gray-600 hover:text-indigo-600 hover:bg-gray-50' ?>">
                    <span>Admin</span>
                    <i class="fas fa-chevron-down text-xs transform transition-transform" :class="{ 'rotate-180': open }"></i>
                </button>
                <div x-show="open" class="pl-4 space-y-1">
                    <a href="<?= Url::to(['usergroup/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">กลุ่มผู้ใช้งาน</a>
                    <a href="<?= Url::to(['user/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">ผู้ใช้งาน</a>
                    <a href="<?= Url::to(['authitem/index']) ?>" class="block px-3 py-2 rounded-md text-sm text-gray-500 hover:text-indigo-600 hover:bg-gray-50">สิทธิ์การใช้งาน</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>
