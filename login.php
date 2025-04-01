<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين ملف الإعدادات
require_once 'includes/config.php';
require_once 'includes/Database.php';

session_start();

// إذا كان المستخدم مسجل الدخول بالفعل، قم بتوجيهه إلى الصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز التحقق غير صالح';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'يرجى إدخال اسم المستخدم وكلمة المرور';
        } else {
            try {
                $db = Database::getInstance();
                $user = $db->fetch(
                    "SELECT * FROM users WHERE username = ?",
                    [$username]
                );

                if (!$user || !is_array($user)) {
                    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
                    logEvent("محاولة تسجيل دخول فاشلة: $username", 'warning');
                } else {
                    // التحقق من حالة الحساب
                    if ($user['status'] !== 'active') {
                        $error = 'الحساب غير نشط. يرجى التواصل مع الإدارة';
                        logEvent("محاولة تسجيل دخول لحساب غير نشط: $username", 'warning');
                    } else {
                        // التحقق من عدد محاولات تسجيل الدخول
                        if ($user['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                            $timeout = LOGIN_TIMEOUT - (time() - strtotime($user['last_login_attempt']));
                            if ($timeout > 0) {
                                $error = 'تم تجاوز الحد الأقصى لمحاولات تسجيل الدخول. يرجى المحاولة بعد ' . ceil($timeout / 60) . ' دقيقة';
                            } else {
                                // إعادة تعيين عدد المحاولات
                                $db->update(
                                    'users',
                                    ['login_attempts' => 0],
                                    'id = ?',
                                    [$user['id']]
                                );
                            }
                        }

                        if (empty($error) && password_verify($password, $user['password'])) {
                            // تسجيل الدخول ناجح
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['last_activity'] = time();

                            // تحديث معلومات تسجيل الدخول
                            $db->update(
                                'users',
                                [
                                    'login_attempts' => 0,
                                    'last_login' => date('Y-m-d H:i:s'),
                                    'last_login_ip' => $_SERVER['REMOTE_ADDR']
                                ],
                                'id = ?',
                                [$user['id']]
                            );

                            // تسجيل النشاط
                            logActivity($user['id'], 'login', 'تسجيل دخول ناجح');
                            logEvent("تسجيل دخول ناجح للمستخدم: $username", 'info');

                            header('Location: index.php');
                            exit;
                        } else {
                            // تسجيل الدخول فشل
                            $attempts = $user['login_attempts'] + 1;
                            $db->update(
                                'users',
                                [
                                    'login_attempts' => $attempts,
                                    'last_login_attempt' => date('Y-m-d H:i:s')
                                ],
                                'id = ?',
                                [$user['id']]
                            );

                            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                                $error = 'تم تجاوز الحد الأقصى لمحاولات تسجيل الدخول. يرجى المحاولة بعد ' . ceil(LOGIN_TIMEOUT / 60) . ' دقيقة';
                            } else {
                                $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
                            }
                            logEvent("محاولة تسجيل دخول فاشلة: $username", 'warning');
                        }
                    }
                }
            } catch (Exception $e) {
                $error = 'حدث خطأ أثناء تسجيل الدخول';
                logEvent("خطأ في تسجيل الدخول: " . $e->getMessage(), 'error');
            }
        }
    }
}

// إنشاء رمز CSRF جديد
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">تسجيل الدخول</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>