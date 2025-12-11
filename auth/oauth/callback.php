<?php
/**
 * OAuth Callback Handler
 * Обработчик обратного вызова от OAuth провайдеров
 */

// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../../src/OAuth/',
        __DIR__ . '/../../src/Models/',
        __DIR__ . '/../../src/Controllers/'
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

session_start();

// Получаем провайдер и код
$provider = $_GET['provider'] ?? null;
$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;

// Проверка на отмену пользователем
if ($error) {
    setFlash('error', 'Вы отменили авторизацию');
    header('Location: /auth/login.php');
    exit;
}

// Проверка обязательных параметров
if (!$provider || !$code) {
    setFlash('error', 'Ошибка авторизации. Недостаточно параметров.');
    header('Location: /auth/login.php');
    exit;
}

// Загружаем конфигурацию OAuth
$oauthConfig = require __DIR__ . '/config.php';

if (!isset($oauthConfig[$provider])) {
    setFlash('error', 'Неизвестный OAuth провайдер');
    header('Location: /auth/login.php');
    exit;
}

try {
    // Создаем экземпляр провайдера
    $providerClass = ucfirst($provider) . 'Provider';
    
    if (!class_exists($providerClass)) {
        throw new Exception('Класс провайдера не найден: ' . $providerClass);
    }
    
    // Получаем подключение к БД
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        throw new Exception('Ошибка подключения к базе данных');
    }
    
    $oauthProvider = new $providerClass($oauthConfig[$provider], $db);
    
    // Обмениваем код на токен
    $accessToken = $oauthProvider->getAccessToken($code);
    
    if (!$accessToken) {
        throw new Exception('Не удалось получить токен доступа');
    }
    
    // Получаем информацию о пользователе
    $userInfo = $oauthProvider->getUserInfo($accessToken);
    
    if (empty($userInfo['email'])) {
        throw new Exception('Не удалось получить email. Предоставьте доступ к email.');
    }
    
    // Обрабатываем пользователя (создаем или находим существующего)
    $userId = $oauthProvider->processUser($userInfo);
    
    if (!$userId) {
        throw new Exception('Ошибка создания/поиска пользователя');
    }
    
    // Авторизуем пользователя
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $userInfo['email'];
    $_SESSION['authenticated'] = true;
    
    // Названия провайдеров для сообщения
    $providerNames = [
        'yandex' => 'Яндекс',
        'vk' => 'ВКонтакте',
        'mailru' => 'Mail.ru'
    ];
    
    $providerName = $providerNames[$provider] ?? ucfirst($provider);
    setFlash('success', 'Вы успешно вошли через ' . $providerName);
    header('Location: /index.php');
    exit;
    
} catch (Exception $e) {
    // Логируем ошибку
    error_log("OAuth Error ($provider): " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    setFlash('error', 'Ошибка авторизации: ' . $e->getMessage());
    header('Location: /auth/login.php');
    exit;
}