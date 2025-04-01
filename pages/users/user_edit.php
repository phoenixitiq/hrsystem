<?php
require_once 'config.php';
requireLogin();

// Check if user has admin permission
if (!hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = $_POST['role_id'] ?? 0;
    $is_active = $_POST['is_active'] ?? 1;

    $errors = [];

    // Validate user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $errors[] = "المستخدم غير موجود";
    } else {
        // Validate username
        if (empty($username)) {
            $errors[] = "اسم المستخدم مطلوب";
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user_id]);
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
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "البريد الإلكتروني موجود مسبقاً";
            }
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

        // Prevent deactivating the last admin
        if ($role_id == 1 && $is_active == 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = 1 AND is_active = 1");
            $stmt->execute();
            $active_admin_count = $stmt->fetchColumn();
            
            if ($active_admin_count <= 1) {
                $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $current_status = $stmt->fetchColumn();
                
                if ($current_status == 1) {
                    $errors[] = "لا يمكن تعطيل آخر مدير نشط في النظام";
                }
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, role_id = ?, is_active = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $username,
                $email,
                $role_id,
                $is_active,
                $user_id
            ]);

            $_SESSION['success'] = "تم تحديث بيانات المستخدم بنجاح";
            header("Location: users.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء تحديث بيانات المستخدم";
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