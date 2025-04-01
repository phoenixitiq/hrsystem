<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'عرض تفاصيل السلفة';
$current_page = 'loans';
require_once 'includes/header.php';

// Get loan ID
$loan_id = $_GET['id'] ?? null;

if (!$loan_id) {
    $_SESSION['error'] = "لم يتم تحديد السلفة";
    header("Location: loans.php");
    exit();
}

// Get loan data with employee and creator info
$stmt = $pdo->prepare("
    SELECT l.*, 
           e.first_name, e.last_name, e.employee_id as emp_id,
           u.username as created_by_name,
           u2.username as updated_by_name
    FROM loans l 
    JOIN employees e ON l.employee_id = e.id 
    LEFT JOIN users u ON l.created_by = u.id
    LEFT JOIN users u2 ON l.updated_by = u2.id
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    $_SESSION['error'] = "السلفة غير موجودة";
    header("Location: loans.php");
    exit();
}

// Calculate remaining amount
$total_paid = 0;
$remaining_amount = $loan['amount'];
$monthly_payment = $loan['monthly_payment'];
$total_payments = $loan['term_months'];
$completed_payments = 0;

if ($loan['status'] === 'approved' || $loan['status'] === 'completed') {
    // Get the number of months since the loan was approved
    $start_date = new DateTime($loan['created_at']);
    $current_date = new DateTime();
    $interval = $start_date->diff($current_date);
    $months_passed = ($interval->y * 12) + $interval->m;
    
    // Calculate completed payments
    $completed_payments = min($months_passed, $total_payments);
    $total_paid = $completed_payments * $monthly_payment;
    $remaining_amount = max(0, $loan['amount'] - $total_paid);
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i>
                            تفاصيل السلفة
                        </h5>
                        <div>
                            <?php if (hasPermission('admin')): ?>
                            <a href="loan_edit.php?id=<?php echo $loan['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <?php endif; ?>
                            <a href="loans.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> عودة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Loan Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">معلومات السلفة</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Employee Info -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">الموظف</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle fa-2x text-primary me-2"></i>
                                <div>
                                    <h6 class="mb-0">
                                        <a href="employee_view.php?id=<?php echo $loan['employee_id']; ?>">
                                            <?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']); ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($loan['emp_id']); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">الحالة</label>
                            <div>
                                <span class="badge bg-<?php echo getStatusColor($loan['status']); ?> fs-6">
                                    <?php echo getStatusText($loan['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">المبلغ</label>
                            <h5 class="mb-0"><?php echo formatCurrency($loan['amount']); ?></h5>
                        </div>

                        <!-- Interest Rate -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">نسبة الفائدة</label>
                            <h5 class="mb-0"><?php echo $loan['interest_rate']; ?>%</h5>
                        </div>

                        <!-- Term Months -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">مدة السداد</label>
                            <h5 class="mb-0"><?php echo $loan['term_months']; ?> شهر</h5>
                        </div>

                        <!-- Monthly Payment -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">القسط الشهري</label>
                            <h5 class="mb-0"><?php echo formatCurrency($loan['monthly_payment']); ?></h5>
                        </div>

                        <!-- Reason -->
                        <div class="col-12 mb-3">
                            <label class="form-label text-muted">سبب السلفة</label>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($loan['reason'])); ?></p>
                        </div>

                        <!-- Created Info -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">تاريخ الإنشاء</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock text-muted me-2"></i>
                                <div>
                                    <div><?php echo formatDateTime($loan['created_at']); ?></div>
                                    <small class="text-muted">
                                        بواسطة: <?php echo htmlspecialchars($loan['created_by_name']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Updated Info -->
                        <?php if ($loan['updated_at']): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">آخر تحديث</label>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-history text-muted me-2"></i>
                                <div>
                                    <div><?php echo formatDateTime($loan['updated_at']); ?></div>
                                    <small class="text-muted">
                                        بواسطة: <?php echo htmlspecialchars($loan['updated_by_name']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Progress -->
        <div class="col-md-4">
            <!-- Payment Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">ملخص السداد</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label text-muted">نسبة السداد</label>
                        <div class="progress" style="height: 20px;">
                            <?php $progress = ($completed_payments / $total_payments) * 100; ?>
                            <div class="progress-bar bg-success" 
                                 role="progressbar" 
                                 style="width: <?php echo $progress; ?>%"
                                 aria-valuenow="<?php echo $progress; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo number_format($progress, 1); ?>%
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $completed_payments; ?> من <?php echo $total_payments; ?> قسط
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted">المبلغ المدفوع</label>
                            <h5 class="text-success mb-0"><?php echo formatCurrency($total_paid); ?></h5>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label text-muted">المبلغ المتبقي</label>
                            <h5 class="text-danger mb-0"><?php echo formatCurrency($remaining_amount); ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Schedule -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">جدول السداد</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>القسط</th>
                                    <th>التاريخ</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $start_date = new DateTime($loan['created_at']);
                                for ($i = 1; $i <= $total_payments; $i++): 
                                    $payment_date = clone $start_date;
                                    $payment_date->modify("+$i months");
                                    $is_paid = $i <= $completed_payments;
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo formatDate($payment_date->format('Y-m-d')); ?></td>
                                    <td><?php echo formatCurrency($monthly_payment); ?></td>
                                    <td>
                                        <?php if ($is_paid): ?>
                                        <span class="badge bg-success">تم الدفع</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning">معلق</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 