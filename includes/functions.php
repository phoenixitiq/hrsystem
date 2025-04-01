<?php
/**
 * Core functions for HR System
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once 'Database.php';

/**
 * تنسيق حالة الموظف
 */
function formatEmployeeStatus($status) {
    $statuses = [
        'active' => 'نشط',
        'inactive' => 'غير نشط',
        'terminated' => 'منتهي',
        'on_leave' => 'في إجازة'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * التحقق من صحة رقم الهوية الوطنية
 */
function validateNationalId($id) {
    return preg_match('/^[0-9]{12}$/', $id);
}

/**
 * التحقق من صحة رقم الهاتف العراقي
 */
function validateIraqiPhone($phone) {
    return preg_match('/^(07[3-9]|075)\d{8}$/', $phone);
}

/**
 * التحقق من صحة البريد الإلكتروني
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من صحة المبلغ
 */
function validateAmount($amount) {
    return is_numeric($amount) && $amount > 0;
}

/**
 * تنسيق المبلغ بالعملة العراقية
 */
function formatCurrency($amount, $decimals = 2) {
    return number_format($amount, $decimals);
}

/**
 * تحويل التاريخ الهجري إلى ميلادي باستخدام مكتبة IntlDateFormatter
 */
function hijriToGregorian($hijri_date) {
    try {
        $formatter = new IntlDateFormatter(
            'ar_IQ@calendar=islamic',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            'Asia/Baghdad',
            IntlDateFormatter::TRADITIONAL
        );
        
        $timestamp = $formatter->parse($hijri_date);
        if ($timestamp === false) {
            return false;
        }
        
        return date('Y-m-d', $timestamp);
    } catch (Exception $e) {
        error_log("خطأ في تحويل التاريخ الهجري: " . $e->getMessage());
        return false;
    }
}

/**
 * تنسيق التاريخ بالشكل العربي
 */
function formatArabicDate($date) {
    $months = [
        'January' => 'يناير',
        'February' => 'فبراير',
        'March' => 'مارس',
        'April' => 'أبريل',
        'May' => 'مايو',
        'June' => 'يونيو',
        'July' => 'يوليو',
        'August' => 'أغسطس',
        'September' => 'سبتمبر',
        'October' => 'أكتوبر',
        'November' => 'نوفمبر',
        'December' => 'ديسمبر'
    ];
    
    $date = new DateTime($date);
    $month = $months[$date->format('F')];
    return $date->format('d') . ' ' . $month . ' ' . $date->format('Y');
}

/**
 * دالة التحقق من تسجيل الدخول
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * دالة التحقق من الصلاحيات
 */
function hasPermission($permission) {
    if (!isset($_SESSION['user_permissions'])) {
        return false;
    }
    return in_array($permission, $_SESSION['user_permissions']);
}

/**
 * دالة تنسيق التاريخ
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * دالة تنسيق الوقت
 */
function formatTime($time, $format = 'H:i') {
    if (empty($time)) {
        return '';
    }
    return date($format, strtotime($time));
}

/**
 * دالة إنشاء رابط آمن
 */
function createLink($path, $params = []) {
    $url = APP_URL . '/' . ltrim($path, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * دالة إعادة التوجيه
 */
function redirect($path, $params = []) {
    $url = createLink($path, $params);
    header("Location: $url");
    exit();
}

/**
 * دالة التحقق من صحة البريد الإلكتروني
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * دالة إنشاء كلمة مرور عشوائية
 */
function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * دالة تشفير كلمة المرور
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * دالة التحقق من كلمة المرور
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * دالة إنشاء رمز CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * دالة التحقق من رمز CSRF
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * دالة إنشاء رسالة تنبيه
 */
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * دالة عرض رسالة التنبيه
 */
function showAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return "<div class='alert alert-{$alert['type']}'>{$alert['message']}</div>";
    }
    return '';
}

/**
 * دالة تحميل الملف
 */
function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('معاملات الملف غير صالحة');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('لم يتم تحديد ملف');
        case UPLOAD_ERR_INI_SIZE:
            throw new Exception('حجم الملف يتجاوز الحد المسموح به في php.ini');
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('حجم الملف يتجاوز الحد المسموح به في النموذج');
        case UPLOAD_ERR_PARTIAL:
            throw new Exception('تم رفع الملف جزئياً فقط');
        case UPLOAD_ERR_NO_TMP_DIR:
            throw new Exception('مجلد الملفات المؤقتة مفقود');
        case UPLOAD_ERR_CANT_WRITE:
            throw new Exception('فشل في كتابة الملف على القرص');
        case UPLOAD_ERR_EXTENSION:
            throw new Exception('تم إيقاف رفع الملف من قبل PHP');
        default:
            throw new Exception('خطأ غير معروف');
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('حجم الملف يتجاوز الحد المسموح به');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, ALLOWED_FILE_TYPES)) {
        throw new Exception('نوع الملف غير مسموح به');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('امتداد الملف غير مسموح به');
    }

    $filename = sprintf(
        '%s.%s',
        sha1_file($file['tmp_name']),
        $extension
    );

    if (!move_uploaded_file($file['tmp_name'], $destination . '/' . $filename)) {
        throw new Exception('فشل في نقل الملف');
    }

    return $filename;
}

