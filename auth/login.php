<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../src/Models/',
        __DIR__ . '/../src/Controllers/'
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/main.css" />
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
