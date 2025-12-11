<?php
/**
 * Компонент для отображения аватарки пользователя
 */

/**
 * Получить URL аватарки пользователя
 * 
 * @param array $user Данные пользователя
 * @param int $size Размер аватарки (px)
 * @return string URL аватарки
 */
function getUserAvatar($user, $size = 200) {
    // Если есть загруженная аватарка
    if (!empty($user['user_img'])) {
        // Проверяем, это URL или локальный путь
        if (strpos($user['user_img'], 'http') === 0) {
            // Это URL (например, из OAuth)
            return $user['user_img'];
        } else {
            // Это локальный файл
            return '/uploads/avatars/' . $user['user_img'];
        }
    }
    
    // Генерируем аватар по умолчанию с инициалами
    return generateDefaultAvatar($user, $size);
}

/**
 * Генерировать аватар по умолчанию с инициалами
 * 
 * @param array $user Данные пользователя
 * @param int $size Размер (px)
 * @return string URL аватара
 */
function generateDefaultAvatar($user, $size = 200) {
    $name = $user['name'] ?? 'U';
    $lastName = $user['last_name'] ?? '';
    
    // Получаем инициалы
    $initials = mb_substr($name, 0, 1, 'UTF-8');
    if (!empty($lastName)) {
        $initials .= mb_substr($lastName, 0, 1, 'UTF-8');
    }
    $initials = mb_strtoupper($initials, 'UTF-8');
    
    // Генерируем цвет на основе email
    $email = $user['email'] ?? '';
    $hash = md5($email);
    $hue = hexdec(substr($hash, 0, 2)) % 360;
    
    // Используем UI Avatars API
    $params = [
        'name' => urlencode($initials),
        'size' => $size,
        'background' => sprintf('%02x%02x%02x', 
            (int)(127 + 127 * cos(deg2rad($hue))),
            (int)(127 + 127 * cos(deg2rad($hue + 120))),
            (int)(127 + 127 * cos(deg2rad($hue + 240)))
        ),
        'color' => 'ffffff',
        'bold' => 'true'
    ];
    
    return 'https://ui-avatars.com/api/?' . http_build_query($params);
}

/**
 * Отобразить HTML аватарки
 * 
 * @param array $user Данные пользователя
 * @param int $size Размер (px)
 * @param string $class Дополнительные CSS классы
 * @return string HTML код
 */
function renderUserAvatar($user, $size = 50, $class = '') {
    $avatarUrl = getUserAvatar($user, $size);
    $name = htmlspecialchars($user['name'] ?? 'User');
    $lastName = htmlspecialchars($user['last_name'] ?? '');
    $fullName = trim($name . ' ' . $lastName);
    
    $html = '<img src="' . htmlspecialchars($avatarUrl) . '" ';
    $html .= 'alt="' . $fullName . '" ';
    $html .= 'title="' . $fullName . '" ';
    $html .= 'width="' . $size . '" ';
    $html .= 'height="' . $size . '" ';
    $html .= 'class="user-avatar ' . $class . '" ';
    $html .= 'loading="lazy">';
    
    return $html;
}

/**
 * Получить полное имя пользователя
 * 
 * @param array $user Данные пользователя
 * @return string Полное имя
 */
function getUserFullName($user) {
    $name = $user['name'] ?? '';
    $lastName = $user['last_name'] ?? '';
    return trim($name . ' ' . $lastName) ?: 'User';
}
