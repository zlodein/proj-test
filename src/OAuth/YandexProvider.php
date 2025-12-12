<?php

require_once __DIR__ . '/BaseOAuthProvider.php';

/**
 * Яндекс OAuth провайдер
 */
class YandexProvider extends BaseOAuthProvider {
    
    public function getAuthUrl() {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri']
        ];
        
        if (isset($this->config['scope'])) {
            $params['scope'] = $this->config['scope'];
        }
        
        return $this->config['auth_url'] . '?' . http_build_query($params);
    }
    
    public function getAccessToken($code) {
        $params = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret']
        ];
        
        $response = $this->httpRequest(
            $this->config['token_url'],
            'POST',
            http_build_query($params),
            ['Content-Type: application/x-www-form-urlencoded']
        );
        
        if (!isset($response['access_token'])) {
            throw new Exception('Ошибка получения токена доступа');
        }
        
        return $response['access_token'];
    }
    
    public function getUserInfo($accessToken) {
        $response = $this->httpRequest(
            $this->config['api_url'] . '?format=json',
            'GET',
            null,
            ['Authorization: OAuth ' . $accessToken]
        );
        
        // Получаем имя и фамилию
        $firstName = $response['first_name'] ?? '';
        $lastName = $response['last_name'] ?? '';
        
        // Если не заполнены, пробуем разделить real_name
        if (empty($firstName) && !empty($response['real_name'])) {
            $nameParts = explode(' ', trim($response['real_name']), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
        }
        
        // Получаем аватар согласно документации
        $avatar = null;
        
        // Проверяем что аватар существует
        if (isset($response['is_avatar_empty']) && $response['is_avatar_empty'] === false) {
            if (isset($response['default_avatar_id']) && !empty($response['default_avatar_id'])) {
                // Формируем URL по документации Яндекса
                // Доступные размеры: islands-small, islands-34, islands-middle, islands-50, islands-retina-small, 
                // islands-68, islands-75, islands-retina-middle, islands-retina-50, islands-200
                $avatar = 'https://avatars.yandex.net/get-yapic/' . $response['default_avatar_id'] . '/islands-200';
            }
        }
        
        return [
            'email' => $response['default_email'] ?? null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => $response['real_name'] ?? $response['display_name'] ?? $response['login'] ?? '',
            'avatar' => $avatar
        ];
    }
}
