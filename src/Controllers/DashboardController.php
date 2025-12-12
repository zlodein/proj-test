<?php
/**
 * Контроллер панели управления
 * Путь: /public_html/src/Controllers/DashboardController.php
 */

class DashboardController {
    private $presentationModel;
    
    public function __construct() {
        $this->presentationModel = new Presentation();
    }
    
    // Создать презентацию
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error' => 'Метод не разрешён'], 405);
        }
        
        $title = trim($_POST['title'] ?? 'Новая презентация');
        $description = trim($_POST['description'] ?? '');
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(['error' => 'Недействительный токен'], 403);
        }
        
        if (empty($title)) {
            jsonResponse(['error' => 'Введите название'], 400);
        }
        
        try {
            $id = $this->presentationModel->create([
                'user_id' => getCurrentUserId(),
                'title' => $title,
                'description' => $description
            ]);
            
            jsonResponse([
                'success' => true,
                'id' => $id,
                'message' => 'Презентация создана'
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['error' => 'Ошибка создания презентации'], 500);
        }
    }
    
    // Получить презентации
    public function getPresentations() {
        try {
            $presentations = $this->presentationModel->getByUser();
            
            jsonResponse([
                'success' => true,
                'presentations' => $presentations
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['error' => 'Ошибка получения презентаций'], 500);
        }
    }
    
    // Удалить презентацию
    public function delete($id) {
        if (!$id || !canAccessPresentation($id)) {
            jsonResponse(['error' => 'Доступ запрещён'], 403);
        }
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(['error' => 'Недействительный токен'], 403);
        }
        
        try {
            $this->presentationModel->delete($id);
            
            jsonResponse([
                'success' => true,
                'message' => 'Презентация удалена'
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['error' => 'Ошибка удаления'], 500);
        }
    }
    
    // Переключить статус публикации
    public function togglePublish($id) {
        if (!$id || !canAccessPresentation($id)) {
            jsonResponse(['error' => 'Доступ запрещён'], 403);
        }
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(['error' => 'Недействительный токен'], 403);
        }
        
        try {
            $presentation = $this->presentationModel->find($id);
            $newStatus = $presentation['status'] === 'published' ? 'draft' : 'published';
            
            $this->presentationModel->update($id, ['status' => $newStatus]);
            
            jsonResponse([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'published' ? 'Опубликовано' : 'Снято с публикации'
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['error' => 'Ошибка обновления'], 500);
        }
    }
    
    // Дублировать презентацию
    public function duplicate($id) {
        if (!$id || !canAccessPresentation($id)) {
            jsonResponse(['error' => 'Доступ запрещён'], 403);
        }
        
        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!verifyCsrfToken($csrfToken)) {
            jsonResponse(['error' => 'Недействительный токен'], 403);
        }
        
        try {
            $newId = $this->presentationModel->duplicate($id, getCurrentUserId());
            
            jsonResponse([
                'success' => true,
                'id' => $newId,
                'message' => 'Презентация скопирована'
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['error' => 'Ошибка копирования'], 500);
        }
    }
}
