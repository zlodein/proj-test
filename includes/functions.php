<?php
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function isMobileDevice() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $mobileAgents = array(
        'Android', 'webOS', 'iPhone', 'iPad', 'iPod', 
        'BlackBerry', 'Windows Phone', 'Opera Mini', 'IEMobile'
    );
    
    foreach ($mobileAgents as $agent) {
        if (stripos($userAgent, $agent) !== false) {
            return true;
        }
    }
    
    return false;
}

function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash'][$type] = $message;
    }
    header("Location: $url");
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function jsonResponse($data, $statusCode = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (APP_ENV === 'production') {
        ini_set('display_errors', 0);
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($token)) return false;
    
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
        unset($_SESSION['csrf_token']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function validateEmail($email) {
    return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 255;
}

function validatePassword($password) {
    return !empty($password) && strlen($password) >= PASSWORD_MIN_LENGTH &&
           preg_match('/[a-zA-Z]/', $password) && preg_match('/[0-9]/', $password);
}

function validateName($name) {
    $name = trim($name);
    return strlen($name) >= 2 && strlen($name) <= 100;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateSecureFilename($originalName) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
}

function validateImage($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    return in_array($mimeType, ALLOWED_MIME_TYPES) && in_array($extension, ALLOWED_EXTENSIONS);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function canAccessPresentation($presentationId) {
    $userId = getCurrentUserId();
    if (!$userId) {
        // Проверяем публичный доступ
        $presentation = getPresentation($presentationId);
        if ($presentation && $presentation['is_public'] == 1) {
            return true;
        }
        return false;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM presentations WHERE id = ? AND user_id = ?");
    $stmt->execute([$presentationId, $userId]);
    
    return $stmt->fetch() !== false;
}

// ИСПРАВЛЕННАЯ ФУНКЦИЯ: Добавлена проверка на null
function getPresentation($presentationId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.name as user_name, u.email, u.tariff_id
        FROM presentations p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$presentationId]);
    
    $presentation = $stmt->fetch();
    if ($presentation) {
        // Проверяем, что slides_data не null перед декодированием
        if ($presentation['slides_data'] !== null && $presentation['slides_data'] !== '') {
            $data = json_decode($presentation['slides_data'], true);
            $presentation['slides'] = $data['slides'] ?? [];
        } else {
            $presentation['slides'] = [];
        }
    }
    
    return $presentation;
}

// ИСПРАВЛЕННАЯ ФУНКЦИЯ: Получение презентации с цветом темы
function getPresentationWithTheme($presentationId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.name as user_name 
        FROM presentations p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$presentationId]);
    
    $presentation = $stmt->fetch();
    if ($presentation) {
        // Проверяем, что slides_data не null перед декодированием
        if ($presentation['slides_data'] !== null && $presentation['slides_data'] !== '') {
            $data = json_decode($presentation['slides_data'], true);
            $presentation['slides'] = $data['slides'] ?? [];
        } else {
            $presentation['slides'] = [];
        }
        
        // Устанавливаем цвет темы по умолчанию если не задан
        if (empty($presentation['theme_color'])) {
            $presentation['theme_color'] = '#2c7f8d';
        }
    }
    
    return $presentation;
}

// НОВАЯ ФУНКЦИЯ: Получение публичной презентации по хэшу
function getPresentationByHash($hash) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT p.*, u.name as user_name 
        FROM presentations p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.public_hash = ? AND p.is_public = 1
    ");
    $stmt->execute([$hash]);
    
    $presentation = $stmt->fetch();
    if ($presentation) {
        if ($presentation['slides_data'] !== null && $presentation['slides_data'] !== '') {
            $data = json_decode($presentation['slides_data'], true);
            $presentation['slides'] = $data['slides'] ?? [];
        } else {
            $presentation['slides'] = [];
        }
        
        if (empty($presentation['theme_color'])) {
            $presentation['theme_color'] = '#2c7f8d';
        }
    }
    
    return $presentation;
}

