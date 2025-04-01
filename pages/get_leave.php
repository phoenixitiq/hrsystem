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
if (!hasPermission('view_leaves')) {
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول إلى هذه البيانات']);
    exit;
}

// التحقق من وجود معرف طلب الإجازة
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'معرف طلب الإجازة مطلوب']);
    exit;
}

$leave_id = $_GET['id'];

try {
    $db = Database::getInstance();
    $leave = $db->fetch(
        "SELECT * FROM leaves 
         WHERE id = ? AND created_by = ?",
        [$leave_id, $_SESSION['user_id']]
    );

    if ($leave) {
        echo json_encode(['success' => true, 'leave' => $leave]);
    } else {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على طلب الإجازة']);
    }
} catch (Exception $e) {
    logEvent("خطأ في جلب بيانات طلب الإجازة: " . $e->getMessage(), 'error');
    echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب البيانات']);
} 