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
        $stmt = $this->db->prepare("SELECT id, email, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Пользователь существует
            // Проверяем активность
            if (!$user['is_active']) {
                throw new Exception('Аккаунт заблокирован');
            }
            return $user['id'];
        } else {
            // Создаем нового пользователя
            // Генерируем случайный пароль (пользователь не сможет им воспользоваться)
            $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            
            // Получаем ID роли по умолчанию (user)
            $roleId = 1; // ID роли по умолчанию
            $tariffId = 1; // ID тарифа по умолчанию
            
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, name, role_id, tariff_id, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$email, $randomPassword, $name, $roleId, $tariffId]);
            
            return $this->db->lastInsertId();
        }
    }
}
