<?php
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        redirect('/auth/login.php', 'Необходимо войти в систему', 'error');
    }
}

function requireGuest() {
    if (isAuthenticated()) {
        redirect('/index.php');
    }
}

function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, email, name, created_at, last_login_at, role_id, tariff_id FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    
    return $stmt->fetch();
}

// УДАЛИТЬ ЭТОТ БЛОК - функция уже есть в functions.php
// function getCurrentUserId() {
//     return $_SESSION['user_id'] ?? null;
// }

function isAdmin($userId = null) {
    if (!$userId) {
        $userId = getCurrentUserId(); // Эта функция из functions.php
    }
    
    if (!$userId) return false;
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT role_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    // Проверяем, является ли пользователь администратором (role_id = 2)
    return $user && $user['role_id'] == 2; // ADMIN_ROLE_ID будет из config.php
}

function requireAdmin() {
    if (!isAuthenticated()) {
        redirect('/auth/login.php', 'Необходимо войти в систему', 'error');
    }
    
    if (!isAdmin()) {
        redirect('/index.php', 'Доступ запрещен', 'error');
    }
}

function loginUser($userId) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['login_time'] = time();
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
}

function logoutUser() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

function checkLoginAttempts($email) {
    $db = Database::getInstance()->getConnection();
    
    $db->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL " . LOGIN_BLOCK_TIME . " SECOND)");
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE email = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL " . LOGIN_BLOCK_TIME . " SECOND)
    ");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    
    return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
}

function recordLoginAttempt($email, $success = false) {
    $db = Database::getInstance()->getConnection();
    $db->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            success TINYINT(1) DEFAULT 0,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX(email),
            INDEX(attempted_at)
        )
    ");
    
    if (!$success) {
        $stmt = $db->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
    } else {
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE email = ?");
        $stmt->execute([$email]);
    }
}

// Добавляем новые функции для администрирования
function impersonateUser($targetUserId) {
    if (!isAdmin()) {
        return false;
    }
    
    // Сохраняем оригинальный ID администратора
    $_SESSION['original_admin_id'] = $_SESSION['user_id'];
    $_SESSION['user_id'] = $targetUserId;
    
    return true;
}

function stopImpersonation() {
    if (isset($_SESSION['original_admin_id'])) {
        $_SESSION['user_id'] = $_SESSION['original_admin_id'];
        unset($_SESSION['original_admin_id']);
        return true;
    }
    
    return false;
}
?>