/**
 * دالة لإرسال البريد الإلكتروني
 */
function sendEmail($to, $subject, $message) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . APP_NAME . ' <' . SMTP_FROM . '>',
        'Reply-To: ' . SMTP_FROM,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

/**
 * دالة تسجيل النشاط
 */
function logActivity($user_id, $action, $description) {
    try {
        $db = Database::getInstance();
        $db->insert('activity_logs', [
            'user_id' => $user_id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        error_log("خطأ في تسجيل النشاط: " . $e->getMessage());
    }
}

/**
 * دالة تسجيل الأحداث
 */
function logEvent($message, $level = 'info') {
    $logFile = LOG_PATH . '/events.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    error_log($logMessage, 3, $logFile);
}

/**
 * دالة للحصول على إحصائيات النظام
 */
function getSystemStats() {
    try {
        $db = Database::getInstance();
        
        $stats = [
            'employees' => $db->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC),
            'loans' => $db->query("SELECT COUNT(*) as count FROM loans WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC),
            'debts' => $db->query("SELECT COUNT(*) as count FROM debts WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC),
            'salaries' => $db->query("SELECT COUNT(*) as count FROM salaries WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)
        ];
        
        return $stats;
    } catch (Exception $e) {
        error_log("خطأ في جلب إحصائيات النظام: " . $e->getMessage());
        return [];
    }
}

/**
 * تنسيق حجم الملفات
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Input sanitization
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Response handling
function sendJsonResponse($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Validation functions
function validateRequired($value) {
    return !empty(trim($value));
}

function validateNumeric($value) {
    return is_numeric($value);
}

/**
 * تنفيذ استعلام SQL مع إعادة المحاولة في حالة الفشل
 */
function executeQueryWithRetry($query, $params = [], $cacheKey = null, $maxRetries = 3) {
    $db = Database::getInstance();
    $attempts = 0;
    
    while ($attempts < $maxRetries) {
        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // تخزين النتيجة في الكاش إذا تم تحديد مفتاح الكاش
            if ($cacheKey) {
                $cache = new Cache();
                $cache->set($cacheKey, $result, 300); // تخزين لمدة 5 دقائق
            }
            
            return $result;
        } catch (PDOException $e) {
            $attempts++;
            if ($attempts >= $maxRetries) {
                error_log("فشل تنفيذ الاستعلام بعد {$maxRetries} محاولات: " . $e->getMessage());
                throw $e;
            }
            sleep(1); // انتظار ثانية واحدة قبل إعادة المحاولة
        }
    }
}

/**
 * إنشاء حقل CSRF مخفي
 */
function getCsrfInput() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}

/**
 * التحقق من صلاحيات المستخدم
 * @param string $permission اسم الصلاحية المطلوبة
 * @return bool
 */
function checkPermission($permission) {
    // التحقق من تسجيل دخول المستخدم
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // الحصول على صلاحيات المستخدم
    $db = Database::getInstance();
    $sql = "SELECT p.permission_name 
            FROM user_permissions up 
            JOIN permissions p ON up.permission_id = p.id 
            WHERE up.user_id = ?";
    
    $permissions = $db->fetchAll($sql, [$_SESSION['user_id']]);
    
    // تحويل الصلاحيات إلى مصفوفة بسيطة
    $userPermissions = array_column($permissions, 'permission_name');
    
    // التحقق من وجود الصلاحية المطلوبة
    return in_array($permission, $userPermissions);
}

/**
 * الحصول على إعدادات النظام
 * @param string $key مفتاح الإعداد
 * @param mixed $default القيمة الافتراضية في حالة عدم وجود الإعداد
 * @return mixed
 */
function getSetting($key, $default = null) {
    static $settings = null;
    
    // تحميل الإعدادات مرة واحدة فقط
    if ($settings === null) {
        try {
            $db = Database::getInstance();
            $sql = "SELECT setting_key, setting_value FROM settings";
            $results = $db->fetchAll($sql);
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            error_log("خطأ في تحميل إعدادات النظام: " . $e->getMessage());
            $settings = [];
        }
    }
    
    return $settings[$key] ?? $default;
}

// التحقق من قوة كلمة المرور
function validatePassword($password) {
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return false;
    }
    return true;
}

// تنظيف اسم الملف
function sanitizeFileName($fileName) {
    $fileName = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $fileName);
    return strtolower($fileName);
}

