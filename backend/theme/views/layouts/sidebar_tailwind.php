<aside :class="sidebarOpen ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'" class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-white transition-transform duration-300 transform lg:translate-x-0 lg:static lg:inset-0 shadow-2xl flex flex-col">
    <!-- Brand -->
    <div class="flex items-center justify-center h-16 bg-slate-950 border-b border-slate-800">
        <a href="index.php?r=site/index" class="flex items-center space-x-2 font-bold text-xl tracking-wider text-white hover:text-indigo-400 transition">
            <!-- <img src="<?php echo Yii::$app->request->baseUrl; ?>/uploads/logo/slapsis_logo.jpg" alt="Logo" class="h-8 w-auto rounded"> -->
            <span>SLAPSIS</span>
        </a>
    </div>

    <!-- Menu -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
        
        <?php
        // Helper to check active state
        $isActive = function($controller) {
            return Yii::$app->controller->id === $controller;
        };
        $isGroupActive = function($controllers) {
            return in_array(Yii::$app->controller->id, $controllers);
        };
        ?>

        <!-- Settings Group -->
        <div x-data="{ open: <?= $isGroupActive(['site']) ? 'true' : 'false' ?> }" class="mb-1">
            <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 focus:outline-none" :class="open ? 'bg-slate-800 text-indigo-400' : 'text-slate-300'">
                <div class="flex items-center">
                    <i class="fas fa-cog w-5 h-5 mr-3 text-center"></i>
                    <span>ตั้งค่า</span>
                </div>
                <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''"></i>
            </button>
            <div x-show="open" class="mt-1 space-y-1 pl-10" style="display: none;">
                <a href="<?=\Yii\helpers\Url::to(['site/index'])?>" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('site') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    เชื่อมต่อร้านค้า
                </a>
            </div>
        </div>

        <!-- Product Group -->
        <div x-data="{ open: <?= $isGroupActive(['product']) ? 'true' : 'false' ?> }" class="mb-1">
            <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 focus:outline-none" :class="open ? 'bg-slate-800 text-indigo-400' : 'text-slate-300'">
                <div class="flex items-center">
                    <i class="fas fa-cubes w-5 h-5 mr-3 text-center"></i>
                    <span>สินค้า</span>
                </div>
                <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''"></i>
            </button>
            <div x-show="open" class="mt-1 space-y-1 pl-10" style="display: none;">
                <a href="<?=\Yii\helpers\Url::to(['product/index'])?>" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('product') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    ข้อมูลสินค้า
                </a>
            </div>
        </div>

        <!-- Order Group -->
        <div x-data="{ open: <?= $isGroupActive(['order']) ? 'true' : 'false' ?> }" class="mb-1">
            <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 focus:outline-none" :class="open ? 'bg-slate-800 text-indigo-400' : 'text-slate-300'">
                <div class="flex items-center">
                    <i class="fas fa-shopping-cart w-5 h-5 mr-3 text-center"></i>
                    <span>คำสั่งซื้อ</span>
                </div>
                <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''"></i>
            </button>
            <div x-show="open" class="mt-1 space-y-1 pl-10" style="display: none;">
                <a href="<?=\Yii\helpers\Url::to(['order/index'])?>" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('order') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    คำสั่งซื้อล่าสุด
                </a>
            </div>
        </div>

        <!-- Single Items -->
        <a href="<?=\Yii\helpers\Url::to(['expense/index'])?>" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 hover:text-white <?= $isActive('expense') ? 'bg-slate-800 text-indigo-400' : 'text-slate-300' ?>">
            <i class="fas fa-money-check w-5 h-5 mr-3 text-center"></i>
            <span>บันทึกค่าใช้จ่าย</span>
        </a>

        <a href="<?=\Yii\helpers\Url::to(['income-compare/index'])?>" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 hover:text-white <?= $isActive('income-compare') ? 'bg-slate-800 text-indigo-400' : 'text-slate-300' ?>">
            <i class="fas fa-chart-line w-5 h-5 mr-3 text-center"></i>
            <span>ค่าธรรมเนียมรวม</span>
        </a>

        <a href="<?=\Yii\helpers\Url::to(['shopee-income/index'])?>" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 hover:text-white <?= $isActive('shopee-income') ? 'bg-slate-800 text-indigo-400' : 'text-slate-300' ?>">
            <i class="fas fa-money-bill-wave w-5 h-5 mr-3 text-center"></i>
            <span>ค่าธรรมเนียม Shopee</span>
        </a>

        <a href="<?=\Yii\helpers\Url::to(['tiktok-income/index'])?>" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 hover:text-white <?= $isActive('tiktok-income') ? 'bg-slate-800 text-indigo-400' : 'text-slate-300' ?>">
            <i class="fas fa-list w-5 h-5 mr-3 text-center"></i>
            <span>ค่าธรรมเนียม Tiktok</span>
        </a>

        <a href="<?=\Yii\helpers\Url::to(['sync-log/index'])?>" class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 hover:text-white <?= $isActive('sync-log') ? 'bg-slate-800 text-indigo-400' : 'text-slate-300' ?>">
            <i class="fas fa-history w-5 h-5 mr-3 text-center"></i>
            <span>ประวัติการ Sync</span>
        </a>

        <!-- Stock Management Group -->
        <?php if(\Yii::$app->user->can('productgroup/index')||\Yii::$app->user->can('product/index')||\Yii::$app->user->can('warehouse/index')||\Yii::$app->user->can('product/index')||\Yii::$app->user->can('stocksum/index')||\Yii::$app->user->can('stocktrans/index')):?>
        <div x-data="{ open: <?= $isGroupActive(['productgroup', 'unit', 'productbrand', 'warehouse', 'stocksum']) ? 'true' : 'false' ?> }" class="mb-1 mt-4">
            <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                จัดการสต๊อก
            </div>
            <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 focus:outline-none" :class="open ? 'bg-slate-800 text-indigo-400' : 'text-slate-300'">
                <div class="flex items-center">
                    <i class="fas fa-cubes w-5 h-5 mr-3 text-center"></i>
                    <span>จัดการสต๊อกสินค้า</span>
                </div>
                <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''"></i>
            </button>
            <div x-show="open" class="mt-1 space-y-1 pl-10" style="display: none;">
                <?php if (\Yii::$app->user->can('productgroup/index')): ?>
                <a href="index.php?r=productgroup/index" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('productgroup') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    กลุ่มสินค้า
                </a>
                <?php endif; ?>
                
                <?php if (\Yii::$app->user->can('product/index')): ?>
                <a href="index.php?r=unit" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('unit') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    หน่วยนับ
                </a>
                <?php endif; ?>

                <?php if (\Yii::$app->user->can('productbrand/index')): ?>
                <a href="index.php?r=productbrand" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('productbrand') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    ยี่ห้อสินค้า
                </a>
                <?php endif; ?>

                <?php if (\Yii::$app->user->can('warehouse/index')): ?>
                <a href="index.php?r=warehouse" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('warehouse') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    คลังสินค้า
                </a>
                <?php endif; ?>

                <?php if (\Yii::$app->user->can('stocksum/index')): ?>
                <a href="index.php?r=stocksum" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('stocksum') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    สินค้าคงเหลือ
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Admin -->
        <?php  if (\backend\models\User::findName(\Yii::$app->user->id) == 'slapsis'): ?>
        <div x-data="{ open: <?= $isGroupActive(['usergroup', 'user', 'authitem', 'dbbackup', 'dbrestore']) ? 'true' : 'false' ?> }" class="mb-1 mt-4">
            <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                ผู้ดูแลระบบ
            </div>
            
            <button @click="open = !open" class="flex items-center justify-between w-full px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 hover:bg-slate-800 focus:outline-none" :class="open ? 'bg-slate-800 text-indigo-400' : 'text-slate-300'">
                <div class="flex items-center">
                    <i class="fas fa-user-shield w-5 h-5 mr-3 text-center"></i>
                    <span>ผู้ใช้งาน</span>
                </div>
                <i class="fas fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'transform rotate-180' : ''"></i>
            </button>
            <div x-show="open" class="mt-1 space-y-1 pl-10" style="display: none;">
                <a href="<?=\Yii\helpers\Url::to(['usergroup/index'])?>" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('usergroup') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    กลุ่มผู้ใช้งาน
                </a>
                <a href="<?=\Yii\helpers\Url::to(['user/index'])?>" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('user') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    ผู้ใช้งาน
                </a>
                <a href="<?=\Yii\helpers\Url::to(['authitem/index'])?>" class="block px-3 py-2 text-sm rounded-md transition-colors duration-200 hover:text-white hover:bg-indigo-600 <?= $isActive('authitem') ? 'bg-indigo-600 text-white' : 'text-slate-400' ?>">
                    สิทธิ์การใช้งาน
                </a>
            </div>
        </div>
        <?php endif; ?>

    </nav>
</aside>
