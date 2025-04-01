<?php
class Cache {
    private $cache_dir;
    private $max_size = 104857600; // 100MB
    private $compression = true;
    
    public function __construct($cache_dir = null) {
        // استخدام المسار المعرف في config.php إذا لم يتم تحديد مسار
        $this->cache_dir = $cache_dir ?? CACHE_PATH . '/';
        
        // التأكد من وجود / في نهاية المسار
        $this->cache_dir = rtrim($this->cache_dir, '/') . '/';
        
        // التحقق من وجود وصلاحيات المجلد
        if (!is_dir($this->cache_dir)) {
            if (!@mkdir($this->cache_dir, 0755, true)) {
                throw new Exception("فشل في إنشاء مجلد الكاش: " . $this->cache_dir);
            }
        }
        
        if (!is_writable($this->cache_dir)) {
            throw new Exception("مجلد الكاش غير قابل للكتابة: " . $this->cache_dir);
        }
        
        // تنظيف تلقائي عند التهيئة
        $this->cleanup();
    }
    
    public function set($key, $value, $ttl = 3600) {
        try {
            $data = [
                'value' => $value,
                'expires' => time() + $ttl
            ];
            
            $serialized = serialize($data);
            if ($this->compression) {
                $serialized = gzcompress($serialized, 9);
            }
            
            $cache_file = $this->cache_dir . md5($key) . '.cache';
            if (file_put_contents($cache_file, $serialized) === false) {
                throw new Exception("فشل في كتابة ملف الكاش: " . $cache_file);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("خطأ في تخزين الكاش: " . $e->getMessage());
            return false;
        }
    }
    
    public function get($key) {
        try {
            $file = $this->cache_dir . md5($key) . '.cache';
            if (!file_exists($file)) {
                return null;
            }
            
            $data = file_get_contents($file);
            if ($data === false) {
                throw new Exception("فشل في قراءة ملف الكاش: " . $file);
            }
            
            if ($this->compression) {
                $data = gzuncompress($data);
            }
            
            $data = unserialize($data);
            if ($data['expires'] < time()) {
                @unlink($file);
                return null;
            }
            
            return $data['value'];
        } catch (Exception $e) {
            error_log("خطأ في قراءة الكاش: " . $e->getMessage());
            return null;
        }
    }
    
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    public function delete($key) {
        $file = $this->cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    private function cleanup() {
        $total_size = 0;
        $files = [];
        
        // جمع معلومات الملفات
        foreach (glob($this->cache_dir . '*.cache') as $file) {
            $size = filesize($file);
            $total_size += $size;
            $files[] = [
                'path' => $file,
                'size' => $size,
                'time' => filemtime($file)
            ];
        }
        
        // إذا تجاوز الحجم الكلي الحد الأقصى
        if ($total_size > $this->max_size) {
            // ترتيب الملفات حسب وقت التعديل
            usort($files, function($a, $b) {
                return $a['time'] - $b['time'];
            });
            
            // حذف الملفات القديمة حتى يصبح الحجم ضمن الحد المسموح
            foreach ($files as $file) {
                if ($total_size <= $this->max_size) {
                    break;
                }
                unlink($file['path']);
                $total_size -= $file['size'];
            }
        }
    }
    
    public function getStats() {
        $total_size = 0;
        $total_files = 0;
        $expired_files = 0;
        $current_time = time();
        
        foreach (glob($this->cache_dir . '*.cache') as $file) {
            $total_files++;
            $total_size += filesize($file);
            
            $data = file_get_contents($file);
            if ($this->compression) {
                $data = gzuncompress($data);
            }
            $data = unserialize($data);
            
            if ($data['expires'] < $current_time) {
                $expired_files++;
            }
        }
        
        return [
            'total_size' => $total_size,
            'total_files' => $total_files,
            'expired_files' => $expired_files,
            'compression' => $this->compression,
            'max_size' => $this->max_size
        ];
    }
} 