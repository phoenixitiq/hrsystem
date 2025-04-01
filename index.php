<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تعطيل عرض الأخطاء في بيئة الإنتاج
if (!isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'development') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// التحقق من وجود ملف الإعدادات
if (!file_exists('includes/config.php')) {
    die('خطأ: ملف الإعدادات غير موجود');
}

// تضمين ملف الإعدادات
require_once 'includes/config.php';

// تضمين ملف الوظائف المساعدة
require_once 'includes/functions.php';

// تضمين الملفات المطلوبة
require_once 'includes/cache.php';
require_once 'includes/csrf.php';

// التحقق من تسجيل الدخول وإعادة التوجيه إذا لم يكن مسجل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// التحقق من الصلاحيات
if (!hasPermission('view_dashboard')) {
    header("Location: error.php?code=403");
    exit();
}

// تعيين عنوان الصفحة والصفحة الحالية
$page_title = 'لوحة التحكم';
$current_page = 'dashboard';

// تضمين ملف الهيدر
require_once 'includes/header.php';

// إنشاء كائن التخزين المؤقت
try {
    $cache = new Cache();
} catch (Exception $e) {
    error_log("خطأ في تهيئة الكاش: " . $e->getMessage());
    $cache = null;
}

// جلب الإحصائيات من الكاش أو قاعدة البيانات
$stats = null;
$error = null;

try {
    if ($cache) {
        $stats = $cache->get('system_stats');
    }
    
    if (!$stats) {
        $stats = getSystemStats();
        if ($cache) {
            $cache->set('system_stats', $stats, 300);
        }
    }
} catch (Exception $e) {
    error_log("خطأ في جلب الإحصائيات: " . $e->getMessage());
    $error = "عذراً، حدث خطأ في جلب الإحصائيات";
}

// جلب آخر الموظفين المضافة
$latestEmployees = null;
try {
    if ($cache) {
        $latestEmployees = $cache->get('latest_employees');
    }
    
    if (!$latestEmployees) {
        $latestEmployees = executeQueryWithRetry(
            "SELECT e.*, d.name as department_name, p.name as position_name 
             FROM employees e 
             LEFT JOIN departments d ON e.department_id = d.id 
             LEFT JOIN positions p ON e.position_id = p.id 
             ORDER BY e.created_at DESC 
             LIMIT 5",
            [],
            'latest_employees'
        );
        if ($cache) {
            $cache->set('latest_employees', $latestEmployees, 300);
        }
    }
} catch (Exception $e) {
    error_log("خطأ في جلب آخر الموظفين: " . $e->getMessage());
    $error = "عذراً، حدث خطأ في جلب البيانات";
}

// جلب آخر القروض
$latestLoans = $cache->get('latest_loans');
if (!$latestLoans) {
    try {
        $latestLoans = executeQueryWithRetry(
            "SELECT l.*, e.name as employee_name 
             FROM loans l 
             JOIN employees e ON l.employee_id = e.id 
             ORDER BY l.created_at DESC 
             LIMIT 5",
            [],
            'latest_loans'
        );
        // تخزين النتائج في الكاش لمدة 5 دقائق
        $cache->set('latest_loans', $latestLoans, 300);
    } catch (Exception $e) {
        error_log("خطأ في جلب آخر القروض: " . $e->getMessage());
        $error = "عذراً، حدث خطأ في جلب البيانات";
    }
}

// جلب آخر الديون
$latestDebts = $cache->get('latest_debts');
if (!$latestDebts) {
    try {
        $latestDebts = executeQueryWithRetry(
            "SELECT d.*, e.name as employee_name 
             FROM debts d 
             JOIN employees e ON d.employee_id = e.id 
             ORDER BY d.created_at DESC 
             LIMIT 5",
            [],
            'latest_debts'
        );
        // تخزين النتائج في الكاش لمدة 5 دقائق
        $cache->set('latest_debts', $latestDebts, 300);
    } catch (Exception $e) {
        error_log("خطأ في جلب آخر الديون: " . $e->getMessage());
        $error = "عذراً، حدث خطأ في جلب البيانات";
    }
}

