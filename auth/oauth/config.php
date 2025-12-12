<?php
/**
 * OAuth 2.0 Configuration
 * 
 * Для каждого провайдера необходимо зарегистрировать приложение:
 * 
 * Яндекс: https://oauth.yandex.ru/
 * VK: https://vk.com/apps?act=manage
 * Mail.ru: https://o2.mail.ru/app/
 */

// Измените на ваш домен
define('SITE_URL', 'https://presentation-realty.ru');

return [
    'yandex' => [
        'client_id' => 'aca999dfc8634f23896998bcf3ef4477',
        'client_secret' => '456083f4c8ca43aaa7c253b83df7d3b2',
        'redirect_uri' => SITE_URL . '/auth/oauth/callback.php?provider=yandex',
        'auth_url' => 'https://oauth.yandex.ru/authorize',
        'token_url' => 'https://oauth.yandex.ru/token',
        'api_url' => 'https://login.yandex.ru/info',
        'scope' => 'login:email login:info login:avatar'
    ],
    'vk' => [
        'client_id' => 'YOUR_VK_CLIENT_ID',
        'client_secret' => 'YOUR_VK_CLIENT_SECRET',
        'redirect_uri' => SITE_URL . '/auth/oauth/callback.php?provider=vk',
        'auth_url' => 'https://oauth.vk.com/authorize',
        'token_url' => 'https://oauth.vk.com/access_token',
        'api_url' => 'https://api.vk.com/method/users.get',
        'api_version' => '5.131',
        'scope' => 'email'
    ],
    'mailru' => [
        'client_id' => 'YOUR_MAILRU_CLIENT_ID',
        'client_secret' => 'YOUR_MAILRU_CLIENT_SECRET',
        'redirect_uri' => SITE_URL . '/auth/oauth/callback.php?provider=mailru',
        'auth_url' => 'https://oauth.mail.ru/login',
        'token_url' => 'https://oauth.mail.ru/token',
        'api_url' => 'https://oauth.mail.ru/userinfo',
        'scope' => 'userinfo'
    ]
];