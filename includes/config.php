<?php
// تعيين إعدادات PHP الأساسية
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);
ini_set('post_max_size', '10M');
ini_set('upload_max_filesize', '10M');
ini_set('max_input_vars', 1000);
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.name', 'HRSESSID');

// تعريف المسارات الأساسية
define('BASEPATH', dirname(__DIR__));
define('INCLUDES_PATH', BASEPATH . '/includes');
define('EXPORT_PATH', BASEPATH . '/exports');
define('APP_PATH', BASEPATH . '/app');
define('UPLOAD_PATH', BASEPATH . '/uploads');
define('CACHE_PATH', BASEPATH . '/cache');
define('LOG_PATH', BASEPATH . '/logs');

// تعريف اسم الموقع
define('SITE_NAME', 'نظام إدارة الموارد البشرية');

// إنشاء مجلد السجلات إذا لم يكن موجوداً
$logDir = LOG_PATH;
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}

// تعيين مسار ملف السجلات
ini_set('error_log', $logDir . '/php_errors.log');

// تسجيل الأخطاء
function logError($message, $severity = 'ERROR') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$severity] $message" . PHP_EOL;
    error_log($logMessage);
}

// معالجة الأخطاء غير الملتقطة
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    logError("$errstr in $errfile on line $errline", 'ERROR');
    return true;
});

// معالجة الاستثناءات غير الملتقطة
set_exception_handler(function($exception) {
    logError("Uncaught Exception: " . $exception->getMessage(), 'ERROR');
    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
        throw $exception;
    } else {
        header('Location: /error.php?code=500');
        exit;
    }
});