// التحقق من صحة التاريخ
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// التحقق من صحة الرقم
function validateNumber($number) {
    return is_numeric($number) && $number >= 0;
}

// تنسيق الرقم
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, '.', ',');
}

// التحقق من صحة النسبة المئوية
function validatePercentage($percentage) {
    return is_numeric($percentage) && $percentage >= 0 && $percentage <= 100;
}

// تنسيق النسبة المئوية
function formatPercentage($percentage, $decimals = 2) {
    return number_format($percentage, $decimals) . '%';
}

// إنشاء نص عشوائي
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// إنشاء رمز QR
function generateQRCode($data, $size = 200) {
    $url = 'https://api.qrserver.com/v1/create-qr-code/';
    $params = [
        'size' => $size . 'x' . $size,
        'data' => urlencode($data)
    ];
    return $url . '?' . http_build_query($params);
}

/**
 * إنشاء ملف Word
 * @param string $html محتوى HTML
 * @param string $filename اسم الملف
 * @return bool
 */
function generateWord($html, $filename) {
    try {
        // تحميل ملف autoload.php
        $vendorPath = __DIR__ . '/../vendor/autoload.php';
        if (!file_exists($vendorPath)) {
            throw new Exception('ملف vendor/autoload.php غير موجود. يرجى تشغيل composer install');
        }
        require_once $vendorPath;

        // التحقق من وجود مكتبة PhpWord
        if (!class_exists('PhpOffice\PhpWord\PhpWord')) {
            throw new Exception('مكتبة PhpWord غير مثبتة. يرجى تثبيتها باستخدام Composer: composer require phpoffice/phpword');
        }

        // إنشاء كائن PhpWord
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // إضافة قسم جديد
        $section = $phpWord->addSection();
        
        // إضافة النص
        $section->addText(strip_tags($html));
        
        // حفظ الملف
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($filename);
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إنشاء ملف Word: " . $e->getMessage());
        return false;
    }
}

