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

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('/auth/login.php', 'Недействительная ссылка', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $controller->resetPassword($token);
    exit;
}

$userModel = new User();
$user = $userModel->findByResetToken($token);

if (!$user) {
    redirect('/auth/login.php', 'Ссылка недействительна или истекла', 'error');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новый пароль - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/main.css" />
</head>
<body class="auth-forms">
    <div class="auth-container">
        <div class="auth-header">
            <h1>Новый пароль</h1>
            <p>Введите новый пароль</p>
        </div>
        
        <form id="resetForm" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="token" value="<?php echo escape($token); ?>">
            
            <div class="form-group">
                <label for="password">Новый пароль</label>
                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Минимум <?php echo PASSWORD_MIN_LENGTH; ?> символов" 
                           required minlength="<?php echo PASSWORD_MIN_LENGTH; ?>" autofocus>
                </div>
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
                <i class="fas fa-check"></i> Сохранить пароль
            </button>
        </form>
    </div>
    
    <script>
        document.getElementById('resetForm').addEventListener('submit', async function(e) {
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
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect || '/auth/login.php';
                } else {
                    alert(data.error || 'Ошибка сохранения');
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
