<?php
require_once 'config.php';
requireLogin();

// Check if user has permission
if (!hasPermission(['admin', 'accountant'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? 0;
    $basic_salary = $_POST['basic_salary'] ?? 0;
    $allowances = $_POST['allowances'] ?? 0;
    $deductions = $_POST['deductions'] ?? 0;
    $payment_date = $_POST['payment_date'] ?? '';

    $errors = [];

    // Validate employee
    if (empty($employee_id)) {
        $errors[] = "الموظف مطلوب";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ? AND status = 'active'");
        $stmt->execute([$employee_id]);
        if (!$stmt->fetch()) {
            $errors[] = "الموظف غير موجود أو غير نشط";
        }
    }

    // Validate basic salary
    if (empty($basic_salary) || $basic_salary < 0) {
        $errors[] = "الراتب الأساسي يجب أن يكون أكبر من صفر";
    }

    // Validate allowances
    if ($allowances < 0) {
        $errors[] = "البدلات لا يمكن أن تكون سالبة";
    }

    // Validate deductions
    if ($deductions < 0) {
        $errors[] = "الخصومات لا يمكن أن تكون سالبة";
    }

    // Validate payment date
    if (empty($payment_date)) {
        $errors[] = "تاريخ الدفع مطلوب";
    }

    // Calculate net salary
    $net_salary = $basic_salary + $allowances - $deductions;
    if ($net_salary < 0) {
        $errors[] = "الراتب الصافي لا يمكن أن يكون سالباً";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO salaries (employee_id, basic_salary, allowances, deductions, net_salary, payment_date) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $employee_id,
                $basic_salary,
                $allowances,
                $deductions,
                $net_salary,
                $payment_date
            ]);

            $_SESSION['success'] = "تم إضافة الراتب بنجاح";
            header("Location: salary.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء إضافة الراتب";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: salary.php");
        exit();
    }
}

// If not POST request, redirect to salary page
header("Location: salary.php");
exit(); 