// إنشاء ملف ZIP
function generateZIP($files, $filename) {
    $zip = new \ZipArchive();
    $zipFile = tempnam(sys_get_temp_dir(), 'zip');
    
    if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, basename($file));
            }
        }
        $zip->close();
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        unlink($zipFile);
        exit;
    }
    return false;
}

// إنشاء نسخة احتياطية
function createBackup() {
    $backupDir = BACKUP_PATH . '/' . date('Y-m-d');
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $filename = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
    $command = sprintf(
        'mysqldump -h %s -u %s -p%s %s > %s',
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $filename
    );
    
    exec($command, $output, $returnVar);
    return $returnVar === 0;
}

// استعادة نسخة احتياطية
function restoreBackup($filename) {
    if (!file_exists($filename)) {
        return false;
    }
    
    $command = sprintf(
        'mysql -h %s -u %s -p%s %s < %s',
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        $filename
    );
    
    exec($command, $output, $returnVar);
    return $returnVar === 0;
}

// تنظيف الملفات المؤقتة
function cleanTempFiles() {
    $tempDir = sys_get_temp_dir();
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && time() - filemtime($file) > 86400) {
            unlink($file);
        }
    }
}

// تنظيف سجلات النشاط
function cleanActivityLogs() {
    try {
        $db = Database::getInstance();
        $db->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    } catch (Exception $e) {
        error_log("خطأ في تنظيف سجلات النشاط: " . $e->getMessage());
    }
}

// تنظيف سجلات الأخطاء
function cleanErrorLogs() {
    $logFile = LOG_PATH . '/error.log';
    if (file_exists($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
        rename($logFile, $logFile . '.' . date('Y-m-d'));
    }
}

// تنظيف النسخ الاحتياطية
function cleanBackups() {
    $backupDir = BACKUP_PATH;
    $files = glob($backupDir . '/*.sql');
    foreach ($files as $file) {
        if (time() - filemtime($file) > 30 * 24 * 60 * 60) {
            unlink($file);
        }
    }
}

// تنظيف الملفات المرفوعة
function cleanUploads() {
    $uploadDir = UPLOAD_PATH;
    $files = glob($uploadDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && time() - filemtime($file) > 30 * 24 * 60 * 60) {
            unlink($file);
        }
    }
}

// تنظيف ملفات التصدير
function cleanExports() {
    $exportDir = EXPORT_PATH;
    $files = glob($exportDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && time() - filemtime($file) > 7 * 24 * 60 * 60) {
            unlink($file);
        }
    }
}

// تنظيف النظام
function cleanSystem() {
    cleanTempFiles();
    cleanActivityLogs();
    cleanErrorLogs();
    cleanBackups();
    cleanUploads();
    cleanExports();
}

// التحقق من التحديثات
function checkUpdates() {
    $currentVersion = APP_VERSION;
    $updateUrl = APP_UPDATE_URL;
    
    try {
        $ch = curl_init($updateUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        if ($data && isset($data['version'])) {
            return version_compare($data['version'], $currentVersion, '>');
        }
    } catch (Exception $e) {
        error_log("خطأ في التحقق من التحديثات: " . $e->getMessage());
    }
    return false;
}

// تحديث النظام
function updateSystem($version) {
    try {
        $updateUrl = APP_UPDATE_URL . '/download/' . $version;
        $tempFile = tempnam(sys_get_temp_dir(), 'update');
        
        $ch = curl_init($updateUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FILE, fopen($tempFile, 'w'));
        curl_exec($ch);
        curl_close($ch);
        
        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === TRUE) {
            $zip->extractTo('.');
            $zip->close();
            unlink($tempFile);
            return true;
        }
    } catch (Exception $e) {
        error_log("خطأ في تحديث النظام: " . $e->getMessage());
    }
    return false;
}