// НОВАЯ ФУНКЦИЯ: Проверка прав пользователя по тарифу
function getUserTariffPermissions($userId = null) {
    if (!$userId) {
        $userId = getCurrentUserId();
    }
    
    if (!$userId) {
        return [
            'can_print' => false,
            'can_share' => false,
            'can_public_link' => false,
            'max_public_links' => 0,
            'tariff_name' => 'Гость'
        ];
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT t.* 
        FROM tariffs t 
        JOIN users u ON u.tariff_id = t.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $tariff = $stmt->fetch();
    
    if (!$tariff) {
        // Возвращаем бесплатный тариф по умолчанию
        return [
            'can_print' => false,
            'can_share' => false,
            'can_public_link' => false,
            'max_public_links' => 0,
            'tariff_name' => 'Бесплатный'
        ];
    }
    
    return [
        'can_print' => (bool)$tariff['can_print'],
        'can_share' => (bool)$tariff['can_share'],
        'can_public_link' => (bool)$tariff['can_public_link'],
        'max_public_links' => (int)$tariff['max_public_links'],
        'tariff_name' => $tariff['name']
    ];
}

// НОВАЯ ФУНКЦИЯ: Проверка, может ли пользователь печатать
function canUserPrint($userId = null) {
    $permissions = getUserTariffPermissions($userId);
    return $permissions['can_print'];
}

// НОВАЯ ФУНКЦИЯ: Проверка, может ли пользователь делиться
function canUserShare($userId = null) {
    $permissions = getUserTariffPermissions($userId);
    return $permissions['can_share'];
}

// НОВАЯ ФУНКЦИЯ: Проверка, может ли пользователь создавать публичные ссылки
function canUserCreatePublicLink($userId = null) {
    $permissions = getUserTariffPermissions($userId);
    return $permissions['can_public_link'];
}

// НОВАЯ ФУНКЦИЯ: Сколько публичных ссылок осталось у пользователя
function getRemainingPublicLinks($userId = null) {
    if (!$userId) {
        $userId = getCurrentUserId();
    }
    
    $permissions = getUserTariffPermissions($userId);
    $maxLinks = $permissions['max_public_links'];
    
    if ($maxLinks == 0) {
        return 0;
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM presentations WHERE user_id = ? AND is_public = 1");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    $remaining = $maxLinks - $result['count'];
    return max(0, $remaining);
}

// НОВАЯ ФУНКЦИЯ: Создание публичной ссылки
function createPublicLink($presentationId, $userId = null) {
    if (!$userId) {
        $userId = getCurrentUserId();
    }
    
    // Проверяем права
    if (!canUserCreatePublicLink($userId)) {
        return ['error' => 'Ваш тариф не позволяет создавать публичные ссылки'];
    }
    
    // Проверяем лимит
    $remaining = getRemainingPublicLinks($userId);
    if ($remaining <= 0) {
        return ['error' => 'Вы исчерпали лимит публичных ссылок по вашему тарифу'];
    }
    
    // Проверяем доступ к презентации
    if (!canAccessPresentation($presentationId)) {
        return ['error' => 'Доступ к презентации запрещен'];
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Генерируем уникальный хэш
    $hash = bin2hex(random_bytes(16));
    
    // Обновляем презентацию
    $stmt = $db->prepare("
        UPDATE presentations 
        SET public_hash = ?, is_public = 1, public_url = CONCAT('https://presentation-realty.ru/view/', ?)
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$hash, $hash, $presentationId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        return [
            'success' => true,
            'public_url' => 'https://presentation-realty.ru/view/' . $hash,
            'hash' => $hash,
            'remaining_links' => $remaining - 1
        ];
    }
    
    return ['error' => 'Ошибка создания публичной ссылки'];
}

// НОВАЯ ФУНКЦИЯ: Отключение публичной ссылки
function disablePublicLink($presentationId, $userId = null) {
    if (!$userId) {
        $userId = getCurrentUserId();
    }
    
    // Проверяем доступ к презентации
    if (!canAccessPresentation($presentationId)) {
        return ['error' => 'Доступ к презентации запрещен'];
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        UPDATE presentations 
        SET public_hash = NULL, is_public = 0, public_url = NULL
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([$presentationId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        return [
            'success' => true,
            'remaining_links' => getRemainingPublicLinks($userId) + 1
        ];
    }
    
    return ['error' => 'Ошибка отключения публичной ссылки'];
}

function generateRevealHtml($slides, $title, $presentationId, $userId) {
    $slideHtml = '';
    
    foreach ($slides as $index => $slide) {
        switch ($slide['type']) {
            case 'cover':
                $bg = $slide['background'] ? 
                    "data-background='/assets/uploads/{$slide['background']}' data-background-opacity='0.8'" : '';
                
                $slideHtml .= "
                <section {$bg} style='color: white; text-align: center;'>
                    <h1>" . escape($slide['title'] ?? '') . "</h1>
                    <h3>" . escape($slide['subtitle'] ?? '') . "</h3>
                    <div style='margin-top: 2em;'>
                        <span style='padding: 10px 20px; background: rgba(255,255,255,0.3); border-radius: 20px; margin: 0 10px;'>" . 
                        escape($slide['type_label'] ?? '') . "</span>
                        <span style='padding: 10px 20px; background: rgba(255,255,255,0.3); border-radius: 20px; margin: 0 10px;'>" . 
                        escape($slide['price'] ?? '') . "</span>
                    </div>
                </section>";
                break;
                
            case 'gallery':
                $imagesHtml = '';
                foreach (($slide['images'] ?? []) as $img) {
                    $imagesHtml .= "<img src='" . escape($img['url']) . "' style='width: 300px; height: 200px; object-fit: cover; margin: 10px; border-radius: 10px;'>";
                }
                
                $slideHtml .= "
                <section>
                    <h2>" . escape($slide['title'] ?? 'Галерея') . "</h2>
                    <div style='display: flex; flex-wrap: wrap; justify-content: center;'>{$imagesHtml}</div>
                </section>";
                break;
                
            case 'characteristics':
                $itemsHtml = '';
                foreach (($slide['items'] ?? []) as $item) {
                    $itemsHtml .= "<tr><td><strong>" . escape($item['label'] ?? '') . "</strong></td><td>" . 
                                  escape($item['value'] ?? '') . "</td></tr>";
                }
                
                $slideHtml .= "
                <section>
                    <h2>" . escape($slide['title'] ?? 'Характеристики') . "</h2>
                    <table style='margin: 0 auto;'>{$itemsHtml}</table>
                </section>";
                break;
                
            case 'text':
                $slideHtml .= "
                <section>
                    <h2>" . escape($slide['title'] ?? 'Текст') . "</h2>
                    <div style='max-width: 800px; margin: 0 auto;'>" . 
                    nl2br(escape($slide['content'] ?? '')) . "</div>
                </section>";
                break;
        }
    }
    
    return "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='utf-8'>
    <title>" . escape($title) . "</title>
    <link rel='stylesheet' href='" . REVEAL_CDN . "/reveal.min.css'>
    <link rel='stylesheet' href='" . REVEAL_CDN . "/theme/white.min.css'>
    <style>
        .reveal h1, .reveal h2 { text-transform: none; }
        .reveal table { font-size: 0.8em; }
    </style>
</head>
<body>
    <div class='reveal'>
        <div class='slides'>{$slideHtml}</div>
    </div>
    <script src='" . REVEAL_CDN . "/reveal.min.js'></script>
    <script>
        Reveal.initialize({
            hash: true,
            transition: 'slide',
            width: '100%',
            height: '100%'
        });
    </script>
</body>
</html>";
}
?>