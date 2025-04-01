<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين الملفات المطلوبة
require_once 'includes/config.php';
require_once 'includes/csrf.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// التحقق من الصلاحيات
if (!checkPermission('manage_employees')) {
    header("Location: error.php?code=403");
    exit();
}

// تعيين عنوان الصفحة والصفحة الحالية
$page_title = 'إدارة الموظفين';
$current_page = 'employees';

// تضمين ملف الهيدر
require_once 'includes/header.php';

// معالجة معلمات البحث
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$position = $_GET['position'] ?? '';
$status = $_GET['status'] ?? '';

// بناء استعلام SQL
$sql = "SELECT e.*, d.name as department_name, p.name as position_name 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN positions p ON e.position_id = p.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($department) {
    $sql .= " AND e.department_id = ?";
    $params[] = $department;
}

if ($position) {
    $sql .= " AND e.position_id = ?";
    $params[] = $position;
}

if ($status) {
    $sql .= " AND e.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY e.created_at DESC";

// جلب البيانات
try {
    $stmt = executeQueryWithRetry($sql, $params);
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("خطأ في جلب بيانات الموظفين: " . $e->getMessage());
    $error = "عذراً، حدث خطأ في جلب البيانات";
}

// جلب الأقسام والمناصب للقائمة المنسدلة
try {
    $departments = executeQueryWithRetry("SELECT * FROM departments ORDER BY name")->fetchAll();
    $positions = executeQueryWithRetry("SELECT * FROM positions ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    error_log("خطأ في جلب بيانات الأقسام والمناصب: " . $e->getMessage());
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">قائمة الموظفين</h6>
                    <div>
                        <a href="employee_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة موظف جديد
                        </a>
                        <div class="btn-group ms-2">
                            <a href="export.php?type=excel&data_type=employees" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="export.php?type=pdf&data_type=employees" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <a href="export.php?type=csv&data_type=employees" class="btn btn-info">
                                <i class="fas fa-file-csv"></i> CSV
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <!-- نموذج البحث -->
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="بحث..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="department" class="form-select">
                                <option value="">كل الأقسام</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" 
                                            <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="position" class="form-select">
                                <option value="">كل المناصب</option>
                                <?php foreach ($positions as $pos): ?>
                                    <option value="<?php echo $pos['id']; ?>" 
                                            <?php echo $position == $pos['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pos['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">كل الحالات</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>نشط</option>
                                <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <a href="employees.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> إعادة تعيين
                            </a>
                        </div>
                    </form>

                    <!-- جدول الموظفين -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الرقم الوظيفي</th>
                                    <th>الاسم</th>
                                    <th>القسم</th>
                                    <th>المنصب</th>
                                    <th>تاريخ التعيين</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">لا يوجد موظفين</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($employee['employee_id']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['department_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($employee['position_name'] ?? '-'); ?></td>
                                            <td><?php echo formatArabicDate($employee['hire_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $employee['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo formatEmployeeStatus($employee['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="employee_view.php?id=<?php echo $employee['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="employee_edit.php?id=<?php echo $employee['id']; ?>" 
                                                       class="btn btn-sm btn-primary" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// تضمين ملف الفوتر
require_once 'includes/footer.php';
?>