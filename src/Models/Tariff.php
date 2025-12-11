<?php
class Tariff {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM tariffs WHERE is_active = 1 ORDER BY price");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM tariffs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getUserTariff($userId) {
        $stmt = $this->db->prepare("
            SELECT t.* 
            FROM tariffs t 
            JOIN users u ON u.tariff_id = t.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $tariff = $stmt->fetch();
        
        if (!$tariff) {
            return $this->getDefaultTariff();
        }
        
        return $tariff;
    }
    
    /**
     * Получить оставшееся количество презентаций (для отображения пользователю)
     * Показывает разницу между лимитом и текущими активными презентациями
     */
    public function getRemainingPresentations($userId) {
        $tariff = $this->getUserTariff($userId);
        $maxPresentations = $tariff['max_presentations'];
        
        if ($maxPresentations == 0) {
            return '∞';
        }
        
        $stats = $this->getUserStats($userId);
        $remaining = $maxPresentations - $stats['current_active'];
        return max(0, $remaining);
    }
    
    /**
     * Проверить, может ли пользователь создать новую презентацию
     * ВАЖНО: проверяет lifetime лимит (total_created), а не текущие активные
     * Это предотвращает обход через удаление и повторное создание
     */
    public function canCreatePresentation($userId) {
        $tariff = $this->getUserTariff($userId);
        $maxPresentations = $tariff['max_presentations'];
        
        // Безлимитный тариф
        if ($maxPresentations == 0) {
            return true;
        }
        
        $stats = $this->getUserStats($userId);
        
        // Проверяем общее количество созданных презентаций за весь период
        // Это предотвращает обход лимита через удаление
        return $stats['total_created'] < $maxPresentations;
    }
    
    /**
     * Получить статистику презентаций пользователя
     */
    public function getUserStats($userId) {
        $stmt = $this->db->prepare("
            SELECT total_created, current_active, last_reset_date 
            FROM user_presentation_stats 
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $stats = $stmt->fetch();
        
        // Если статистики нет, инициализируем её
        if (!$stats) {
            $this->initUserStats($userId);
            return [
                'total_created' => 0,
                'current_active' => 0,
                'last_reset_date' => null
            ];
        }
        
        return $stats;
    }
    
    /**
     * Инициализировать статистику для пользователя
     */
    private function initUserStats($userId) {
        // Подсчитываем существующие презентации
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM presentations WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $count = $result['count'] ?? 0;
        
        $stmt = $this->db->prepare("
            INSERT INTO user_presentation_stats (user_id, total_created, current_active) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                total_created = VALUES(total_created),
                current_active = VALUES(current_active)
        ");
        $stmt->execute([$userId, $count, $count]);
    }
    
    /**
     * Увеличить счетчик созданных презентаций
     * Вызывается при создании новой презентации
     */
    public function incrementPresentationCount($userId) {
        $stmt = $this->db->prepare("
            INSERT INTO user_presentation_stats (user_id, total_created, current_active)
            VALUES (?, 1, 1)
            ON DUPLICATE KEY UPDATE 
                total_created = total_created + 1,
                current_active = current_active + 1,
                updated_at = NOW()
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Уменьшить счетчик активных презентаций
     * Вызывается при удалении презентации
     * ВАЖНО: total_created НЕ уменьшается!
     */
    public function decrementActivePresentationCount($userId) {
        $stmt = $this->db->prepare("
            UPDATE user_presentation_stats 
            SET current_active = GREATEST(0, current_active - 1),
                updated_at = NOW()
            WHERE user_id = ?
        ");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Сбросить счетчик созданных презентаций
     * Вызывается только при апгрейде тарифа или по запросу администратора
     */
    public function resetPresentationCount($userId, $newLimit = null) {
        $currentActive = 0;
        
        // Получаем текущее количество активных презентаций
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM presentations WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $currentActive = $result['count'] ?? 0;
        
        $stmt = $this->db->prepare("
            UPDATE user_presentation_stats 
            SET total_created = ?,
                current_active = ?,
                last_reset_date = NOW(),
                updated_at = NOW()
            WHERE user_id = ?
        ");
        return $stmt->execute([$currentActive, $currentActive, $userId]);
    }
    
    /**
     * Получить детальную информацию о лимитах пользователя
     */
    public function getUserLimitInfo($userId) {
        $tariff = $this->getUserTariff($userId);
        $stats = $this->getUserStats($userId);
        
        $maxPresentations = $tariff['max_presentations'];
        $isUnlimited = ($maxPresentations == 0);
        
        return [
            'tariff_name' => $tariff['name'],
            'max_presentations' => $isUnlimited ? '∞' : $maxPresentations,
            'total_created' => $stats['total_created'],
            'current_active' => $stats['current_active'],
            'can_create' => $isUnlimited || $stats['total_created'] < $maxPresentations,
            'remaining_lifetime' => $isUnlimited ? '∞' : max(0, $maxPresentations - $stats['total_created']),
            'remaining_slots' => $isUnlimited ? '∞' : max(0, $maxPresentations - $stats['current_active']),
            'is_unlimited' => $isUnlimited,
            'last_reset_date' => $stats['last_reset_date']
        ];
    }
    
    private function getDefaultTariff() {
        $stmt = $this->db->prepare("SELECT * FROM tariffs WHERE price = 0 AND is_active = 1 LIMIT 1");
        $stmt->execute();
        $tariff = $stmt->fetch();
        
        if (!$tariff) {
            // Создаем базовый тариф по умолчанию
            $tariff = [
                'id' => 1,
                'name' => 'Бесплатный',
                'description' => 'Базовые возможности',
                'price' => 0,
                'max_presentations' => 3,
                'duration_days' => 0,
                'features' => '["Базовые шаблоны", "Экспорт в PDF"]'
            ];
        }
        
        return $tariff;
    }
}
?>