// التحقق من صحة النظام
function validateSystem() {
    $errors = [];
    
    // التحقق من وجود المجلدات المطلوبة
    $requiredDirs = [
        'includes',
        'uploads',
        'exports',
        'cache',
        'logs',
        'backups'
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!file_exists($dir)) {
            $errors[] = "المجلد $dir غير موجود";
        } elseif (!is_writable($dir)) {
            $errors[] = "المجلد $dir غير قابل للكتابة";
        }
    }
    
    // التحقق من وجود الملفات المطلوبة
    $requiredFiles = [
        'includes/config.php',
        'includes/Database.php',
        'includes/functions.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            $errors[] = "الملف $file غير موجود";
        } elseif (!is_readable($file)) {
            $errors[] = "الملف $file غير قابل للقراءة";
        }
    }
    
    // التحقق من إعدادات قاعدة البيانات
    try {
        $db = Database::getInstance();
        $db->query("SELECT 1");
    } catch (Exception $e) {
        $errors[] = "خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage();
    }
    
    return $errors;
}

// إصلاح النظام
function repairSystem() {
    $errors = validateSystem();
    if (empty($errors)) {
        return true;
    }
    
    foreach ($errors as $error) {
        if (strpos($error, 'غير موجود') !== false) {
            $dir = str_replace(' غير موجود', '', $error);
            if (mkdir($dir, 0755, true)) {
                error_log("تم إنشاء المجلد: $dir");
            }
        } elseif (strpos($error, 'غير قابل للكتابة') !== false) {
            $dir = str_replace(' غير قابل للكتابة', '', $error);
            if (chmod($dir, 0755)) {
                error_log("تم تغيير صلاحيات المجلد: $dir");
            }
        }
    }
    
    return validateSystem() === [];
}

// إعادة تعيين النظام
function resetSystem() {
    try {
        // حذف جميع البيانات
        $db = Database::getInstance();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $db->query("TRUNCATE TABLE $table");
        }
        
        // حذف الملفات المرفوعة
        cleanUploads();
        
        // حذف ملفات التصدير
        cleanExports();
        
        // حذف سجلات النظام
        cleanActivityLogs();
        cleanErrorLogs();
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إعادة تعيين النظام: " . $e->getMessage());
        return false;
    }
}

// إعادة تشغيل النظام
function restartSystem() {
    try {
        // إيقاف النظام
        stopSystem();
        
        // تنظيف النظام
        cleanSystem();
        
        // إعادة تشغيل النظام
        startSystem();
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إعادة تشغيل النظام: " . $e->getMessage());
        return false;
    }
}

// إيقاف النظام
function stopSystem() {
    try {
        // حفظ حالة النظام
        $status = getSystemStatus();
        file_put_contents(APP_PATH . '/system.status', json_encode($status));
        
        // إغلاق اتصالات قاعدة البيانات
        $db = Database::getInstance();
        $db->close();
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في إيقاف النظام: " . $e->getMessage());
        return false;
    }
}

// تشغيل النظام
function startSystem() {
    try {
        // التحقق من حالة النظام
        if (file_exists(APP_PATH . '/system.status')) {
            $status = json_decode(file_get_contents(APP_PATH . '/system.status'), true);
            if ($status['maintenance']) {
                return false;
            }
        }
        
        // إعادة تهيئة اتصال قاعدة البيانات
        $db = Database::getInstance();
        
        return true;
    } catch (Exception $e) {
        error_log("خطأ في تشغيل النظام: " . $e->getMessage());
        return false;
    }
}

// الحصول على حالة النظام
function getSystemStatus() {
    $status = [
        'version' => APP_VERSION,
        'environment' => APP_ENV,
        'maintenance' => false,
        'last_backup' => null,
        'last_update' => null,
        'errors' => []
    ];
    
    // التحقق من وجود ملف النسخ الاحتياطي الأخير
    $backupDir = BACKUP_PATH;
    $files = glob($backupDir . '/*.sql');
    if (!empty($files)) {
        $status['last_backup'] = date('Y-m-d H:i:s', filemtime(end($files)));
    }
    
    // التحقق من وجود ملف التحديث الأخير
    if (file_exists(APP_PATH . '/system.status')) {
        $lastStatus = json_decode(file_get_contents(APP_PATH . '/system.status'), true);
        $status['last_update'] = $lastStatus['last_update'] ?? null;
    }
    
    // التحقق من وجود أخطاء
    $status['errors'] = validateSystem();
    
    return $status;
}

