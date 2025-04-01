<?php
require_once 'config.php';
requireLogin();

// Check if user has admin permission
if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? 0;

    $errors = [];

    // Validate username
    if (empty($username)) {
        $errors[] = "اسم المستخدم مطلوب";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "اسم المستخدم موجود مسبقاً";
        }
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "البريد الإلكتروني مطلوب";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "البريد الإلكتروني موجود مسبقاً";
        }
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "كلمة المرور مطلوبة";
    } elseif (strlen($password) < 6) {
        $errors[] = "يجب أن تكون كلمة المرور 6 أحرف على الأقل";
    }

    // Validate role
    if (empty($role_id)) {
        $errors[] = "الدور مطلوب";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM roles WHERE id = ?");
        $stmt->execute([$role_id]);
        if ($stmt->fetchColumn() == 0) {
            $errors[] = "الدور غير موجود";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, role_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $email,
                $role_id
            ]);

            $_SESSION['success'] = "تم إضافة المستخدم بنجاح";
            header("Location: users.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء إضافة المستخدم";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: users.php");
        exit();
    }
}

// If not POST request, redirect to users page
header("Location: users.php");
exit(); 