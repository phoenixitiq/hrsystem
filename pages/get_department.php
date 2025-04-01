<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين الملفات المطلوبة
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('view_departments')) {
    die(json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول إلى هذه الصفحة']));
}

// التحقق من وجود معرف القسم
if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'message' => 'معرف القسم مطلوب']));
}

$department_id = $_GET['id'];

try {
    $db = Database::getInstance();
    $department = $db->fetch(
        "SELECT * FROM departments 
         WHERE id = ? AND created_by = ?",
        [$department_id, $_SESSION['user_id']]
    );

    if ($department) {
        echo json_encode(['success' => true, 'department' => $department]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على القسم']);
    }
} catch (Exception $e) {
    logEvent("خطأ في جلب بيانات القسم: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب بيانات القسم']);
} 