try {
    // التحقق من وجود مجلد vendor
    $vendorDir = BASEPATH . '/vendor';
    if (!file_exists($vendorDir)) {
        throw new Exception('مجلد vendor غير موجود. يرجى تشغيل: composer install');
    }

    // التحقق من وجود ملف composer.json
    $composerJson = BASEPATH . '/composer.json';
    if (!file_exists($composerJson)) {
        throw new Exception('ملف composer.json غير موجود');
    }

    // التحقق من وجود ملف composer.lock
    $composerLock = BASEPATH . '/composer.lock';
    if (!file_exists($composerLock)) {
        throw new Exception('ملف composer.lock غير موجود. يرجى تشغيل: composer install');
    }

    // تحميل Composer Autoloader
    $autoloadFile = BASEPATH . '/vendor/autoload.php';
    if (!file_exists($autoloadFile)) {
        throw new Exception('ملف autoload.php غير موجود. يرجى تشغيل: composer install');
    }
    require_once $autoloadFile;

    // تحميل وتهيئة Dotenv
    $envFile = BASEPATH . '/.env';
    if (file_exists($envFile)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(BASEPATH);
        try {
            $dotenv->load();
            
            // التحقق من المتغيرات المطلوبة
            $dotenv->required([
                'DB_HOST',
                'DB_NAME',
                'DB_USER',
                'DB_PASS'
            ])->notEmpty();
        } catch (\Dotenv\Exception\ValidationException $e) {
            throw new Exception('خطأ في ملف .env: ' . $e->getMessage());
        }
    } else {
        // إذا لم يكن ملف .env موجوداً، نستخدم القيم الافتراضية
        $_ENV['DB_HOST'] = 'localhost';
        $_ENV['DB_NAME'] = 'hrsystem';
        $_ENV['DB_USER'] = 'root';
        $_ENV['DB_PASS'] = '';
        $_ENV['APP_ENV'] = 'development';
    }

    // إعدادات قاعدة البيانات
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'hrsystem');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
    define('DB_CHARSET', 'utf8mb4');

    // إعدادات البريد الإلكتروني
    define('SMTP_HOST', isset($_ENV['SMTP_HOST']) ? $_ENV['SMTP_HOST'] : 'smtp.gmail.com');
    define('SMTP_PORT', isset($_ENV['SMTP_PORT']) ? $_ENV['SMTP_PORT'] : 587);
    define('SMTP_USER', isset($_ENV['SMTP_USER']) ? $_ENV['SMTP_USER'] : '');
    define('SMTP_PASS', isset($_ENV['SMTP_PASS']) ? $_ENV['SMTP_PASS'] : '');
    define('SITE_EMAIL', isset($_ENV['MAIL_FROM_ADDRESS']) ? $_ENV['MAIL_FROM_ADDRESS'] : 'noreply@hr.teebaalahmd.com.iq');
    define('SMTP_FROM', SITE_EMAIL);
    define('SMTP_FROM_NAME', isset($_ENV['MAIL_FROM_NAME']) ? $_ENV['MAIL_FROM_NAME'] : 'نظام إدارة الموارد البشرية');

    // إعدادات الأمان
    define('CSRF_TOKEN_NAME', 'csrf_token');
    define('SESSION_LIFETIME', 7200); // ساعتان
    define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
    define('PASSWORD_HASH_COST', 10);

    // إعدادات التخزين المؤقت
    define('CACHE_LIFETIME', 300); // 5 دقائق
    define('CACHE_PREFIX', 'hr_');

    // إعدادات التصدير
    define('EXPORT_CHUNK_SIZE', 1000);
    define('EXPORT_MAX_ROWS', 10000);

    // إعدادات التقارير
    define('REPORT_DATE_FORMAT', 'Y-m-d');
    define('REPORT_TIME_FORMAT', 'H:i:s');
    define('REPORT_DATETIME_FORMAT', 'Y-m-d H:i:s');

    // إعدادات الواجهة
    define('DATE_FORMAT', 'Y-m-d');
    define('TIME_FORMAT', 'H:i:s');
    define('DATETIME_FORMAT', 'Y-m-d H:i:s');
    define('CURRENCY_SYMBOL', 'د.ك');
    define('CURRENCY_POSITION', 'after');
    define('THOUSAND_SEPARATOR', ',');
    define('DECIMAL_SEPARATOR', '.');

    // إعدادات الملفات
    define('MAX_UPLOAD_SIZE', 5242880); // 5MB
    define('ALLOWED_FILE_TYPES', [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ]);

    // إعدادات النظام
    define('DEFAULT_LANGUAGE', 'ar');
    define('DEFAULT_TIMEZONE', 'Asia/Riyadh');
    define('DEFAULT_PAGINATION', 10);
    define('MAX_LOGIN_ATTEMPTS', 5);
    define('LOGIN_TIMEOUT', 900); // 15 دقيقة

    // إعدادات الإشعارات
    define('NOTIFICATION_LIFETIME', 30); // 30 يوم
    define('MAX_NOTIFICATIONS', 100);

    // إعدادات السجلات
    define('LOG_LEVEL', 'debug'); // debug, info, warning, error
    define('LOG_ROTATION', true);
    define('MAX_LOG_FILES', 10);
    define('MAX_LOG_SIZE', 5242880); // 5MB

    // إنشاء المجلدات المطلوبة
    $requiredDirs = [
        UPLOAD_PATH,
        CACHE_PATH,
        EXPORT_PATH,
        LOG_PATH,
        APP_PATH . '/cache/sessions'
    ];

    foreach ($requiredDirs as $dir) {
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                throw new Exception("فشل في إنشاء المجلد: $dir");
            }
        }
        if (!is_writable($dir)) {
            throw new Exception("المجلد غير قابل للكتابة: $dir");
        }
    }

    // تهيئة قاعدة البيانات
    try {
        // إنشاء اتصال قاعدة البيانات باستخدام الكلاس المخصص
        $db = Database::getInstance();
        $pdo = $db->getConnection();
    } catch (Exception $e) {
        logError("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
        throw new Exception("فشل الاتصال بقاعدة البيانات");
    }

    // إعدادات الجلسة
    ini_set('session.cookie_lifetime', 7200);
    ini_set('session.gc_maxlifetime', 7200);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.cookie_httponly', true);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', true);
    ini_set('session.use_only_cookies', true);
    ini_set('session.name', 'HRSESSID');

    // تعيين مسار تخزين الجلسة
    $sessionPath = APP_PATH . '/cache/sessions';
    if (!is_writable(session_save_path())) {
        session_save_path($sessionPath);
        if (!is_writable(session_save_path())) {
            throw new Exception("مسار حفظ الجلسات غير قابل للكتابة: $sessionPath");
        }
    }

    // بدء الجلسة
    if (!session_id()) {
        if (!@session_start()) {
            throw new Exception("فشل في بدء الجلسة");
        }
    }

    // تهيئة المنطقة الزمنية
    date_default_timezone_set('Asia/Riyadh');

    // تهيئة التخزين المؤقت
    try {
        $cache = new Cache();
    } catch (Exception $e) {
        logError("خطأ في تهيئة الكاش: " . $e->getMessage());
        $cache = null;
    }

    // تهيئة نظام الإشعارات
    try {
        $notifications = new Notification();
    } catch (Exception $e) {
        logError("خطأ في تهيئة نظام الإشعارات: " . $e->getMessage());
        $notifications = null;
    }

    // مسارات النظام
    define('BACKUP_PATH', __DIR__ . '/../backups/');
    define('TEMP_PATH', __DIR__ . '/../temp/');
    define('UPLOAD_PATH', __DIR__ . '/../uploads/');
    define('EXPORT_PATH', __DIR__ . '/../exports/');

    // إعدادات التطبيق
    define('APP_ENV', 'development'); // development, production
    define('APP_UPDATE_URL', 'https://api.example.com/updates');

} catch (Exception $e) {
    logError($e->getMessage());
    
    // في وضع التطوير، اعرض الخطأ
    if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
        die("خطأ: " . $e->getMessage());
    } else {
        // في وضع الإنتاج، اعرض رسالة خطأ عامة
        die("عذراً، حدث خطأ في النظام. يرجى المحاولة لاحقاً.");
    }
}
