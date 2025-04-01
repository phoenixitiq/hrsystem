<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'تعديل بيانات الموظف';
$current_page = 'employees';
require_once 'includes/header.php';

// Get employee ID from URL
$employee_id = $_GET['id'] ?? null;

if (!$employee_id) {
    $_SESSION['error'] = "لم يتم تحديد الموظف";
    header("Location: employees.php");
    exit();
}

// Get departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

// Get employee data
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    $_SESSION['error'] = "الموظف غير موجود";
    header("Location: employees.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department_id = $_POST['department_id'] ?? '';
    $position = $_POST['position'] ?? '';
    $hire_date = $_POST['hire_date'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $status = $_POST['status'] ?? 'active';

    $errors = [];

    // Validate required fields
    if (empty($first_name)) $errors[] = "الاسم الأول مطلوب";
    if (empty($last_name)) $errors[] = "الاسم الأخير مطلوب";
    if (empty($department_id)) $errors[] = "القسم مطلوب";
    if (empty($position)) $errors[] = "المنصب مطلوب";
    if (empty($hire_date)) $errors[] = "تاريخ التعيين مطلوب";
    if (empty($salary)) $errors[] = "الراتب مطلوب";

    // Validate email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "صيغة البريد الإلكتروني غير صحيحة";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE employees SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    department_id = ?, 
                    position = ?, 
                    hire_date = ?, 
                    salary = ?, 
                    status = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $first_name, 
                $last_name, 
                $email, 
                $phone, 
                $department_id, 
                $position, 
                $hire_date, 
                $salary, 
                $status,
                $employee_id
            ]);

            $_SESSION['success'] = "تم تحديث بيانات الموظف بنجاح";
            header("Location: employees.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء تحديث بيانات الموظف";
        }
    }
}
?>

<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الرقم الوظيفي</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label for="department_id" class="form-label">القسم</label>
                    <select class="form-select" id="department_id" name="department_id" required>
                        <option value="">اختر القسم</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                <?php echo $employee['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="first_name" class="form-label">الاسم الأول</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="last_name" class="form-label">الاسم الأخير</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo htmlspecialchars($employee['email']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">رقم الهاتف</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($employee['phone']); ?>">
                </div>

                <div class="col-md-6">
                    <label for="position" class="form-label">المنصب</label>
                    <input type="text" class="form-control" id="position" name="position" 
                           value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="hire_date" class="form-label">تاريخ التعيين</label>
                    <input type="date" class="form-control" id="hire_date" name="hire_date" 
                           value="<?php echo htmlspecialchars($employee['hire_date']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="salary" class="form-label">الراتب</label>
                    <input type="number" class="form-control" id="salary" name="salary" step="0.01" 
                           value="<?php echo htmlspecialchars($employee['salary']); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                    <a href="employees.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

    $required_fields = ['employee_id', 'first_name', 'last_name', 'department', 'position', 'hire_date', 'salary'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = 'الرجاء إدخال جميع البيانات المطلوبة';
    } else {
        try {
            // Check if employee ID already exists (excluding current employee)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ? AND id != ?");
            $stmt->execute([$_POST['employee_id'], $employee_id]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'رقم الموظف موجود مسبقاً';
            } else {
                // Update employee
                $stmt = $pdo->prepare("
                    UPDATE employees SET 
                        employee_id = ?,
                        first_name = ?,
                        last_name = ?,
                        email = ?,
                        phone = ?,
                        address = ?,
                        department = ?,
                        position = ?,
                        hire_date = ?,
                        salary = ?,
                        status = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $_POST['employee_id'],
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['phone'],
                    $_POST['address'],
                    $_POST['department'],
                    $_POST['position'],
                    $_POST['hire_date'],
                    $_POST['salary'],
                    $_POST['status'] ?? 'active',
                    $employee_id
                ]);
                
                $success = 'تم تحديث بيانات الموظف بنجاح';
                
                // Refresh employee data
                $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
                $stmt->execute([$employee_id]);
                $employee = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء تحديث بيانات الموظف';
        }
    }
}

// Get unique departments for dropdown
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - تعديل بيانات الموظف</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 15px 20px;
            margin: 5px 0;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link.active {
            background: #3498db;
            color: white;
        }
        .sidebar .nav-link i {
            margin-left: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,.05);
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="p-3">
                    <h4 class="text-center mb-4"><?php echo SITE_NAME; ?></h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-home"></i> لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="employees.php">
                                <i class="fas fa-users"></i> الموظفين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="salary.php">
                                <i class="fas fa-money-bill-wave"></i> الرواتب
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="loans.php">
                                <i class="fas fa-hand-holding-usd"></i> السلف
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="debts.php">
                                <i class="fas fa-file-invoice-dollar"></i> الديون
                            </a>
                        </li>
                        <?php if (hasPermission('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-user-cog"></i> المستخدمين
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php">
                                <i class="fas fa-cog"></i> الإعدادات
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>تعديل بيانات الموظف</h2>
                    <div>
                        <span class="text-muted">مرحباً، <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="employee_id" class="form-label">رقم الموظف</label>
                                    <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo htmlspecialchars($employee['employee_id']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">الاسم الأول</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">الاسم الأخير</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($employee['phone']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">القسم</label>
                                    <input type="text" class="form-control" id="department" name="department" list="departments" value="<?php echo htmlspecialchars($employee['department']); ?>" required>
                                    <datalist id="departments">
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="position" class="form-label">المنصب</label>
                                    <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="hire_date" class="form-label">تاريخ التعيين</label>
                                    <input type="date" class="form-control" id="hire_date" name="hire_date" value="<?php echo $employee['hire_date']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salary" class="form-label">الراتب</label>
                                    <input type="number" class="form-control" id="salary" name="salary" step="0.01" value="<?php echo $employee['salary']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">الحالة</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>نشط</option>
                                        <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                                        <option value="on_leave" <?php echo $employee['status'] === 'on_leave' ? 'selected' : ''; ?>>في إجازة</option>
                                    </select>
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">العنوان</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($employee['address']); ?></textarea>
                                </div>
                            </div>

                            <div class="text-end">
                                <a href="employees.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 