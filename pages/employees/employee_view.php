<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'عرض بيانات الموظف';
$current_page = 'employees';
require_once 'includes/header.php';

// Get employee ID from URL
$employee_id = $_GET['id'] ?? null;

if (!$employee_id) {
    $_SESSION['error'] = "لم يتم تحديد الموظف";
    header("Location: employees.php");
    exit();
}

// Get employee data with department name
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    WHERE e.id = ?
");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    $_SESSION['error'] = "الموظف غير موجود";
    header("Location: employees.php");
    exit();
}

// Get employee salaries
$stmt = $pdo->prepare("
    SELECT * FROM salaries 
    WHERE employee_id = ? 
    ORDER BY payment_date DESC
");
$stmt->execute([$employee_id]);
$salaries = $stmt->fetchAll();

// Get employee loans
$stmt = $pdo->prepare("
    SELECT * FROM loans 
    WHERE employee_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$employee_id]);
$loans = $stmt->fetchAll();

// Get employee debts
$stmt = $pdo->prepare("
    SELECT * FROM debts 
    WHERE employee_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$employee_id]);
$debts = $stmt->fetchAll();

// Calculate totals
$total_salary = array_sum(array_column($salaries, 'net_salary'));
$total_loans = array_sum(array_column($loans, 'amount'));
$total_debts = array_sum(array_column($debts, 'amount'));
?>

<div class="container-fluid">
    <!-- Employee Info Card -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">المعلومات الأساسية</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                        <h4 class="mt-3"><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($employee['position']); ?></p>
                        <span class="badge bg-<?php echo $employee['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo $employee['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                        </span>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            الرقم الوظيفي
                            <span class="text-primary"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            القسم
                            <span class="text-primary"><?php echo htmlspecialchars($employee['department_name']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            تاريخ التعيين
                            <span class="text-primary"><?php echo formatDate($employee['hire_date']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            الراتب الأساسي
                            <span class="text-primary"><?php echo formatCurrency($employee['salary']); ?></span>
                        </li>
                    </ul>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <?php if (hasPermission('admin')): ?>
                        <a href="employee_edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> تعديل
                        </a>
                        <?php endif; ?>
                        <a href="employees.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> عودة
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Info Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">معلومات الاتصال</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if ($employee['email']): ?>
                        <li class="list-group-item">
                            <i class="fas fa-envelope text-primary me-2"></i>
                            <?php echo htmlspecialchars($employee['email']); ?>
                        </li>
                        <?php endif; ?>
                        <?php if ($employee['phone']): ?>
                        <li class="list-group-item">
                            <i class="fas fa-phone text-primary me-2"></i>
                            <?php echo htmlspecialchars($employee['phone']); ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Financial Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title">إجمالي الرواتب</h6>
                            <h3 class="mb-0"><?php echo formatCurrency($total_salary); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title">إجمالي السلف</h6>
                            <h3 class="mb-0"><?php echo formatCurrency($total_loans); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title">إجمالي الديون</h6>
                            <h3 class="mb-0"><?php echo formatCurrency($total_debts); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="salaries-tab" data-bs-toggle="tab" href="#salaries" role="tab">
                                <i class="fas fa-money-bill-wave me-2"></i>الرواتب
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="loans-tab" data-bs-toggle="tab" href="#loans" role="tab">
                                <i class="fas fa-hand-holding-usd me-2"></i>السلف
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="debts-tab" data-bs-toggle="tab" href="#debts" role="tab">
                                <i class="fas fa-file-invoice-dollar me-2"></i>الديون
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-4" id="employeeTabsContent">
                        <!-- Salaries Tab -->
                        <div class="tab-pane fade show active" id="salaries" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الراتب الأساسي</th>
                                            <th>الخصومات</th>
                                            <th>الإضافات</th>
                                            <th>صافي الراتب</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td><?php echo formatDate($salary['payment_date']); ?></td>
                                            <td><?php echo formatCurrency($salary['base_salary']); ?></td>
                                            <td><?php echo formatCurrency($salary['deductions']); ?></td>
                                            <td><?php echo formatCurrency($salary['additions']); ?></td>
                                            <td><?php echo formatCurrency($salary['net_salary']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $salary['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo $salary['payment_status'] === 'paid' ? 'مدفوع' : 'معلق'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($salaries)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">لا توجد رواتب</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Loans Tab -->
                        <div class="tab-pane fade" id="loans" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>المبلغ</th>
                                            <th>نسبة الفائدة</th>
                                            <th>المدة (شهر)</th>
                                            <th>القسط الشهري</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?php echo formatDate($loan['created_at']); ?></td>
                                            <td><?php echo formatCurrency($loan['amount']); ?></td>
                                            <td><?php echo $loan['interest_rate']; ?>%</td>
                                            <td><?php echo $loan['term_months']; ?></td>
                                            <td><?php echo formatCurrency($loan['monthly_payment']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($loan['status']); ?>">
                                                    <?php echo getStatusText($loan['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($loans)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">لا توجد سلف</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Debts Tab -->
                        <div class="tab-pane fade" id="debts" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>المبلغ</th>
                                            <th>السبب</th>
                                            <th>تاريخ الاستحقاق</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($debts as $debt): ?>
                                        <tr>
                                            <td><?php echo formatDate($debt['created_at']); ?></td>
                                            <td><?php echo formatCurrency($debt['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($debt['reason']); ?></td>
                                            <td><?php echo formatDate($debt['due_date']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStatusColor($debt['status']); ?>">
                                                    <?php echo getStatusText($debt['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($debts)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">لا توجد ديون</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 