<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'إضافة موظف جديد';
$current_page = 'employees';
require_once 'includes/header.php';

// Get departments for dropdown
$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
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
    if (empty($employee_id)) $errors[] = "الرقم الوظيفي مطلوب";
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

    // Check if employee_id already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "الرقم الوظيفي مستخدم مسبقاً";
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO employees (
                    employee_id, first_name, last_name, email, phone, 
                    department_id, position, hire_date, salary, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $employee_id, $first_name, $last_name, $email, $phone,
                $department_id, $position, $hire_date, $salary, $status
            ]);

            $_SESSION['success'] = "تم إضافة الموظف بنجاح";
            header("Location: employees.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "حدث خطأ أثناء إضافة الموظف";
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
                    <label for="employee_id" class="form-label">الرقم الوظيفي</label>
                    <input type="text" class="form-control" id="employee_id" name="employee_id" 
                           value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="department_id" class="form-label">القسم</label>
                    <select class="form-select" id="department_id" name="department_id" required>
                        <option value="">اختر القسم</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                <?php echo isset($_POST['department_id']) && $_POST['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="first_name" class="form-label">الاسم الأول</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="last_name" class="form-label">الاسم الأخير</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label">رقم الهاتف</label>
                    <input type="text" class="form-control" id="phone" name="phone" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>

                <div class="col-md-6">
                    <label for="position" class="form-label">المنصب</label>
                    <input type="text" class="form-control" id="position" name="position" 
                           value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="hire_date" class="form-label">تاريخ التعيين</label>
                    <input type="date" class="form-control" id="hire_date" name="hire_date" 
                           value="<?php echo isset($_POST['hire_date']) ? htmlspecialchars($_POST['hire_date']) : date('Y-m-d'); ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="salary" class="form-label">الراتب</label>
                    <input type="number" class="form-control" id="salary" name="salary" step="0.01" 
                           value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : ''; ?>>نشط</option>
                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>غير نشط</option>
                    </select>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
                    </button>
                    <a href="employees.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 