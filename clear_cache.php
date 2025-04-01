<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين ملف الإعدادات
require_once 'includes/config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// التحقق من الصلاحيات
if (!hasPermission('manage_settings')) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// التحقق من رمز CSRF
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    header("Location: index.php?error=invalid_token");
    exit();
}

try {
    // إنشاء كائن الكاش
    $cache = new Cache();
    
    // تنظيف الكاش
    $cache->clear();
    
    // تسجيل العملية
    logEvent("تم تنظيف الكاش بواسطة المستخدم: " . $_SESSION['username'], 'info');
    
    // إعادة التوجيه مع رسالة نجاح
    header("Location: index.php?success=cache_cleared");
} catch (Exception $e) {
    // تسجيل الخطأ
    error_log("خطأ في تنظيف الكاش: " . $e->getMessage());
    
    // إعادة التوجيه مع رسالة خطأ
    header("Location: index.php?error=cache_clear_failed");
}
exit(); 