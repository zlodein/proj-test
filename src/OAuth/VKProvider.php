<?php

require_once __DIR__ . '/OAuthProvider.php';

/**
 * ВКонтакте OAuth провайдер
 */
class VKProvider extends OAuthProvider {
    
    public function getAuthUrl() {
        $params = [
            'client_id' => $this->config['client_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'display' => 'page',
            'scope' => $this->config['scope'],
            'response_type' => 'code',
            'v' => $this->config['api_version']
        ];
        
        return $this->config['auth_url'] . '?' . http_build_query($params);
    }
    
    public function getAccessToken($code) {
        $params = [
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'redirect_uri' => $this->config['redirect_uri'],
            'code' => $code
        ];
        
        $response = $this->httpRequest(
            $this->config['token_url'] . '?' . http_build_query($params)
        );
        
        if (!isset($response['access_token'])) {
            throw new Exception('Ошибка получения токена доступа');
        }
        
        return [
            'access_token' => $response['access_token'],
            'user_id' => $response['user_id'] ?? null,
            'email' => $response['email'] ?? null
        ];
    }
    
    public function getUserInfo($accessToken) {
        $params = [
            'user_ids' => $accessToken['user_id'],
            'fields' => 'first_name,last_name',
            'access_token' => $accessToken['access_token'],
            'v' => $this->config['api_version']
        ];
        
        $response = $this->httpRequest(
            $this->config['api_url'] . '?' . http_build_query($params)
        );
        
        if (!isset($response['response'][0])) {
            throw new Exception('Ошибка получения данных пользователя');
        }
        
        $user = $response['response'][0];
        
        return [
            'email' => $accessToken['email'],
            'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
        ];
    }
}
