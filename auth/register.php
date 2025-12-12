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
    $controller->register();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/main.css" />
</head>
<body class="auth-forms">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Регистрация</h1>
            <p>Создайте свой аккаунт</p>
        </div>
        
        <form id="registerForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="name">Имя</label>
                <div class="form-input">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" class="form-control" 
                           placeholder="Ваше имя" required autofocus minlength="2" maxlength="100">
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="form-input">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="your@email.com" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Минимум <?php echo PASSWORD_MIN_LENGTH; ?> символов" 
                           required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                </div>
                <div id="passwordStrength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Подтверждение пароля</label>
                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" 
                           placeholder="Повторите пароль" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i> Зарегистрироваться
            </button>
        </form>
        
        <div class="auth-footer-get">
            <p>Уже есть аккаунт?</p>
            <a href="/auth/login.php">Войти</a>
        </div>
    </div>
    
    <script>
        // Проверка надёжности пароля
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthDiv.textContent = '⚠️ Слабый пароль';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                strengthDiv.textContent = '✓ Средний пароль';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = '✓ Надёжный пароль';
                strengthDiv.className = 'password-strength strength-strong';
            }
        });
        
        // Отправка формы
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                alert('Пароли не совпадают');
                return;
            }
            
            const btn = this.querySelector('.btn-submit');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Регистрация...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/auth/register.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.href = data.redirect || '/index.php';
                } else {
                    alert(data.error || 'Ошибка регистрации');
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
