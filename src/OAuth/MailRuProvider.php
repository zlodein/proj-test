<?php

require_once __DIR__ . '/BaseOAuthProvider.php';

/**
 * Mail.ru OAuth провайдер
 */
class MailRuProvider extends BaseOAuthProvider {
    
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $this->config['redirect_uri'],
            'scope' => $this->config['scope']
        ];
        
        return $this->config['auth_url'] . '?' . http_build_query($params);
    }
    
    public function getAccessToken($code) {
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->config['redirect_uri']
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
            $this->config['api_url'],
            'GET',
            null,
            ['Authorization: Bearer ' . $accessToken]
        );
        
        return [
            'email' => $response['email'] ?? null,
            'name' => $response['name'] ?? 
                     trim(($response['first_name'] ?? '') . ' ' . ($response['last_name'] ?? '')) ?:
                     'User'
        ];
    }
}
