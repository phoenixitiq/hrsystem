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
if (!checkPermission('manage_loans')) {
    header("Location: error.php?code=403");
    exit();
}

// تعيين عنوان الصفحة والصفحة الحالية
$page_title = 'إدارة القروض';
$current_page = 'loans';

// تضمين ملف الهيدر
require_once 'includes/header.php';

// معالجة معلمات البحث
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// بناء استعلام SQL
$sql = "SELECT l.*, e.first_name, e.last_name, e.employee_id 
        FROM loans l 
        JOIN employees e ON l.employee_id = e.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($type) {
    $sql .= " AND l.loan_type = ?";
    $params[] = $type;
}

if ($status) {
    $sql .= " AND l.status = ?";
    $params[] = $status;
}

if ($from_date) {
    $sql .= " AND l.created_at >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $sql .= " AND l.created_at <= ?";
    $params[] = $to_date . ' 23:59:59';
}

$sql .= " ORDER BY l.created_at DESC";

// جلب البيانات
try {
    $stmt = executeQueryWithRetry($sql, $params);
    $loans = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("خطأ في جلب بيانات القروض: " . $e->getMessage());
    $error = "عذراً، حدث خطأ في جلب البيانات";
}
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">قائمة القروض</h6>
                    <div>
                        <a href="loan_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة قرض جديد
                        </a>
                        <div class="btn-group ms-2">
                            <a href="export.php?type=excel&data_type=loans" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Excel
                            </a>
                            <a href="export.php?type=pdf&data_type=loans" class="btn btn-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            <a href="export.php?type=csv&data_type=loans" class="btn btn-info">
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
                            <select name="type" class="form-select">
                                <option value="">كل الأنواع</option>
                                <option value="personal" <?php echo $type == 'personal' ? 'selected' : ''; ?>>شخصي</option>
                                <option value="housing" <?php echo $type == 'housing' ? 'selected' : ''; ?>>سكني</option>
                                <option value="emergency" <?php echo $type == 'emergency' ? 'selected' : ''; ?>>طارئ</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">كل الحالات</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>معلق</option>
                                <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>موافق عليه</option>
                                <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>مرفوض</option>
                                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="from_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($from_date); ?>" placeholder="من تاريخ">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="to_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($to_date); ?>" placeholder="إلى تاريخ">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        <div class="col-md-1">
                            <a href="loans.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </form>

                    <!-- جدول القروض -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>الموظف</th>
                                    <th>نوع القرض</th>
                                    <th>المبلغ</th>
                                    <th>المبلغ المدفوع</th>
                                    <th>المبلغ المتبقي</th>
                                    <th>تاريخ البداية</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($loans)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">لا يوجد قروض</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($loan['loan_type']); ?></td>
                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                            <td><?php echo formatCurrency($loan['paid_amount']); ?></td>
                                            <td><?php echo formatCurrency($loan['remaining_amount']); ?></td>
                                            <td><?php echo formatArabicDate($loan['start_date']); ?></td>
                                            <td><?php echo formatArabicDate($loan['end_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $loan['status'] == 'pending' ? 'warning' : 
                                                        ($loan['status'] == 'approved' ? 'success' : 
                                                        ($loan['status'] == 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo formatLoanStatus($loan['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="loan_view.php?id=<?php echo $loan['id']; ?>" 
                                                       class="btn btn-sm btn-info" title="عرض">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="loan_edit.php?id=<?php echo $loan['id']; ?>" 
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