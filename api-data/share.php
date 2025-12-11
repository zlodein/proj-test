<?php
// /api-data/share.php - обработка публичных ссылок
function handleShareRequest($action) {
    global $method;
    
    switch ($action) {
        case 'toggle_public_link':
            requireAuth();
            
            if ($method !== 'POST') {
                jsonResponse(['error' => 'Метод не разрешён'], 405);
            }
            
            $presentationId = filter_input(INPUT_POST, 'presentation_id', FILTER_VALIDATE_INT);
            $enable = filter_input(INPUT_POST, 'enable', FILTER_VALIDATE_BOOLEAN);
            $csrfToken = $_POST['csrf_token'] ?? '';
            
            if (!verifyCsrfToken($csrfToken)) {
                jsonResponse(['error' => 'Недействительный токен'], 403);
            }
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            if ($enable) {
                $result = createPublicLink($presentationId);
                if (isset($result['success'])) {
                    jsonResponse([
                        'success' => true,
                        'message' => 'Публичная ссылка создана!',
                        'public_url' => $result['public_url'],
                        'remaining_links' => $result['remaining_links']
                    ]);
                } else {
                    jsonResponse(['error' => $result['error']], 400);
                }
            } else {
                $result = disablePublicLink($presentationId);
                if (isset($result['success'])) {
                    jsonResponse([
                        'success' => true,
                        'message' => 'Публичная ссылка отключена',
                        'remaining_links' => $result['remaining_links']
                    ]);
                } else {
                    jsonResponse(['error' => $result['error']], 400);
                }
            }
            break;
            
        case 'get_public_link_info':
            requireAuth();
            
            $presentationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            $presentation = getPresentation($presentationId);
            
            if (!$presentation) {
                jsonResponse(['error' => 'Презентация не найдена'], 404);
            }
            
            jsonResponse([
                'success' => true,
                'is_public' => (bool)$presentation['is_public'],
                'public_url' => $presentation['public_url'] ?? null,
                'public_hash' => $presentation['public_hash'] ?? null,
                'remaining_links' => getRemainingPublicLinks()
            ]);
            break;
            
        case 'get_public_stats':
            requireAuth();
            
            $presentationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$presentationId || !canAccessPresentation($presentationId)) {
                jsonResponse(['error' => 'Доступ запрещён'], 403);
            }
            
            // В будущем здесь можно добавить статистику просмотров
            jsonResponse([
                'success' => true,
                'views' => 0,
                'message' => 'Статистика будет доступна в будущих обновлениях'
            ]);
            break;
            
        default:
            jsonResponse(['error' => 'Неизвестное действие: ' . $action], 400);
    }
}
?>