// جلب إحصائيات الكاش
$cacheStats = $cache->getStats();
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- إحصائيات سريعة -->
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">الموظفين النشطين</p>
                                <h5 class="font-weight-bolder">
                                    <?php echo $stats['employees'][0]['count'] ?? 0; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-primary shadow-primary text-center rounded-circle">
                                <i class="fas fa-users text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">القروض المعلقة</p>
                                <h5 class="font-weight-bolder">
                                    <?php echo $stats['loans'][0]['count'] ?? 0; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                <i class="fas fa-money-bill-wave text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">الديون النشطة</p>
                                <h5 class="font-weight-bolder">
                                    <?php echo $stats['debts'][0]['count'] ?? 0; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-warning shadow-warning text-center rounded-circle">
                                <i class="fas fa-exclamation-triangle text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">الرواتب المعلقة</p>
                                <h5 class="font-weight-bolder">
                                    <?php echo $stats['salaries'][0]['count'] ?? 0; ?>
                                </h5>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle">
                                <i class="fas fa-wallet text-lg opacity-10"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- آخر الموظفين -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>آخر الموظفين المضافة</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($latestEmployees)): ?>
                        <p class="text-center">لا يوجد موظفين</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>الموظف</th>
                                        <th>القسم</th>
                                        <th>المنصب</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestEmployees as $employee): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['department_name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['position_name']); ?></td>
                                            <td>
                                                <a href="employee_view.php?id=<?php echo $employee['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- آخر القروض -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>آخر القروض</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($latestLoans)): ?>
                        <p class="text-center">لا يوجد قروض</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>الموظف</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestLoans as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['employee_name']); ?></td>
                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $loan['status'] == 'pending' ? 'warning' : 
                                                        ($loan['status'] == 'approved' ? 'success' : 
                                                        ($loan['status'] == 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo $loan['status'] == 'pending' ? 'معلق' : 
                                                        ($loan['status'] == 'approved' ? 'معتمد' : 
                                                        ($loan['status'] == 'completed' ? 'مكتمل' : 'ملغي')); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="loan_view.php?id=<?php echo $loan['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- آخر الديون -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>آخر الديون</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($latestDebts)): ?>
                        <p class="text-center">لا يوجد ديون</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>الموظف</th>
                                        <th>المبلغ</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestDebts as $debt): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($debt['employee_name']); ?></td>
                                            <td><?php echo formatCurrency($debt['amount']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $debt['status'] == 'active' ? 'warning' : 
                                                        ($debt['status'] == 'paid' ? 'success' : 'danger'); 
                                                ?>">
                                                    <?php echo $debt['status'] == 'active' ? 'نشط' : 
                                                        ($debt['status'] == 'paid' ? 'مدفوع' : 'ملغي'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="debt_view.php?id=<?php echo $debt['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (hasPermission('manage_settings')): ?>
<!-- إحصائيات الكاش -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">إحصائيات التخزين المؤقت</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-box">
                            <h6>حجم الكاش</h6>
                            <p><?php echo formatBytes($cacheStats['total_size']); ?> / <?php echo formatBytes($cacheStats['max_size']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <h6>عدد الملفات</h6>
                            <p><?php echo number_format($cacheStats['total_files']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <h6>الملفات منتهية الصلاحية</h6>
                            <p><?php echo number_format($cacheStats['expired_files']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-box">
                            <h6>حالة الضغط</h6>
                            <p><?php echo $cacheStats['compression'] ? 'مفعل' : 'معطل'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <form method="post" action="clear_cache.php" class="d-inline">
                            <?php echo getCsrfInput(); ?>
                            <button type="submit" class="btn btn-warning btn-sm">
                                <i class="fas fa-broom"></i> تنظيف الكاش
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// تضمين ملف الفوتر
require_once 'includes/footer.php';
?>
