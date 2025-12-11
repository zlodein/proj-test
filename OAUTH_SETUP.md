# Полная инструкция по настройке OAuth 2.0

## Что было исправлено

✅ **Конфликт имен классов** - переименован `OAuthProvider` в `BaseOAuthProvider`  
✅ **Ошибка `$conn`** - используется `Database::getInstance()->getConnection()`  
✅ **Ошибка `first_name`** - исправлено на `name` согласно вашей схеме БД  
✅ **Добавлены** необходимые поля: `role_id`, `tariff_id`, `is_active`

## Шаг 1: Обновите код на сервере

```bash
cd /home/c/cq88845/presentation-realty/public_html
git fetch origin
git checkout oauth-integration
git pull origin oauth-integration
```

## Шаг 2: Настройте OAuth приложения

### Яндекс OAuth

1. Перейдите на **https://oauth.yandex.ru/**
2. Нажмите **"Создать приложение"**
3. Заполните форму:
   - **Название**: Presentation Realty
   - **Callback URI**: `https://presentation-realty.ru/auth/oauth/callback.php?provider=yandex`
4. В разделе **"Права"** выберите:
   - ☑️ **login:email** - Доступ к email
   - ☑️ **login:info** - Доступ к имени и фамилии
5. Скопируйте:
   - **ClientID**
   - **Client secret**

### ВКонтакте OAuth

1. Перейдите на **https://vk.com/apps?act=manage**
2. Нажмите **"Создать"**
3. Выберите тип: **"Standalone-приложение"**
4. В настройках:
   - **Доверенный redirect URI**: `https://presentation-realty.ru/auth/oauth/callback.php?provider=vk`
5. Включите доступ к **Email**
6. Скопируйте:
   - **ID приложения**
   - **Защищённый ключ**

### Mail.ru OAuth

1. Перейдите на **https://o2.mail.ru/app/**
2. Нажмите **"Создать приложение"**
3. Заполните:
   - **Redirect URI**: `https://presentation-realty.ru/auth/oauth/callback.php?provider=mailru`
4. В правах выберите: **Доступ к email**
5. Скопируйте:
   - **Client ID**
   - **Client Secret**

## Шаг 3: Обновите конфигурацию

Откройте файл `/home/c/cq88845/presentation-realty/public_html/auth/oauth/config.php`:

```php
<?php
define('SITE_URL', 'https://presentation-realty.ru');

return [
    'yandex' => [
        'client_id' => 'ВАШ_YANDEX_CLIENT_ID',  // Замените на реальный
        'client_secret' => 'ВАШ_YANDEX_CLIENT_SECRET',  // Замените на реальный
        'redirect_uri' => SITE_URL . '/auth/oauth/callback.php?provider=yandex',
        'auth_url' => 'https://oauth.yandex.ru/authorize',
        'token_url' => 'https://oauth.yandex.ru/token',
        'api_url' => 'https://login.yandex.ru/info',
        'scope' => 'login:email login:info'
    ],
    'vk' => [
        'client_id' => 'ВАШ_VK_APP_ID',  // Замените на реальный
        'client_secret' => 'ВАШ_VK_SECRET_KEY',  // Замените на реальный
        'redirect_uri' => SITE_URL . '/auth/oauth/callback.php?provider=vk',
        'auth_url' => 'https://oauth.vk.com/authorize',
        'token_url' => 'https://oauth.vk.com/access_token',
        'api_url' => 'https://api.vk.com/method/users.get',
        'api_version' => '5.131',
        'scope' => 'email'
    ],
    'mailru' => [
        'client_id' => 'ВАШ_MAILRU_CLIENT_ID',  // Замените на реальный
        'client_secret' => 'ВАШ_MAILRU_CLIENT_SECRET',  // Замените на реальный
        'redirect_uri' => SITE_URL . '/auth/oauth/callback.php?provider=mailru',
        'auth_url' => 'https://oauth.mail.ru/login',
        'token_url' => 'https://oauth.mail.ru/token',
        'api_url' => 'https://oauth.mail.ru/userinfo',
        'scope' => 'userinfo'
    ]
];
```

## Шаг 4: Проверьте работу

1. Откройте **https://presentation-realty.ru/auth/login.php**
2. Вы должны увидеть три кнопки OAuth под формой
3. Нажмите на кнопку "Яндекс"
4. Разрешите доступ к вашим данным
5. Вы должны быть автоматически авторизованы!

## Структура файлов

```
auth/
├── login.php                    # Обновлённая форма с OAuth кнопками
└── oauth/
    ├── config.php              # Конфигурация OAuth (ЗАПОЛНИТЕ!)
    └── callback.php            # Callback обработчик

src/
└── OAuth/
    ├── BaseOAuthProvider.php   # Базовый класс
    ├── YandexProvider.php      # Яндекс провайдер
    ├── VKProvider.php          # VK провайдер
    └── MailRuProvider.php      # Mail.ru провайдер
```

## Что происходит при OAuth авторизации

1. Пользователь нажимает кнопку "Яндекс"
2. Перенаправляется на Яндекс для подтверждения
3. После подтверждения возвращается на `callback.php`
4. `callback.php` обменивает код на токен
5. Получает email и имя пользователя
6. Ищет пользователя по email:
   - Если есть - авторизует
   - Если нет - создаёт нового с ролью `user` и бесплатным тарифом
7. Перенаправляет на главную страницу

## Отладка

Если что-то не работает, проверьте логи:

```bash
tail -f /home/c/cq88845/presentation-realty/logs/error.log
```

Или стандартные логи Apache/PHP.

## Безопасность

✅ HTTPS используется для всех OAuth запросов  
✅ Email автоматически верифицирован  
✅ Случайный пароль генерируется для OAuth пользователей  
✅ Проверка активности аккаунта (`is_active`)  

## Важно!

⚠️ **Не забудьте** заменить `YOUR_*_CLIENT_ID` и `YOUR_*_CLIENT_SECRET` в `config.php` на реальные значения!

⚠️ **Callback URL** должен точно совпадать в настройках приложения и в `config.php`!

⚠️ **Используйте HTTPS** в продакшене (у вас уже есть SSL сертификат)
