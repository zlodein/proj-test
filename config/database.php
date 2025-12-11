<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (APP_ENV === 'development') {
                die('Ошибка подключения к БД: ' . $e->getMessage());
            } else {
                die('Ошибка подключения к базе данных');
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function insert($table, $data) {
        $columns = implode('`, `', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$placeholders})";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    public function update($table, $data, $where) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "`{$key}` = :set_{$key}";
            $params[":set_{$key}"] = $value;
        }
        
        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "`{$key}` = :w_{$key}";
            $params[":w_{$key}"] = $value;
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . 
               " WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function delete($table, $where) {
        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "`{$key}` = :{$key}";
        }
        
        $sql = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $whereParts);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($where);
        return $stmt->rowCount();
    }

    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
