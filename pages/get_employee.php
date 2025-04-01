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
if (!hasPermission('view_employees')) {
    die(json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول إلى هذه الصفحة']));
}

// التحقق من وجود معرف الموظف
if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'message' => 'معرف الموظف مطلوب']));
}

$employee_id = $_GET['id'];

try {
    $db = Database::getInstance();
    $employee = $db->fetch(
        "SELECT * FROM employees 
         WHERE id = ? AND created_by = ?",
        [$employee_id, $_SESSION['user_id']]
    );

    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على الموظف']);
    }
} catch (Exception $e) {
    logEvent("خطأ في جلب بيانات الموظف: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب بيانات الموظف']);
} 