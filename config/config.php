<?php
define('APP_NAME', 'Presentation Realty');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://presentation-realty.ru');
define('APP_ENV', 'development');
define('SESSION_LIFETIME', 604800);
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_RESET_EXPIRY', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_TIME', 900);
define('DB_HOST', 'localhost');
define('DB_NAME', 'cq88845_present');
define('DB_USER', 'cq88845_present');
define('DB_PASS', 'asf234erdsSD@');
define('DB_CHARSET', 'utf8mb4');
define('ROOT_PATH', '/home/c/cq88845/presentation-realty.ru/public_html');
define('UPLOAD_DIR', ROOT_PATH . '/assets/uploads/');
define('PRESENTATIONS_DIR', ROOT_PATH . '/presentations/');
define('LOGS_DIR', ROOT_PATH . '/logs/');
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('MAIL_FROM', 'noreply@presentation-realty.ru');
define('MAIL_FROM_NAME', 'Presentation Realty');
define('DEFAULT_USER_ROLE', 1);
define('ADMIN_ROLE_ID', 2);
define('YOOKASSA_SHOP_ID', 'your_shop_id');
define('YOOKASSA_SECRET_KEY', 'your_secret_key');
define('SUPPORT_EMAIL', 'support@presentation-realty.ru');
define('COMPANY_NAME', 'Presentation Realty');
define('COMPANY_ADDRESS', 'г. Москва, ул. Примерная, д. 1');
define('COMPANY_PHONE', '+7 (999) 123-45-67');
define('COMPANY_INN', '1234567890');
define('COMPANY_OGRN', '1234567890123');

// YooKassa (если будете использовать)
// define('YOOKASSA_SHOP_ID', 'your_shop_id');
// define('YOOKASSA_SECRET_KEY', 'your_secret_key');

define('REVEAL_VERSION', '4.6.0');
define('REVEAL_CDN', 'https://cdnjs.cloudflare.com/ajax/libs/reveal.js/' . REVEAL_VERSION);
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_DIR . 'error.log');
}

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);

$directories = [UPLOAD_DIR, PRESENTATIONS_DIR, LOGS_DIR];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}
