<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/Models/',
        __DIR__ . '/../src/Controllers/',
        __DIR__ . '/../src/OAuth/'
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
if (isset($_GET['logout'])) {
    logoutUser();
    setFlash('success', 'Вы вышли из системы');
    header('Location: /auth/login.php');
    exit;
}

if (isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $controller->login();
    exit;
}

$error = getFlash('error');
$success = getFlash('success');

// Загружаем конфигурацию OAuth
$oauthConfig = [];
if (file_exists(__DIR__ . '/oauth/config.php')) {
    $oauthConfig = require __DIR__ . '/oauth/config.php';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/main.css" />
    <style>
        /* OAuth Styles */
        .oauth-divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        
        .oauth-divider span {
            background: white;
            padding: 0 15px;
            color: #666;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        .oauth-divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }
        
        .oauth-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .oauth-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .oauth-btn:hover {
            opacity: 0.85;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .oauth-btn i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .oauth-yandex {
            background: #fc3f1d;
        }
        
        .oauth-vk {
            background: #0077ff;
        }
        
        .oauth-mailru {
            background: #168de2;
        }
    </style>
</head>
<body class="auth-forms">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Вход</h1>
            <p>Войдите в свой аккаунт</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo escape($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo escape($success); ?>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="/auth/login.php">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="form-input">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="your@email.com" 
                           required 
                           autofocus
                           autocomplete="username email">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Введите пароль" 
                           required
                           autocomplete="current-password">
                </div>
            </div>
            
            <div class="form-options">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Запомнить меня</label>
                </div>
                <a href="/auth/forgot-password.php" class="forgot-link">Забыли пароль?</a>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
        </form>
        
        <?php if (!empty($oauthConfig)): ?>
        <!-- OAuth разделитель -->
        <div class="oauth-divider">
            <span>или войдите через</span>
        </div>
        
        <!-- OAuth кнопки -->
        <div class="oauth-buttons">
            <?php
            $providers = [
                'yandex' => [
                    'name' => 'Яндекс',
                    'icon' => 'fab fa-yandex',
                    'class' => 'yandex',
                    'provider_class' => 'YandexProvider'
                ],
                'vk' => [
                    'name' => 'ВКонтакте',
                    'icon' => 'fab fa-vk',
                    'class' => 'vk',
                    'provider_class' => 'VKProvider'
                ],
                'mailru' => [
                    'name' => 'Mail.ru',
                    'icon' => 'fas fa-at',
                    'class' => 'mailru',
                    'provider_class' => 'MailRuProvider'
                ]
            ];
            
            // Получаем подключение к БД
            $db = Database::getInstance()->getConnection();
            
            foreach ($providers as $key => $provider):
                if (!isset($oauthConfig[$key])) continue;
                
                try {
                    $providerClass = $provider['provider_class'];
                    $oauthProvider = new $providerClass($oauthConfig[$key], $db);
                    $authUrl = $oauthProvider->getAuthUrl();
            ?>
            <a href="<?php echo htmlspecialchars($authUrl); ?>" class="oauth-btn oauth-<?php echo $provider['class']; ?>">
                <i class="<?php echo $provider['icon']; ?>"></i>
                <?php echo $provider['name']; ?>
            </a>
            <?php
                } catch (Exception $e) {
                    // Скрываем кнопку если провайдер не настроен
                    error_log("OAuth provider $key error: " . $e->getMessage());
                }
            endforeach;
            ?>
        </div>
        <?php endif; ?>
        
        <div class="auth-footer-get">
            <p>Нет аккаунта?</p>
            <a href="/auth/register.php">Зарегистрироваться</a>
        </div>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var btn = this.querySelector('.btn-submit');
            var originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вход...';
            
            var formData = new FormData(this);
            
            fetch('/auth/login.php', {
                method: 'POST',
                body: formData
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (data.success) {
                    window.location.href = data.redirect || '/index.php';
                } else {
                    alert(data.error || 'Ошибка входа');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером. Проверьте консоль.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    </script>
</body>
</html>
