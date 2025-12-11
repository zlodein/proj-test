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

if (isAuthenticated()) {
    redirect('/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $controller->forgotPassword();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/main.css" />
</head>
<body class="auth-forms">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Восстановление пароля</h1>
            <p>Мы отправим ссылку на ваш email</p>
        </div>
        
        <form id="forgotForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="form-input">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="your@email.com" required autofocus>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Отправить ссылку
            </button>
        </form>
        
        <div class="auth-footer-get">
            <a href="/auth/login.php"><i class="fas fa-arrow-left"></i> Вернуться к входу</a>
        </div>
    </div>
    
    <script>
        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/auth/forgot-password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    window.location.href = '/auth/login.php';
                } else {
                    alert(data.error || 'Ошибка отправки');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка соединения с сервером');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    </script>
</body>
</html>
