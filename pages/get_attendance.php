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
if (!hasPermission('view_attendance')) {
    die(json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول إلى هذه الصفحة']));
}

// التحقق من وجود معرف تسجيل الحضور
if (!isset($_GET['id'])) {
    die(json_encode(['success' => false, 'message' => 'معرف تسجيل الحضور مطلوب']));
}

$attendance_id = $_GET['id'];

try {
    $db = Database::getInstance();
    $attendance = $db->fetch(
        "SELECT * FROM attendance 
         WHERE id = ? AND created_by = ?",
        [$attendance_id, $_SESSION['user_id']]
    );

    if ($attendance) {
        echo json_encode(['success' => true, 'attendance' => $attendance]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على تسجيل الحضور']);
    }
} catch (Exception $e) {
    logEvent("خطأ في جلب بيانات تسجيل الحضور: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب بيانات تسجيل الحضور']);
} 