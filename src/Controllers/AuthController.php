<?php
/**
 * Контроллер авторизации
 * Путь: /public_html/src/Controllers/AuthController.php
 */

class AuthController {
    
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Вход пользователя
     */
    public function login() {
        // Очистка буфера вывода
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        header('Content-Type: application/json; charset=utf-8');
        
        // Проверка метода
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Метод не разрешён'], 405);
            return;
        }
        
        // Получение данных
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Проверка CSRF токена
        if (!verifyCsrfToken($csrfToken)) {
            $this->jsonResponse(['error' => 'Недействительный токен безопасности'], 403);
            return;
        }
        
        // Валидация
        if (empty($email) || empty($password)) {
            $this->jsonResponse(['error' => 'Заполните все поля'], 400);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Неверный формат email'], 400);
            return;
        }
        
        // Проверка блокировки
        if (checkLoginAttempts($email)) {
            $this->jsonResponse(['error' => 'Слишком много попыток входа. Попробуйте позже.'], 429);
            return;
        }
        
        // Поиск пользователя
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            recordLoginAttempt($email, false);
            $this->jsonResponse(['error' => 'Неверный email или пароль'], 401);
            return;
        }
        
        // Проверка пароля
        if (!verifyPassword($password, $user['password'])) {
            recordLoginAttempt($email, false);
            $this->jsonResponse(['error' => 'Неверный email или пароль'], 401);
            return;
        }
        
        // Проверка активности
        if (!$user['is_active']) {
            $this->jsonResponse(['error' => 'Аккаунт заблокирован'], 403);
            return;
        }
        
        // Успешный вход
        loginUser($user['id']);
        recordLoginAttempt($email, true);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Вход выполнен успешно',
            'redirect' => '/index.php'
        ]);
    }
    
    /**
     * Регистрация пользователя
     */
    public function register() {
        // Очистка буфера вывода
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        header('Content-Type: application/json; charset=utf-8');
        
        // Проверка метода
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Метод не разрешён'], 405);
            return;
        }
        
        // Получение данных
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        // Проверка CSRF токена
        if (!verifyCsrfToken($csrfToken)) {
            $this->jsonResponse(['error' => 'Недействительный токен безопасности'], 403);
            return;
        }
        
        // Валидация
        if (empty($name) || empty($email) || empty($password)) {
            $this->jsonResponse(['error' => 'Заполните все обязательные поля'], 400);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Неверный формат email'], 400);
            return;
        }
        
        if (strlen($name) < 2 || strlen($name) > 100) {
            $this->jsonResponse(['error' => 'Имя должно быть от 2 до 100 символов'], 400);
            return;
        }
        
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $this->jsonResponse(['error' => 'Пароль должен быть минимум ' . PASSWORD_MIN_LENGTH . ' символов'], 400);
            return;
        }
        
        if ($password !== $passwordConfirm) {
            $this->jsonResponse(['error' => 'Пароли не совпадают'], 400);
            return;
        }
        
        // Проверка существования email
        if ($this->userModel->emailExists($email)) {
            $this->jsonResponse(['error' => 'Пользователь с таким email уже существует'], 409);
            return;
        }
        
        // Создание пользователя
        try {
            $userId = $this->userModel->create([
                'name' => $name,
                'email' => $email,
                'password' => $password
            ]);
            
            // Автоматический вход
            loginUser($userId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Регистрация успешна',
                'redirect' => '/index.php'
            ]);
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Ошибка регистрации. Попробуйте позже.'], 500);
        }
    }
    
    /**
     * Восстановление пароля
     */
    public function forgotPassword() {
        // Очистка буфера вывода
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Метод не разрешён'], 405);
            return;
        }
        
        $email = trim($_POST['email'] ?? '');
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrfToken)) {
            $this->jsonResponse(['error' => 'Недействительный токен'], 403);
            return;
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['error' => 'Неверный email'], 400);
            return;
        }
        
        $user = $this->userModel->findByEmail($email);
        
        if ($user) {
            $token = $this->userModel->createPasswordResetToken($email);
            $resetLink = APP_URL . "/auth/reset-password.php?token=" . $token;
            
            // Отправка email (заглушка)
            error_log("Password reset link for {$email}: {$resetLink}");
        }
        
        // Всегда успех (безопасность)
        $this->jsonResponse([
            'success' => true,
            'message' => 'Если email существует, на него будет отправлена ссылка для сброса пароля'
        ]);
    }
    
    /**
     * Сброс пароля
     */
    public function resetPassword($token) {
        // Очистка буфера вывода
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        header('Content-Type: application/json; charset=utf-8');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Метод не разрешён'], 405);
            return;
        }
        
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrfToken)) {
            $this->jsonResponse(['error' => 'Недействительный токен'], 403);
            return;
        }
        
        if (empty($password) || strlen($password) < PASSWORD_MIN_LENGTH) {
            $this->jsonResponse(['error' => 'Пароль слишком короткий'], 400);
            return;
        }
        
        if ($password !== $passwordConfirm) {
            $this->jsonResponse(['error' => 'Пароли не совпадают'], 400);
            return;
        }
        
        $user = $this->userModel->findByResetToken($token);
        
        if (!$user) {
            $this->jsonResponse(['error' => 'Недействительная или истёкшая ссылка'], 400);
            return;
        }
        
        if ($this->userModel->updatePassword($user['id'], $password)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Пароль успешно изменён',
                'redirect' => '/auth/login.php'
            ]);
        } else {
            $this->jsonResponse(['error' => 'Ошибка обновления пароля'], 500);
        }
    }
    
    /**
     * Отправка JSON ответа
     */
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        
        // Очистка буфера
        if (ob_get_level()) {
            ob_clean();
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Очистка и завершение
        if (ob_get_level()) {
            ob_end_flush();
        }
        
        exit;
    }
}
