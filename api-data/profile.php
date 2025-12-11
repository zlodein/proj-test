<?php
function handleProfileRequest($action) {
    if ($action !== 'update_profile') {
        jsonResponse(['error' => 'Неизвестное действие профиля: ' . $action], 400);
    }
    
    requireAuth();
    
    $userId = getCurrentUserId();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? ''); // ДОБАВЛЕНО
    
    // Валидация имени
    if (empty($name)) {
        jsonResponse(['success' => false, 'error' => 'Имя не может быть пустым'], 400);
    }
    
    // Получаем текущие данные пользователя
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Пользователь не найден'], 404);
    }
    
    // Формируем данные для обновления
    $updateData = [
        'name' => $name,
        'last_name' => $lastName // ДОБАВЛЕНО
    ];
    
    // Если указан новый пароль
    if (!empty($newPassword)) {
        // Проверяем текущий пароль для подтверждения изменений
        if (empty($currentPassword)) {
            jsonResponse(['success' => false, 'error' => 'Введите текущий пароль для подтверждения изменений'], 400);
        }
        
        if (!verifyPassword($currentPassword, $user['password'])) {
            jsonResponse(['success' => false, 'error' => 'Неверный текущий пароль'], 400);
        }
        
        // Проверяем совпадение нового пароля
        if ($newPassword !== $confirmPassword) {
            jsonResponse(['success' => false, 'error' => 'Новый пароль и подтверждение не совпадают'], 400);
        }
        
        // Валидация нового пароля
        if (!validatePassword($newPassword)) {
            jsonResponse(['success' => false, 'error' => 'Новый пароль не соответствует требованиям безопасности'], 400);
        }
        
        $updateData['password'] = hashPassword($newPassword);
    } else {
        // Если пароль не меняем, но есть текущий пароль - проверяем его (опционально)
        if (!empty($currentPassword)) {
            if (!verifyPassword($currentPassword, $user['password'])) {
                jsonResponse(['success' => false, 'error' => 'Неверный текущий пароль'], 400);
            }
        }
    }
    
    // Обновляем профиль
    $userModel = new User();
    if ($userModel->updateProfile($userId, $updateData)) {
        jsonResponse(['success' => true, 'message' => 'Профиль успешно обновлен']);
    } else {
        jsonResponse(['success' => false, 'error' => 'Ошибка обновления профиля'], 500);
    }
}
?>
