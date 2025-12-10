<?php
class Presentation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $db = Database::getInstance()->getConnection();
        
        $defaultData = [
            'theme_color' => '#2c7f8d',
            'status' => 'draft',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'last_autosave' => date('Y-m-d H:i:s')
        ];
        
        $data = array_merge($defaultData, $data);
        
        if (isset($data['slides_data']) && is_array($data['slides_data'])) {
            $data['slides_data'] = json_encode($data['slides_data'], JSON_UNESCAPED_UNICODE);
        }
        
        $columns = implode('`, `', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO `presentations` (`{$columns}`) VALUES ({$placeholders})";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        
        $presentationId = $db->lastInsertId();
        
        // Увеличиваем счетчик созданных презентаций
        if ($presentationId && isset($data['user_id'])) {
            $tariffModel = new Tariff();
            $tariffModel->incrementPresentationCount($data['user_id']);
            
            // Записываем в аудит
            $this->logPresentationAction($data['user_id'], $presentationId, 'create');
        }
        
        return $presentationId;
    }
    
    public function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        if (!isset($data['last_autosave'])) {
            $data['last_autosave'] = date('Y-m-d H:i:s');
        }
        
        if (isset($data['slides_data']) && is_array($data['slides_data'])) {
            $data['slides_data'] = json_encode($data['slides_data'], JSON_UNESCAPED_UNICODE);
        }
        
        $setParts = [];
        $params = ['id' => $id];
        
        foreach ($data as $key => $value) {
            $setParts[] = "`{$key}` = :{$key}";
            $params[$key] = $value;
        }
        
        $sql = "UPDATE `presentations` SET " . implode(', ', $setParts) . " WHERE `id` = :id";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete($id) {
        $db = Database::getInstance()->getConnection();
        
        // Получаем user_id перед удалением
        $stmt = $db->prepare("SELECT user_id FROM presentations WHERE id = ?");
        $stmt->execute([$id]);
        $presentation = $stmt->fetch();
        
        if (!$presentation) {
            return false;
        }
        
        $userId = $presentation['user_id'];
        
        // Сначала удаляем связанные изображения
        $stmt = $db->prepare("UPDATE images SET is_deleted = 1, deleted_at = NOW() WHERE presentation_id = ?");
        $stmt->execute([$id]);
        
        // Затем удаляем презентацию
        $stmt = $db->prepare("DELETE FROM presentations WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        // Уменьшаем счетчик активных презентаций
        // ВАЖНО: total_created НЕ уменьшается!
        if ($result && $userId) {
            $tariffModel = new Tariff();
            $tariffModel->decrementActivePresentationCount($userId);
            
            // Записываем в аудит
            $this->logPresentationAction($userId, $id, 'delete');
        }
        
        return $result;
    }
    
    /**
     * Запись действий с презентацией в лог аудита
     */
    private function logPresentationAction($userId, $presentationId, $action) {
        try {
            $db = Database::getInstance()->getConnection();
            $tariffModel = new Tariff();
            
            // Получаем текущий тариф и статистику
            $tariff = $tariffModel->getUserTariff($userId);
            $statsBefore = $tariffModel->getUserStats($userId);
            
            // Получаем обновленную статистику после действия
            $statsAfter = $tariffModel->getUserStats($userId);
            
            $stmt = $db->prepare("
                INSERT INTO presentation_audit_log 
                (user_id, presentation_id, action, tariff_name, tariff_limit, 
                 total_created_before, total_created_after, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->execute([
                $userId,
                $presentationId,
                $action,
                $tariff['name'] ?? null,
                $tariff['max_presentations'] ?? null,
                $statsBefore['total_created'] ?? 0,
                $statsAfter['total_created'] ?? 0,
                $ipAddress,
                $userAgent
            ]);
        } catch (Exception $e) {
            // Логирование не должно прерывать основной процесс
            error_log("Failed to log presentation action: " . $e->getMessage());
        }
    }
    
    public function getByUser($userId = null, $limit = 50, $offset = 0, $status = null) {
        $db = Database::getInstance()->getConnection();
        
        $userId = $userId ?? getCurrentUserId();
        $params = [$userId];
        
        $statusCondition = '';
        if ($status) {
            $statusCondition = " AND status = ?";
            $params[] = $status;
        }
        
        // ИСПРАВЛЕННЫЙ ЗАПРОС - убрана ссылка на таблицу slides
        $sql = "SELECT * FROM presentations p 
                WHERE user_id = ? $statusCondition
                ORDER BY updated_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $presentations = $stmt->fetchAll();
        
        // Вычисляем количество слайдов из данных
        foreach ($presentations as &$presentation) {
            if (!empty($presentation['slides_data'])) {
                $data = json_decode($presentation['slides_data'], true);
                $presentation['slides_count'] = count($data['slides'] ?? []);
            } else {
                $presentation['slides_count'] = 0;
            }
        }
        
        return $presentations;
    }
    
    public function countByUser($userId = null, $status = null) {
        $db = Database::getInstance()->getConnection();
        
        $userId = $userId ?? getCurrentUserId();
        $params = [$userId];
        
        $statusCondition = '';
        if ($status) {
            $statusCondition = " AND status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT COUNT(*) as total FROM presentations WHERE user_id = ? $statusCondition";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    public function countAll() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM presentations");
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function getById($id) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, u.name as user_name 
            FROM presentations p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        
        $presentation = $stmt->fetch();
        if ($presentation) {
            $data = json_decode($presentation['slides_data'], true);
            $presentation['slides'] = $data['slides'] ?? [];
        }
        
        return $presentation;
    }
    
    public function getAllWithUsers($limit = 50, $offset = 0) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, u.name as user_name, u.email 
            FROM presentations p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        
        return $stmt->fetchAll();
    }
    
    public function updateStatus($id, $status) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("UPDATE presentations SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }
    
    public function getStats() {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                DATE(created_at) as date
            FROM presentations 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>