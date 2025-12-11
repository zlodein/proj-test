<?php
function handleProfileRequest($action) {
    if ($action !== 'update_profile') {
        jsonResponse(['error' => 'Неизвестное действие профиля: ' . $action], 400);
    }
    
    requireAuth();
    
    $userId = getCurrentUserId();
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $name = $_POST['name'] ?? '';
    
    // Проверяем текущий пароль для подтверждения изменений
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!verifyPassword($currentPassword, $user['password'])) {
        jsonResponse(['success' => false, 'error' => 'Неверный текущий пароль']);
    }
    
    $updateData = ['name' => $name];
    
    // Если указан новый пароль
    if (!empty($newPassword)) {
        if (!validatePassword($newPassword)) {
            jsonResponse(['success' => false, 'error' => 'Новый пароль не соответствует требованиям безопасности']);
        }
        
        $updateData['password'] = hashPassword($newPassword);
    }
    
    $userModel = new User();
    if ($userModel->updateProfile($userId, $updateData)) {
        jsonResponse(['success' => true, 'message' => 'Профиль успешно обновлен']);
    } else {
        jsonResponse(['success' => false, 'error' => 'Ошибка обновления профиля']);
    }
}
?>