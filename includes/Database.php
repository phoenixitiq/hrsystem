<?php
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

class Database {
    private static $instance = null;
    private $connection;
    private $cache;
    private $cacheEnabled = true;

    private function __construct() {
        try {
            // التحقق من وجود المتغيرات المطلوبة
            if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
                throw new Exception('متغيرات قاعدة البيانات غير معرفة');
            }

            // إنشاء اتصال PDO
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // تهيئة التخزين المؤقت
            if (class_exists('Cache')) {
                $this->cache = new Cache();
            }
        } catch (PDOException $e) {
            logError("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage(), 'ERROR');
            throw new Exception("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
        } catch (Exception $e) {
            logError($e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function query($sql, $params = [], $cacheKey = null, $cacheTime = 300) {
        try {
            // التحقق من التخزين المؤقت
            if ($this->cacheEnabled && $cacheKey) {
                try {
                    $cachedResult = $this->cache->get($cacheKey);
                    if ($cachedResult !== false) {
                        return $cachedResult;
                    }
                } catch (Exception $e) {
                    error_log("خطأ في التخزين المؤقت: " . $e->getMessage());
                    $this->cacheEnabled = false;
                }
            }

            // التحقق من صحة البيانات
            if (!is_array($params)) {
                throw new Exception('معاملات الاستعلام يجب أن تكون مصفوفة');
            }

            // تنفيذ الاستعلام
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("خطأ في تنفيذ الاستعلام: " . $e->getMessage());
            throw new Exception("فشل في تنفيذ الاستعلام");
        }
    }

    public function fetchAll($sql, $params = [], $cacheKey = null, $cacheTime = 300) {
        try {
            // التحقق من التخزين المؤقت
            if ($this->cacheEnabled && $cacheKey) {
                $cachedResult = $this->cache->get($cacheKey);
                if ($cachedResult !== false) {
                    return $cachedResult;
                }
            }

            // تنفيذ الاستعلام
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();

            // تخزين النتيجة مؤقتاً
            if ($this->cacheEnabled && $cacheKey) {
                $this->cache->set($cacheKey, $result, $cacheTime);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("خطأ في تنفيذ الاستعلام: " . $e->getMessage());
            throw new Exception("فشل في تنفيذ الاستعلام");
        }
    }

    public function fetch($sql, $params = [], $cacheKey = null, $cacheTime = 300) {
        try {
            // التحقق من التخزين المؤقت
            if ($this->cacheEnabled && $cacheKey) {
                $cachedResult = $this->cache->get($cacheKey);
                if ($cachedResult !== false) {
                    return $cachedResult;
                }
            }

            // تنفيذ الاستعلام
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            // تخزين النتيجة مؤقتاً
            if ($this->cacheEnabled && $cacheKey) {
                $this->cache->set($cacheKey, $result, $cacheTime);
            }

            return $result;
        } catch (PDOException $e) {
            error_log("خطأ في تنفيذ الاستعلام: " . $e->getMessage());
            throw new Exception("فشل في تنفيذ الاستعلام");
        }
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) {
            return "{$field} = ?";
        }, array_keys($data));
        
        $sql = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        return $this->query($sql, $params)->rowCount();
    }

    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction() {
        try {
            return $this->connection->beginTransaction();
        } catch (PDOException $e) {
            error_log("خطأ في بدء المعاملة: " . $e->getMessage());
            throw new Exception("فشل في بدء المعاملة");
        }
    }

    public function commit() {
        try {
            return $this->connection->commit();
        } catch (PDOException $e) {
            error_log("خطأ في حفظ المعاملة: " . $e->getMessage());
            throw new Exception("فشل في حفظ المعاملة");
        }
    }

    public function rollBack() {
        try {
            return $this->connection->rollBack();
        } catch (PDOException $e) {
            error_log("خطأ في التراجع عن المعاملة: " . $e->getMessage());
            throw new Exception("فشل في التراجع عن المعاملة");
        }
    }

    public function clearCache($key = null) {
        if ($key) {
            $this->cache->delete($key);
        } else {
            $this->cache->clear();
        }
    }

    public function disableCache() {
        $this->cacheEnabled = false;
    }

    public function enableCache() {
        $this->cacheEnabled = true;
    }

    /**
     * إغلاق الاتصال بقاعدة البيانات
     */
    public function close() {
        if ($this->connection) {
            $this->connection = null;
        }
    }
} 