<?php
/**
 * Компонент для отображения аватарки пользователя
 */

/**
 * Получить URL аватарки пользователя
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
 */
function generateDefaultAvatar($user, $size = 200) {
    $name = trim($user['name'] ?? '');
    $lastName = trim($user['last_name'] ?? '');
    
    // Получаем инициалы
    $initials = '';
    
    if (!empty($name)) {
        // Первая буква имени
        $initials .= mb_substr($name, 0, 1, 'UTF-8');
    }
    
    if (!empty($lastName)) {
        // Первая буква фамилии
        $initials .= mb_substr($lastName, 0, 1, 'UTF-8');
    }
    
    // Если нет ни имени, ни фамилии
    if (empty($initials)) {
        $initials = 'U';
    }
    
    $initials = mb_strtoupper($initials, 'UTF-8');
    
    // Генерируем цвет на основе email
    $email = $user['email'] ?? '';
    $hash = md5($email);
    $hue = hexdec(substr($hash, 0, 2)) % 360;
    
    // Конвертируем HSV в RGB для более красивых цветов
    $saturation = 0.7;
    $value = 0.8;
    
    $c = $value * $saturation;
    $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
    $m = $value - $c;
    
    if ($hue < 60) {
        $r = $c; $g = $x; $b = 0;
    } elseif ($hue < 120) {
        $r = $x; $g = $c; $b = 0;
    } elseif ($hue < 180) {
        $r = 0; $g = $c; $b = $x;
    } elseif ($hue < 240) {
        $r = 0; $g = $x; $b = $c;
    } elseif ($hue < 300) {
        $r = $x; $g = 0; $b = $c;
    } else {
        $r = $c; $g = 0; $b = $x;
    }
    
    $bgColor = sprintf('%02x%02x%02x',
        (int)(($r + $m) * 255),
        (int)(($g + $m) * 255),
        (int)(($b + $m) * 255)
    );
    
    // ИСПРАВЛЕНО: используем rawurlencode вместо urlencode для правильной работы с кириллицей
    $params = [
        'name' => $initials,  // НЕ кодируем здесь, http_build_query сделает это правильно
        'size' => $size,
        'background' => $bgColor,
        'color' => 'ffffff',
        'bold' => 'true'
    ];
    
    // http_build_query автоматически правильно закодирует кириллицу
    return 'https://ui-avatars.com/api/?' . http_build_query($params);
}

/**
 * Отобразить HTML аватарки
 */
function renderUserAvatar($user, $size = 50, $class = '') {
    $avatarUrl = getUserAvatar($user, $size);
    $fullName = getUserFullName($user);
    
    $html = '<img src="' . htmlspecialchars($avatarUrl) . '" ';
    $html .= 'alt="' . htmlspecialchars($fullName) . '" ';
    $html .= 'title="' . htmlspecialchars($fullName) . '" ';
    $html .= 'width="' . $size . '" ';
    $html .= 'height="' . $size . '" ';
    $html .= 'class="user-avatar ' . $class . '" ';
    $html .= 'loading="lazy">';
    
    return $html;
}

/**
 * Получить полное имя пользователя
 */
function getUserFullName($user) {
    $name = trim($user['name'] ?? '');
    $lastName = trim($user['last_name'] ?? '');
    
    if (!empty($name) && !empty($lastName)) {
        return $name . ' ' . $lastName;
    } elseif (!empty($name)) {
        return $name;
    } elseif (!empty($lastName)) {
        return $lastName;
    } else {
        return 'User';
    }
}
