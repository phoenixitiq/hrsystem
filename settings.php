<?php
require_once 'config.php';
requireLogin();

// Check if user has permission
if (!hasPermission(['admin'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $company_address = $_POST['company_address'] ?? '';
    $company_phone = $_POST['company_phone'] ?? '';
    $company_email = $_POST['company_email'] ?? '';
    $tax_rate = $_POST['tax_rate'] ?? 0;
    $insurance_rate = $_POST['insurance_rate'] ?? 0;
    $working_hours = $_POST['working_hours'] ?? 8;
    $overtime_rate = $_POST['overtime_rate'] ?? 1.5;
    $attendance_start_time = $_POST['attendance_start_time'] ?? '09:00';
    $attendance_end_time = $_POST['attendance_end_time'] ?? '17:00';
    $late_threshold_minutes = $_POST['late_threshold_minutes'] ?? 15;
    $early_leave_threshold_minutes = $_POST['early_leave_threshold_minutes'] ?? 15;
    $default_currency = $_POST['default_currency'] ?? 'IQD';
    $usd_exchange_rate = $_POST['usd_exchange_rate'] ?? 0;

    $errors = [];

    // Validate company name
    if (empty($company_name)) {
        $errors[] = "اسم الشركة مطلوب";
    }

    // Validate company email
    if (!empty($company_email) && !filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "البريد الإلكتروني غير صالح";
    }

    // Validate tax rate
    if ($tax_rate < 0 || $tax_rate > 100) {
        $errors[] = "نسبة الضريبة يجب أن تكون بين 0 و 100";
    }

    // Validate insurance rate
    if ($insurance_rate < 0 || $insurance_rate > 100) {
        $errors[] = "نسبة التأمين يجب أن تكون بين 0 و 100";
    }

    // Validate working hours
    if ($working_hours < 1 || $working_hours > 24) {
        $errors[] = "ساعات العمل يجب أن تكون بين 1 و 24";
    }

    // Validate overtime rate
    if ($overtime_rate < 1) {
        $errors[] = "معدل العمل الإضافي يجب أن يكون أكبر من أو يساوي 1";
    }

    // Validate attendance times
    if (empty($attendance_start_time) || empty($attendance_end_time)) {
        $errors[] = "أوقات الحضور مطلوبة";
    }

    // Validate thresholds
    if ($late_threshold_minutes < 0) {
        $errors[] = "حد التأخير لا يمكن أن يكون سالباً";
    }
    if ($early_leave_threshold_minutes < 0) {
        $errors[] = "حد المغادرة المبكرة لا يمكن أن يكون سالباً";
    }

    // Validate exchange rate
    if ($usd_exchange_rate <= 0) {
        $errors[] = "سعر صرف الدولار يجب أن يكون أكبر من صفر";
    }

    if (empty($errors)) {
        try {
            // Update settings in database
            $settings = [
                'company_name' => $company_name,
                'company_address' => $company_address,
                'company_phone' => $company_phone,
                'company_email' => $company_email,
                'tax_rate' => $tax_rate,
                'insurance_rate' => $insurance_rate,
                'working_hours' => $working_hours,
                'overtime_rate' => $overtime_rate,
                'attendance_start_time' => $attendance_start_time,
                'attendance_end_time' => $attendance_end_time,
                'late_threshold_minutes' => $late_threshold_minutes,
                'early_leave_threshold_minutes' => $early_leave_threshold_minutes,
                'default_currency' => $default_currency,
                'usd_exchange_rate' => $usd_exchange_rate
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$key, $value, $value]);
            }

            $_SESSION['success'] = "تم حفظ الإعدادات بنجاح";
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء حفظ الإعدادات";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Fetch current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات النظام - نظام إدارة الموارد البشرية</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">إعدادات النظام</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <h6 class="mb-3">معلومات الشركة</h6>
                            <div class="mb-3">
                                <label for="company_name" class="form-label">اسم الشركة</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="company_address" class="form-label">عنوان الشركة</label>
                                <textarea class="form-control" id="company_address" name="company_address" rows="2"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="company_phone" class="form-label">رقم الهاتف</label>
                                <input type="text" class="form-control" id="company_phone" name="company_phone" 
                                       value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="company_email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="company_email" name="company_email" 
                                       value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                            </div>

                            <h6 class="mb-3 mt-4">إعدادات العملات</h6>
                            <div class="mb-3">
                                <label for="default_currency" class="form-label">العملة الافتراضية</label>
                                <select class="form-select" id="default_currency" name="default_currency">
                                    <option value="IQD" <?php echo ($settings['default_currency'] ?? 'IQD') == 'IQD' ? 'selected' : ''; ?>>دينار عراقي</option>
                                    <option value="USD" <?php echo ($settings['default_currency'] ?? 'IQD') == 'USD' ? 'selected' : ''; ?>>دولار أمريكي</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="usd_exchange_rate" class="form-label">سعر صرف الدولار (بالدينار)</label>
                                <input type="number" class="form-control" id="usd_exchange_rate" name="usd_exchange_rate" 
                                       value="<?php echo htmlspecialchars($settings['usd_exchange_rate'] ?? 0); ?>" step="0.01" required>
                            </div>

                            <h6 class="mb-3 mt-4">إعدادات الرواتب</h6>
                            <div class="mb-3">
                                <label for="tax_rate" class="form-label">نسبة الضريبة (%)</label>
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                       value="<?php echo htmlspecialchars($settings['tax_rate'] ?? 0); ?>" step="0.01">
                            </div>

                            <div class="mb-3">
                                <label for="insurance_rate" class="form-label">نسبة التأمين (%)</label>
                                <input type="number" class="form-control" id="insurance_rate" name="insurance_rate" 
                                       value="<?php echo htmlspecialchars($settings['insurance_rate'] ?? 0); ?>" step="0.01">
                            </div>

                            <h6 class="mb-3 mt-4">إعدادات الحضور</h6>
                            <div class="mb-3">
                                <label for="working_hours" class="form-label">ساعات العمل اليومية</label>
                                <input type="number" class="form-control" id="working_hours" name="working_hours" 
                                       value="<?php echo htmlspecialchars($settings['working_hours'] ?? 8); ?>" min="1" max="24">
                            </div>

                            <div class="mb-3">
                                <label for="overtime_rate" class="form-label">معدل العمل الإضافي</label>
                                <input type="number" class="form-control" id="overtime_rate" name="overtime_rate" 
                                       value="<?php echo htmlspecialchars($settings['overtime_rate'] ?? 1.5); ?>" step="0.1" min="1">
                            </div>

                            <div class="mb-3">
                                <label for="attendance_start_time" class="form-label">وقت بداية الحضور</label>
                                <input type="time" class="form-control" id="attendance_start_time" name="attendance_start_time" 
                                       value="<?php echo htmlspecialchars($settings['attendance_start_time'] ?? '09:00'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="attendance_end_time" class="form-label">وقت نهاية الحضور</label>
                                <input type="time" class="form-control" id="attendance_end_time" name="attendance_end_time" 
                                       value="<?php echo htmlspecialchars($settings['attendance_end_time'] ?? '17:00'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="late_threshold_minutes" class="form-label">حد التأخير (بالدقائق)</label>
                                <input type="number" class="form-control" id="late_threshold_minutes" name="late_threshold_minutes" 
                                       value="<?php echo htmlspecialchars($settings['late_threshold_minutes'] ?? 15); ?>" min="0">
                            </div>

                            <div class="mb-3">
                                <label for="early_leave_threshold_minutes" class="form-label">حد المغادرة المبكرة (بالدقائق)</label>
                                <input type="number" class="form-control" id="early_leave_threshold_minutes" name="early_leave_threshold_minutes" 
                                       value="<?php echo htmlspecialchars($settings['early_leave_threshold_minutes'] ?? 15); ?>" min="0">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>