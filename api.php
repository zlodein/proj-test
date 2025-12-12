<?php
if (ob_get_level()) ob_end_clean();
ob_start();

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/currency.php';

spl_autoload_register(function ($class) {
    foreach ([__DIR__ . '/src/Models/', __DIR__ . '/src/Controllers/'] as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$errorHandler = function($message, $exception = null) {
    if (ob_get_level()) ob_clean();
    
    if ($exception) {
        error_log("Exception: " . $exception->getMessage());
    }
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Server Error',
        'message' => APP_ENV === 'development' ? $message : 'Внутренняя ошибка сервера'
    ], JSON_UNESCAPED_UNICODE);
    exit;
};

set_error_handler(function($errno, $errstr, $errfile, $errline) use ($errorHandler) {
    $errorHandler("[$errno] $errstr in $errfile:$errline");
});

set_exception_handler(function($exception) use ($errorHandler) {
    $errorHandler($exception->getMessage(), $exception);
});

if (ob_get_level()) ob_clean();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

error_log("API Request: $action | Method: $method | User: " . (getCurrentUserId() ?? 'guest'));

try {
    switch ($action) {
        case 'create_presentation':
        case 'get_presentations':
        case 'get_presentation':
        case 'update_presentation':
        case 'delete_presentation':
        case 'toggle_publish':
        case 'duplicate_presentation':
        case 'find_nearest_metro':
            require_once __DIR__ . '/api-data/presentations.php';
            handlePresentationRequest($action);
            break;
            
        case 'upload_image':
        case 'upload_multiple':
        case 'delete_image':
        case 'get_images':
            require_once __DIR__ . '/api-data/images.php';
            handleImageRequest($action);
            break;
            
        case 'generate_presentation':
        case 'export_pdf':
            require_once __DIR__ . '/api-data/export.php';
            handleExportRequest($action);
            break;
            
        case 'get_stats':
            require_once __DIR__ . '/api-data/stats.php';
            handleStatsRequest($action);
            break;
            
        case 'get_currency_rates':
        case 'convert_currency':
            require_once __DIR__ . '/api-data/currency.php';
            handleCurrencyRequest($action);
            break;
            
        case 'update_profile':
            require_once __DIR__ . '/api-data/profile.php';
            handleProfileRequest($action);
            break;
            
        case 'add_feature_request':
        case 'vote_feature':
        case 'update_feature_status':
            require_once __DIR__ . '/api-data/features.php';
            handleFeatureRequest($action);
            break;
            
        case 'get_tariffs':
        case 'create_payment':
        case 'can_create_presentation':
            require_once __DIR__ . '/api-data/tariffs.php';
            handleTariffRequest($action);
            break;
            
        case 'toggle_public_link':
        case 'get_public_link_info':
        case 'get_public_stats':
            require_once __DIR__ . '/api-data/share.php';
            handleShareRequest($action);
            break;
            
        case 'admin_impersonate':
            require_once __DIR__ . '/api-data/admin.php';
            handleAdminRequest($action);
            break;
            
        default:
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => "Неизвестное действие: $action"], JSON_UNESCAPED_UNICODE);
            exit;
    }
    
} catch (Exception $e) {
    error_log("API Error [$action]: " . $e->getMessage());
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Ошибка сервера',
        'message' => APP_ENV === 'development' ? $e->getMessage() : 'Внутренняя ошибка сервера'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>