<?php
// /api-data/features.php
function handleFeatureRequest($action) {
    switch ($action) {
        case 'add_feature_request':
            handleAddFeatureRequest();
            break;
        case 'vote_feature':
            handleVoteFeature();
            break;
        case 'update_feature_status':
            handleUpdateFeatureStatus();
            break;
        default:
            jsonResponse(['error' => 'Неизвестное действие'], 400);
    }
}

function handleAddFeatureRequest() {
    requireAuth();
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        jsonResponse(['error' => 'Заголовок обязателен'], 400);
    }
    
    $userId = $_SESSION['user_id'];
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        INSERT INTO feature_requests (user_id, title, description, status, created_at, updated_at)
        VALUES (?, ?, ?, 'new', NOW(), NOW())
    ");
    
    $stmt->execute([$userId, $title, $description]);
    $featureId = $db->lastInsertId();
    
    // Автоматически голосуем за свое пожелание
    $stmt = $db->prepare("
        INSERT INTO feature_votes (feature_id, user_id, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$featureId, $userId]);
    
    // Получаем созданное пожелание
    $stmt = $db->prepare("
        SELECT fr.*, COUNT(fv.id) as votes,
               CASE WHEN EXISTS (
                   SELECT 1 FROM feature_votes 
                   WHERE feature_id = fr.id AND user_id = ?
               ) THEN 1 ELSE 0 END as user_voted
        FROM feature_requests fr
        LEFT JOIN feature_votes fv ON fr.id = fv.feature_id
        WHERE fr.id = ?
        GROUP BY fr.id
    ");
    $stmt->execute([$userId, $featureId]);
    $feature = $stmt->fetch();
    
    jsonResponse([
        'success' => true,
        'message' => 'Пожелание успешно добавлено',
        'feature' => $feature
    ]);
}

function handleVoteFeature() {
    requireAuth();
    
    $featureId = $_POST['feature_id'] ?? 0;
    if (!$featureId) {
        jsonResponse(['error' => 'Не указано пожелание'], 400);
    }
    
    $userId = $_SESSION['user_id'];
    
    $db = Database::getInstance()->getConnection();
    
    // Проверяем, не голосовал ли уже пользователь
    $stmt = $db->prepare("
        SELECT 1 FROM feature_votes 
        WHERE feature_id = ? AND user_id = ?
    ");
    $stmt->execute([$featureId, $userId]);
    
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Вы уже голосовали за это пожелание'], 400);
    }
    
    // Добавляем голос
    $stmt = $db->prepare("
        INSERT INTO feature_votes (feature_id, user_id, created_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$featureId, $userId]);
    
    // Получаем обновленное количество голосов
    $stmt = $db->prepare("
        SELECT COUNT(*) as votes FROM feature_votes 
        WHERE feature_id = ?
    ");
    $stmt->execute([$featureId]);
    $votes = $stmt->fetch()['votes'];
    
    jsonResponse([
        'success' => true,
        'votes' => $votes
    ]);
}

function handleUpdateFeatureStatus() {
    requireAuth();
    requireAdmin();
    
    $featureId = $_POST['feature_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    
    if (!$featureId || !in_array($status, ['new', 'in_progress', 'completed', 'rejected'])) {
        jsonResponse(['error' => 'Неверные параметры'], 400);
    }
    
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        UPDATE feature_requests 
        SET status = ?, 
            admin_notes = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$status, $adminNotes, $featureId]);
    
    // Если статус "completed", отмечаем как реализованное
    if ($status === 'completed') {
        $stmt = $db->prepare("
            UPDATE feature_requests 
            SET is_implemented = 1 
            WHERE id = ?
        ");
        $stmt->execute([$featureId]);
    }
    
    jsonResponse(['success' => true]);
}
?>