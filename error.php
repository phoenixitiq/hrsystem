<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين ملف الإعدادات
require_once 'includes/config.php';

// تحديد نوع الخطأ
$errorCode = isset($_GET['code']) ? (int)$_GET['code'] : 500;
$errorMessages = [
    400 => 'طلب غير صالح',
    401 => 'غير مصرح',
    403 => 'غير مصرح لك بالوصول',
    404 => 'الصفحة غير موجودة',
    500 => 'حدث خطأ في الخادم',
    503 => 'الخدمة غير متوفرة مؤقتاً'
];

$errorMessage = isset($errorMessages[$errorCode]) ? $errorMessages[$errorCode] : 'حدث خطأ غير معروف';

// تسجيل الخطأ مع معلومات إضافية
$errorDetails = [
    'code' => $errorCode,
    'message' => $errorMessage,
    'url' => $_SERVER['REQUEST_URI'] ?? 'unknown',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
    'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
    'time' => date('Y-m-d H:i:s'),
    'session_id' => session_id() ?? 'no session',
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true)
];

// تسجيل الخطأ في ملف السجلات
logError("Error Details: " . print_r($errorDetails, true), 'ERROR');

// تعيين رأس HTTP
http_response_code($errorCode);
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// تنظيف ذاكرة التخزين المؤقت إذا كان خطأ خادم
if ($errorCode >= 500) {
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    if (class_exists('Cache')) {
        $cache = new Cache();
        $cache->clear();
    }
}

// تنظيف الجلسة في حالة خطأ 401 أو 403
if ($errorCode == 401 || $errorCode == 403) {
    session_destroy();
    session_start();
    session_regenerate_id(true);
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ <?php echo $errorCode; ?> - <?php echo SITE_NAME; ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            direction: rtl;
        }
        .error-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 2em;
        }
        p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .back-button:hover {
            background-color: #2980b9;
        }
        .error-code {
            font-size: 4em;
            color: #e74c3c;
            margin: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 1.2em;
            color: #2c3e50;
            margin: 20px 0;
        }
        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $errorCode; ?></div>
        <h1>عذراً!</h1>
        <p class="error-message"><?php echo $errorMessage; ?></p>
        <a href="<?php echo APP_URL; ?>" class="back-button">العودة للصفحة الرئيسية</a>
        <div class="contact-info">
            <p>إذا استمرت المشكلة، يرجى التواصل مع مدير النظام</p>
            <p>البريد الإلكتروني: <?php echo SITE_EMAIL; ?></p>
        </div>
    </div>
</body>
</html> 