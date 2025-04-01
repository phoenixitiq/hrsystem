<?php
/**
 * ملف الدوال المساعدة
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * تنسيق المبالغ المالية
 * @param float $amount المبلغ
 * @return string
 */
function formatCurrency($amount) {
    return number_format($amount, 2, '.', ',') . ' د.ع';
}

/**
 * تحويل العملة
 * @param float $amount المبلغ
 * @param string $from العملة الأصلية
 * @param string $to العملة المطلوبة
 * @return float
 */
function convertCurrency($amount, $from, $to) {
    if ($from === $to) {
        return $amount;
    }
    
    $exchange_rate = floatval(getSetting('usd_exchange_rate', 1460));
    if ($exchange_rate <= 0) {
        return $amount;
    }
    
    if ($from === 'USD' && $to === 'IQD') {
        return $amount * $exchange_rate;
    } elseif ($from === 'IQD' && $to === 'USD') {
        return $amount / $exchange_rate;
    }
    
    return $amount;
}

/**
 * دالة تنظيف والتحقق من المدخلات
 * @param mixed $input المدخلات المراد التحقق منها
 * @param string $type نوع البيانات
 * @return mixed|array القيمة بعد التحقق أو مصفوفة تحتوي على خطأ
 */
function validateInput($input, $type = 'string') {
    // تنظيف المدخلات
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);

    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) ? $input : false;
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT) !== false ? (int)$input : false;
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT) !== false ? (float)$input : false;
        case 'date':
            $date = DateTime::createFromFormat('Y-m-d', $input);
            return $date && $date->format('Y-m-d') === $input ? $input : false;
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) ? $input : false;
        case 'phone':
            return preg_match('/^(07[3-9]|075)\d{8}$/', $input) ? $input : false;
        case 'username':
            return preg_match('/^[a-zA-Z0-9_\x{0621}-\x{064A}]{3,20}$/u', $input) ? $input : false;
        case 'password':
            return validatePassword($input) ? $input : false;
        default:
            return $input;
    }
}

/**
 * التحقق من قوة كلمة المرور
 * @param string $password كلمة المرور
 * @return bool
 */
function validatePassword($password) {
    // التحقق من طول كلمة المرور وتعقيدها
    if (strlen($password) < PASSWORD_MIN_LENGTH) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
    return true;
}

/**
 * التحقق من تسجيل دخول المستخدم
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * التحقق من الصلاحيات المطلوبة
 */
function hasPermission($permission) {
    if (!isLoggedIn()) {
        return false;
    }

    // المدير العام لديه كل الصلاحيات
    if ($_SESSION['role'] == 1) {
        return true;
    }

    // التحقق من الصلاحيات حسب دور المستخدم
    $permissions = [
        1 => ['all'], // مدير النظام
        2 => ['manage_employees', 'manage_salaries', 'manage_loans', 'manage_debts'], // مدير
        3 => ['manage_salaries', 'view_employees'], // محاسب
        4 => ['manage_employees', 'view_salaries'] // مدير الموارد البشرية
    ];

    $userRole = $_SESSION['role'];
    return isset($permissions[$userRole]) && in_array($permission, $permissions[$userRole]);
}

/**
 * تسجيل الأحداث والأخطاء
 */
function logEvent($message, $type = 'info', $context = []) {
    $logFile = LOG_PATH . '/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $logMessage = "[$timestamp] [$type] $message $contextStr\n";
    
    // إذا لم يكن المجلد موجودًا، سيتم إنشاؤه
    if (!file_exists(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    // لا تسجل في بيئة الإنتاج رسائل حساسة
    if ($_SERVER['ENV'] !== 'production') {
        error_log($logMessage, 3, $logFile);
    }
}

/**
 * دالة لتحميل الملفات
 */
function uploadFile($file, $type = 'image') {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    // التحقق من حجم الملف
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }

    // التحقق من نوع الملف
    $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileType, ALLOWED_FILE_TYPES)) {
        return false;
    }

    // إنشاء اسم فريد للملف
    $fileName = uniqid() . '.' . $fileType;
    $uploadPath = UPLOAD_PATH . '/' . $type;

    // إنشاء المجلد إذا لم يكن موجوداً
    if (!file_exists($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    // نقل الملف
    if (move_uploaded_file($file['tmp_name'], $uploadPath . '/' . $fileName)) {
        return $fileName;
    }

    return false;
}

/**
 * دالة لتحويل التاريخ إلى التنسيق العربي
 */
function formatDate($date, $format = 'Y-m-d') {
    $date = new DateTime($date);
    return $date->format($format);
}

/**
 * دالة لتحويل الأرقام إلى التنسيق العربي
 */
function formatNumber($number) {
    return number_format($number, 2, '.', ',');
}

/**
 * دالة لإنشاء رابط
 */
function createLink($path, $params = []) {
    $url = APP_URL . '/' . $path;
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

/**
 * دالة لإنشاء رمز CSRF
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * دالة للتحقق من رمز CSRF
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

$companyName = getSetting('company_name', 'اسم الشركة الافتراضي');
$maxUploadSize = getSetting('max_upload_size', 5242880); // 5MB افتراضياً 