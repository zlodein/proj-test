<?php
/**
 * Базовый класс для OAuth провайдеров
 */
abstract class BaseOAuthProvider {
    protected $config;
    protected $db;
    
    public function __construct($config, $db) {
        $this->config = $config;
        $this->db = $db;
    }
    
    /**
     * Получить URL для авторизации
     */
    abstract public function getAuthUrl();
    
    /**
     * Обменять код на токен доступа
     */
    abstract public function getAccessToken($code);
    
    /**
     * Получить информацию о пользователе
     */
    abstract public function getUserInfo($accessToken);
    
    /**
     * Выполнить HTTP запрос
     */
    protected function httpRequest($url, $method = 'GET', $data = null, $headers = []) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("CURL Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode, Response: $response");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Обработать пользователя (создать или обновить)
     */
    public function processUser($userInfo) {
        $email = $userInfo['email'];
        $name = $userInfo['name'];
        
        if (!$email) {
            throw new Exception('Не удалось получить email пользователя');
        }
        
        // Проверяем существует ли пользователь
        $stmt = $this->db->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Пользователь существует
            return $user['id'];
        } else {
            // Создаем нового пользователя
            $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (email, first_name, password, email_verified, created_at, updated_at) 
                VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([$email, $name, $randomPassword]);
            
            return $this->db->lastInsertId();
        }
    }
}
