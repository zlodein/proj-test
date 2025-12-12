<?php
function handleStatsRequest($action) {
    if ($action !== 'get_stats') {
        jsonResponse(['error' => 'Неизвестное действие статистики: ' . $action], 400);
    }
    
    requireAuth();
    
    $db = Database::getInstance()->getConnection();
    $userId = getCurrentUserId();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM presentations WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalPresentations = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM presentations WHERE user_id = ? AND status = 'published'");
    $stmt->execute([$userId]);
    $published = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM presentations WHERE user_id = ? AND status = 'draft'");
    $stmt->execute([$userId]);
    $drafts = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM images WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$userId]);
    $totalImages = $stmt->fetchColumn();
    
    jsonResponse([
        'success' => true,
        'stats' => [
            'total_presentations' => $totalPresentations,
            'published' => $published,
            'drafts' => $drafts,
            'total_images' => $totalImages
        ]
    ]);
}
?>