// تحديث حالة النظام
function updateSystemStatus($status) {
    return file_put_contents(APP_PATH . '/system.status', json_encode($status));
}

// الحصول على معلومات النظام
function getSystemInfo() {
    $info = [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'],
        'database_version' => null,
        'memory_usage' => memory_get_usage(true),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size')
    ];
    
    try {
        $db = Database::getInstance();
        $info['database_version'] = $db->query("SELECT VERSION()")->fetchColumn();
    } catch (Exception $e) {
        error_log("خطأ في جلب معلومات النظام: " . $e->getMessage());
    }
    
    return $info;
}

// الحصول على سجلات النظام
function getSystemLogs($type = 'error', $limit = 100) {
    $logFile = LOG_PATH . '/' . $type . '.log';
    if (!file_exists($logFile)) {
        return [];
    }
    
    $logs = [];
    $lines = array_slice(file($logFile), -$limit);
    
    foreach ($lines as $line) {
        if (preg_match('/\[(.*?)\] \[(.*?)\] (.*)/', $line, $matches)) {
            $logs[] = [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'message' => $matches[3]
            ];
        }
    }
    
    return $logs;
}

// الحصول على النسخ الاحتياطية
function getSystemBackups() {
    $backupDir = BACKUP_PATH;
    $backups = [];
    
    if (file_exists($backupDir)) {
        $files = glob($backupDir . '/*.sql');
        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }
    
    return $backups;
}

// الحصول على ملفات النظام
function getSystemFiles() {
    $files = [];
    $dirs = [
        'includes',
        'uploads',
        'exports',
        'cache',
        'logs',
        'backups'
    ];
    
    foreach ($dirs as $dir) {
        if (file_exists($dir)) {
            $files[$dir] = [
                'size' => getDirSize($dir),
                'files' => count(glob($dir . '/*')),
                'writable' => is_writable($dir)
            ];
        }
    }
    
    return $files;
}

// الحصول على حجم المجلد
function getDirSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : getDirSize($each);
    }
    return $size;
}

// الحصول على إعدادات النظام
function getSystemSettings() {
    try {
        $db = Database::getInstance();
        $settings = $db->query("SELECT * FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        return $settings;
    } catch (Exception $e) {
        error_log("خطأ في جلب إعدادات النظام: " . $e->getMessage());
        return [];
    }
}

// تحديث إعدادات النظام
function updateSystemSettings($settings) {
    try {
        $db = Database::getInstance();
        foreach ($settings as $key => $value) {
            $db->update(
                'settings',
                ['value' => $value],
                'key = ?',
                [$key]
            );
        }
        return true;
    } catch (Exception $e) {
        error_log("خطأ في تحديث إعدادات النظام: " . $e->getMessage());
        return false;
    }
}

// الحصول على معلومات المستخدم
function getUserInfo($user_id) {
    try {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT * FROM users WHERE id = ?",
            [$user_id]
        );
    } catch (Exception $e) {
        error_log("خطأ في جلب معلومات المستخدم: " . $e->getMessage());
        return null;
    }
}

// تحديث معلومات المستخدم
function updateUserInfo($user_id, $data) {
    try {
        $db = Database::getInstance();
        return $db->update(
            'users',
            $data,
            'id = ?',
            [$user_id]
        );
    } catch (Exception $e) {
        error_log("خطأ في تحديث معلومات المستخدم: " . $e->getMessage());
        return false;
    }
}

?>
