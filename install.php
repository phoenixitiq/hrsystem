<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين ملف الإعدادات
require_once 'includes/config.php';

// التحقق من أن الملف موجود في المجلد الرئيسي
if (basename($_SERVER['PHP_SELF']) !== 'install.php') {
    die('Access Denied');
}

// التحقق من أن النظام غير مثبت بالفعل
if (file_exists('includes/config.php') && defined('INSTALLED') && INSTALLED === true) {
    die('النظام مثبت بالفعل. يرجى حذف ملف install.php');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// التحقق من متطلبات النظام
function checkSystemRequirements() {
    $requirements = [
        'php' => [
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'extensions' => [
            'pdo' => extension_loaded('pdo'),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'gd' => extension_loaded('gd'),
            'mbstring' => extension_loaded('mbstring'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl')
        ],
        'permissions' => [
            'includes' => is_writable('includes'),
            'uploads' => is_writable('uploads'),
            'exports' => is_writable('exports'),
            'cache' => is_writable('cache'),
            'logs' => is_writable('logs')
        ]
    ];

    return $requirements;
}

// إنشاء قاعدة البيانات
function createDatabase($host, $username, $password, $database) {
    try {
        // إنشاء اتصال بدون تحديد قاعدة بيانات
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // إنشاء قاعدة البيانات إذا لم تكن موجودة
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$database`");

        // إنشاء جداول قاعدة البيانات
        $sql = file_get_contents('database/schema.sql');
        $pdo->exec($sql);

        return true;
    } catch (PDOException $e) {
        error_log("خطأ في إنشاء قاعدة البيانات: " . $e->getMessage());
        return false;
    }
}

// إنشاء ملف الإعدادات
function createConfigFile($config) {
    $configContent = "<?php\n";
    $configContent .= "define('BASEPATH', true);\n";
    $configContent .= "define('INSTALLED', true);\n\n";
    
    foreach ($config as $key => $value) {
        if (is_string($value)) {
            $configContent .= "define('$key', '$value');\n";
        } else {
            $configContent .= "define('$key', " . ($value ? 'true' : 'false') . ");\n";
        }
    }

    return file_put_contents('includes/config.php', $configContent);
}

// معالجة الخطوات
switch ($step) {
    case 1:
        // فحص متطلبات النظام
        $requirements = checkSystemRequirements();
        $canProceed = true;
        foreach ($requirements['php'] as $key => $value) {
            if ($key === 'status' && !$value) {
                $canProceed = false;
            }
        }
        foreach ($requirements['extensions'] as $loaded) {
            if (!$loaded) {
                $canProceed = false;
            }
        }
        foreach ($requirements['permissions'] as $writable) {
            if (!$writable) {
                $canProceed = false;
            }
        }
        break;

    case 2:
        // إعداد قاعدة البيانات
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $host = $_POST['db_host'] ?? '';
            $username = $_POST['db_username'] ?? '';
            $password = $_POST['db_password'] ?? '';
            $database = $_POST['db_name'] ?? '';

            if (empty($host) || empty($username) || empty($database)) {
                $error = 'يرجى ملء جميع الحقول المطلوبة';
            } else {
                if (createDatabase($host, $username, $password, $database)) {
                    $success = 'تم إنشاء قاعدة البيانات بنجاح';
                    header('Location: install.php?step=3');
                    exit;
                } else {
                    $error = 'فشل في إنشاء قاعدة البيانات. يرجى التحقق من الإعدادات';
                }
            }
        }
        break;

    case 3:
        // إنشاء حساب المسؤول
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $admin_username = $_POST['admin_username'] ?? '';
            $admin_password = $_POST['admin_password'] ?? '';
            $admin_email = $_POST['admin_email'] ?? '';

            if (empty($admin_username) || empty($admin_password) || empty($admin_email)) {
                $error = 'يرجى ملء جميع الحقول المطلوبة';
            } else {
                try {
                    $db = Database::getInstance();
                    
                    // إنشاء حساب المسؤول
                    $db->insert('users', [
                        'username' => $admin_username,
                        'password' => password_hash($admin_password, PASSWORD_BCRYPT, ['cost' => 12]),
                        'email' => $admin_email,
                        'role' => 'admin',
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    // إنشاء ملف الإعدادات
                    $config = [
                        'DB_HOST' => $_SESSION['db_host'],
                        'DB_USERNAME' => $_SESSION['db_username'],
                        'DB_PASSWORD' => $_SESSION['db_password'],
                        'DB_NAME' => $_SESSION['db_name'],
                        'APP_NAME' => $_POST['app_name'] ?? 'نظام إدارة الموارد البشرية',
                        'APP_URL' => $_POST['app_url'] ?? '',
                        'APP_ENV' => 'production',
                        'APP_DEBUG' => false,
                        'APP_KEY' => bin2hex(random_bytes(32))
                    ];

                    if (createConfigFile($config)) {
                        $success = 'تم تثبيت النظام بنجاح';
                        header('Location: install.php?step=4');
                        exit;
                    } else {
                        $error = 'فشل في إنشاء ملف الإعدادات';
                    }
                } catch (Exception $e) {
                    error_log("خطأ في إنشاء حساب المسؤول: " . $e->getMessage());
                    $error = 'حدث خطأ أثناء إنشاء حساب المسؤول';
                }
            }
        }
        break;
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تثبيت نظام إدارة الموارد البشرية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .install-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .requirement-item {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .requirement-item.true {
            background-color: #d4edda;
            color: #155724;
        }
        .requirement-item.false {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-container">
            <h2 class="text-center mb-4">تثبيت نظام إدارة الموارد البشرية</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <h3 class="mb-3">الخطوة 1: التحقق من المتطلبات</h3>
                
                <h4 class="mb-3">متطلبات PHP:</h4>
                <?php foreach ($requirements['php'] as $key => $value): ?>
                    <div class="requirement-item <?php echo $value ? 'true' : 'false'; ?>">
                        <?php echo $key; ?>: <?php echo $value ? '✓' : '✗'; ?>
                    </div>
                <?php endforeach; ?>

                <h4 class="mb-3 mt-4">المكونات المطلوبة:</h4>
                <?php foreach ($requirements['extensions'] as $loaded): ?>
                    <div class="requirement-item <?php echo $loaded ? 'true' : 'false'; ?>">
                        <?php echo $key; ?>: <?php echo $loaded ? '✓' : '✗'; ?>
                    </div>
                <?php endforeach; ?>

                <h4 class="mb-3 mt-4">صلاحيات المجلدات:</h4>
                <?php foreach ($requirements['permissions'] as $path => $isWritable): ?>
                    <div class="requirement-item <?php echo $isWritable ? 'true' : 'false'; ?>">
                        <?php echo $path; ?>: <?php echo $isWritable ? 'قابل للكتابة' : 'غير قابل للكتابة'; ?>
                    </div>
                <?php endforeach; ?>

                <?php if ($canProceed): ?>
                    <div class="text-center mt-4">
                        <a href="?step=2" class="btn btn-primary">التالي</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger mt-4">
                        يرجى حل المشاكل المذكورة أعلاه قبل المتابعة
                    </div>
                <?php endif; ?>

            <?php elseif ($step == 2): ?>
                <h3 class="mb-3">الخطوة 2: إعداد قاعدة البيانات</h3>

                <form method="post" action="">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">خادم قاعدة البيانات</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>

                    <div class="mb-3">
                        <label for="db_name" class="form-label">اسم قاعدة البيانات</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="hrsystem" required>
                    </div>

                    <div class="mb-3">
                        <label for="db_user" class="form-label">اسم المستخدم</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    </div>

                    <div class="mb-3">
                        <label for="db_pass" class="form-label">كلمة المرور</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary">تثبيت</button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <h3 class="text-center mb-4">تم تثبيت النظام بنجاح!</h3>
                <div class="alert alert-success">
                    <p>تم تثبيت نظام إدارة الموارد البشرية بنجاح.</p>
                    <p>يمكنك الآن تسجيل الدخول باستخدام:</p>
                    <ul>
                        <li>اسم المستخدم: admin</li>
                        <li>كلمة المرور: admin123</li>
                    </ul>
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 