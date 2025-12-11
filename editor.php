<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (!function_exists('getPresentationWithTheme')) {
    die('Функция getPresentationWithTheme не найдена');
}

spl_autoload_register(function ($class) {
    foreach ([__DIR__ . '/src/Models/', __DIR__ . '/src/Controllers/'] as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

session_start();
requireAuth();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) redirect('/index.php', 'Презентация не найдена', 'error');

if (!canAccessPresentation($id)) redirect('/index.php', 'Доступ запрещён', 'error');

$presentation = getPresentationWithTheme($id);
if (!$presentation) redirect('/index.php', 'Презентация не найдена', 'error');

$user = getCurrentUser();
$slides = $presentation['slides'] ?? [];
$themeColor = $presentation['theme_color'] ?? '#2c7f8d';
$showAllCurrencies = $presentation['show_all_currencies'] ?? false;
$isMobile = isMobileDevice();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include __DIR__ . '/editor-data/head.php'; ?>
</head>
<body>
    <?php if ($isMobile): ?>
        <?php include __DIR__ . '/editor-data/mobile-editor.php'; ?>
    <?php else: ?>
        <?php include __DIR__ . '/editor-data/header.php'; ?>
        <?php include __DIR__ . '/editor-data/carousel.php'; ?>
        <?php include __DIR__ . '/editor-data/save-indicator.php'; ?>
        <?php include __DIR__ . '/editor-data/add-slide-modal.php'; ?>
    <?php endif; ?>
    
    <?php include __DIR__ . '/editor-data/scripts.php'; ?>
</body>
</html>