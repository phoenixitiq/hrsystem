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
if (!checkPermission('manage_salaries')) {
    header("Location: error.php?code=403");
    exit();
}

// تعيين عنوان الصفحة والصفحة الحالية
$page_title = 'إدارة الرواتب';
$current_page = 'salary';

// تضمين ملف الهيدر
require_once 'includes/header.php';

// معالجة معلمات البحث
$search = $_GET['search'] ?? '';
$month = $_GET['month'] ?? date('Y-m');
$status = $_GET['status'] ?? '';

// بناء استعلام SQL
$sql = "SELECT s.*, e.first_name, e.last_name, e.employee_id 
        FROM salaries s 
        JOIN employees e ON s.employee_id = e.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($month) {
    $sql .= " AND DATE_FORMAT(s.salary_month, '%Y-%m') = ?";
    $params[] = $month;
}

if ($status) {
    $sql .= " AND s.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY s.created_at DESC";

// جلب البيانات
try {
    $stmt = executeQueryWithRetry($sql, $params);
    $salaries = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("خطأ في جلب بيانات الرواتب: " . $e->getMessage());
    $error = "عذراً، حدث خطأ في جلب البيانات";
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">قائمة الرواتب</h6>
                    <div>
                        <a href="salary_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة راتب جديد
                        </a>
                        <div class="btn-group ms-2">
                            <a href="export.php?type=excel&data_type=salaries" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="export.php?type=pdf&data_type=salaries" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <a href="export.php?type=csv&data_type=salaries" class="btn btn-info">
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
                            <input type="month" name="month" class="form-control" 
                                   value="<?php echo htmlspecialchars($month); ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">كل الحالات</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>معلق</option>
                                <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>مدفوع</option>
                                <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> بحث
                            </button>
                            <a href="salary.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> إعادة تعيين
                            </a>
                        </div>
                    </form>

                    <!-- جدول الرواتب -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>شهر الراتب</th>
                                    <th>الراتب الأساسي</th>
                                    <th>البدلات</th>
                                    <th>الخصومات</th>
                                    <th>الصافي</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salaries)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">لا يوجد رواتب</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($salary['first_name'] . ' ' . $salary['last_name']); ?></td>
                                            <td><?php echo formatArabicDate($salary['salary_month']); ?></td>
                                            <td><?php echo formatCurrency($salary['basic_salary']); ?></td>
                                            <td><?php echo formatCurrency($salary['allowances']); ?></td>
                                            <td><?php echo formatCurrency($salary['deductions']); ?></td>
                                            <td><?php echo formatCurrency($salary['net_salary']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $salary['status'] == 'pending' ? 'warning' : 
                                                        ($salary['status'] == 'paid' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo $salary['status'] == 'pending' ? 'معلق' : 
                                                          ($salary['status'] == 'paid' ? 'مدفوع' : 'ملغي'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="salary_view.php?id=<?php echo $salary['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="salary_edit.php?id=<?php echo $salary['id']; ?>" 
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