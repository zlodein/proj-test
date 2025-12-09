<?php
/**
 * Модель пользователя
 * Путь: /public_html/src/Models/User.php
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Создать пользователя
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (email, password, name, created_at, role_id, tariff_id, is_active) 
            VALUES (:email, :password, :name, NOW(), :role_id, :tariff_id, 1)
        ");
        
        $stmt->execute([
            ':email' => $data['email'],
            ':password' => hashPassword($data['password']),
            ':name' => $data['name'],
            ':role_id' => $data['role_id'] ?? 1,
            ':tariff_id' => $data['tariff_id'] ?? 1
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // Найти по email
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    // Найти по ID
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Проверить существование email
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
    
    // Создать токен сброса пароля
    public function createPasswordResetToken($email) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY);
        
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_reset_token = ?, password_reset_expires = ? 
            WHERE email = ?
        ");
        
        $stmt->execute([$token, $expires, $email]);
        
        return $token;
    }
    
    // Найти по токену сброса
    public function findByResetToken($token) {
        $stmt = $this->db->prepare("
            SELECT * FROM users 
            WHERE password_reset_token = ? 
            AND password_reset_expires > NOW()
            LIMIT 1
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    
    // Обновить пароль
    public function updatePassword($userId, $newPassword) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password = ?, 
                password_reset_token = NULL, 
                password_reset_expires = NULL 
            WHERE id = ?
        ");
        
        return $stmt->execute([
            hashPassword($newPassword),
            $userId
        ]);
    }
    
    // ===== ДОБАВЛЕННЫЕ МЕТОДЫ =====
    
    public function countAll() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function countActive() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM users WHERE is_active = 1");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function getAllWithStats() {
        $stmt = $this->db->prepare("
            SELECT u.*, 
                   COUNT(p.id) as presentation_count,
                   t.name as tariff_name,
                   ur.name as role_name
            FROM users u
            LEFT JOIN presentations p ON u.id = p.user_id
            LEFT JOIN tariffs t ON u.tariff_id = t.id
            LEFT JOIN user_roles ur ON u.role_id = ur.id
            GROUP BY u.id
            ORDER BY u.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['name', 'password'];
        $updates = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields) && $value !== null) {
                $updates[] = "`$field` = ?";
                $params[] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function updateTariff($userId, $tariffId) {
        $stmt = $this->db->prepare("UPDATE users SET tariff_id = ? WHERE id = ?");
        return $stmt->execute([$tariffId, $userId]);
    }
    
    public function updateResetToken($userId, $token, $expires) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_reset_token = ?, 
                password_reset_expires = ? 
            WHERE id = ?
        ");
        return $stmt->execute([$token, $expires, $userId]);
    }
    
    public function clearResetToken($userId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET password_reset_token = NULL, 
                password_reset_expires = NULL 
            WHERE id = ?
        ");
        return $stmt->execute([$userId]);
    }
}
?>