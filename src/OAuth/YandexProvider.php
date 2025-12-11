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
        
        return [
            'email' => $response['default_email'] ?? null,
            'name' => $response['real_name'] ?? $response['display_name'] ?? $response['login'] ?? 'User'
        ];
    }
}
