<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين ملف الإعدادات
require_once 'config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// التحقق من صلاحية الجلسة
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
    session_unset();
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}

// تحديث وقت آخر نشاط
$_SESSION['last_activity'] = time();

// التحقق من صلاحيات المستخدم
$user_id = $_SESSION['user_id'];
$db = Database::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user || $user['status'] !== 'active') {
    session_unset();
    session_destroy();
    header('Location: login.php?error=account_disabled');
    exit;
}

// تحديث معلومات المستخدم في الجلسة
$_SESSION['user_role'] = $user['role'];
$_SESSION['user_permissions'] = json_decode($user['permissions'] ?? '[]', true);

// إنشاء رمز CSRF جديد إذا لم يكن موجوداً
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generateCSRFToken();
}

// تعيين رأس HTTP الأمنية
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\';');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// تعيين نوع المحتوى والترميز
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_NAME; ?> - نظام إدارة الموارد البشرية">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/favicon.png">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (hasPermission('dashboard')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">لوحة التحكم</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('employees')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="employees.php">الموظفين</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('attendance')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">الحضور والغياب</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('leaves')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="leaves.php">الإجازات</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('payroll')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="payroll.php">الرواتب</a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('reports')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">التقارير</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (hasPermission('settings')): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i> الإعدادات
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user-circle"></i> الملف الشخصي
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="change_password.php">
                                    <i class="fas fa-key"></i> تغيير كلمة المرور
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 