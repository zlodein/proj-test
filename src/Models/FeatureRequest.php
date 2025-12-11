<?php
class FeatureRequest {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByUser($userId) {
        $stmt = $this->db->prepare("
            SELECT fr.*, 
                   (SELECT COUNT(*) FROM feature_votes WHERE feature_id = fr.id) as votes,
                   EXISTS(SELECT 1 FROM feature_votes WHERE feature_id = fr.id AND user_id = ?) as user_voted
            FROM feature_requests fr
            WHERE fr.user_id = ?
            ORDER BY fr.created_at DESC
        ");
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }
    
    public function getAll($status = null) {
        $sql = "
            SELECT fr.*, u.name as user_name, u.email,
                   (SELECT COUNT(*) FROM feature_votes WHERE feature_id = fr.id) as votes
            FROM feature_requests fr
            JOIN users u ON fr.user_id = u.id
        ";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE fr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY fr.status = 'new' DESC, votes DESC, fr.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function create($userId, $title, $description = null) {
        $stmt = $this->db->prepare("
            INSERT INTO feature_requests (user_id, title, description) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $description]);
        
        $featureId = $this->db->lastInsertId();
        
        // Автоматически голосуем за свою идею
        $this->vote($userId, $featureId);
        
        return $featureId;
    }
    
    public function vote($userId, $featureId) {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO feature_votes (user_id, feature_id) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$userId, $featureId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateStatus($featureId, $status, $adminNotes = null) {
        $stmt = $this->db->prepare("
            UPDATE feature_requests 
            SET status = ?, 
                is_implemented = ?,
                admin_notes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $isImplemented = $status === 'completed' ? 1 : 0;
        
        return $stmt->execute([
            $status, 
            $isImplemented, 
            $adminNotes, 
            $featureId
        ]);
    }
    
    public function getStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM feature_requests
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        
        // Если таблица пустая, возвращаем нули
        if (!$result) {
            return [
                'total' => 0,
                'new' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'rejected' => 0
            ];
        }
        
        return $result;
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM feature_requests WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
?>