<?php

use yii\helpers\Html;
use backend\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-full bg-gray-50">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Prompt"', '"Outfit"', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4F46E5', // Indigo 600
                        secondary: '#10B981', // Emerald 500
                        dark: '#1F2937', // Gray 800
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <?php $this->head() ?>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1; 
        }
        ::-webkit-scrollbar-thumb {
            background: #888; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555; 
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-gray-900 bg-gray-50 flex flex-col">
<?php $this->beginBody() ?>

    <!-- Navbar -->
    <?= $this->render('navbar_tailwind') ?>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Content -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 min-h-[calc(100vh-200px)]">
            <?= $content ?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 p-4 text-center text-sm text-gray-500 mt-auto">
        &copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?>
    </footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
