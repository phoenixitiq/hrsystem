<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/export.php';

// التحقق من وجود اسم الملف
if (!isset($_GET['file']) || empty($_GET['file'])) {
    die('لم يتم تحديد الملف');
}

$filename = basename($_GET['file']);
$filepath = EXPORT_PATH . '/' . $filename;

try {
    $export = Export::getInstance();
    $export->downloadFile($filepath);
} catch (Exception $e) {
    die('حدث خطأ: ' . $e->getMessage());
} 