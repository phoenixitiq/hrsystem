<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'عرض الدين';
$current_page = 'debts';
require_once 'includes/header.php';

// Get debt ID
$debt_id = $_GET['id'] ?? null;
if (!$debt_id) {
    $_SESSION['error'] = "معرف الدين غير صالح";
    header("Location: debts.php");
    exit();
}

// Fetch debt data with employee and creator information
$stmt = $pdo->prepare("
    SELECT d.*, 
           e.first_name, e.last_name, e.employee_id as emp_id,
           u1.username as created_by_user,
           u2.username as updated_by_user
    FROM debts d 
    JOIN employees e ON d.employee_id = e.id 
    LEFT JOIN users u1 ON d.created_by = u1.id
    LEFT JOIN users u2 ON d.updated_by = u2.id
    WHERE d.id = ?
");
$stmt->execute([$debt_id]);
$debt = $stmt->fetch();

if (!$debt) {
    $_SESSION['error'] = "الدين غير موجود";
    header("Location: debts.php");
    exit();
}

// Calculate days until due
$due_date = new DateTime($debt['due_date']);
$today = new DateTime();
$days_until_due = $today->diff($due_date)->days;
$is_overdue = $today > $due_date && $debt['status'] === 'pending';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice-dollar"></i>
                            عرض الدين
                        </h5>
                        <div>
                            <?php if (hasPermission('admin')): ?>
                            <a href="debt_edit.php?id=<?php echo $debt['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <?php endif; ?>
                            <a href="debts.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-right"></i> عودة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Debt Details -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">تفاصيل الدين</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">الموظف</label>
                            <div>
                                <a href="employee_view.php?id=<?php echo $debt['employee_id']; ?>">
                                    <?php echo htmlspecialchars($debt['first_name'] . ' ' . $debt['last_name']); ?>
                                </a>
                                <small class="text-muted">(<?php echo htmlspecialchars($debt['emp_id']); ?>)</small>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">المبلغ</label>
                            <div class="h5 mb-0"><?php echo formatCurrency($debt['amount']); ?></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label text-muted">سبب الدين</label>
                            <div><?php echo nl2br(htmlspecialchars($debt['reason'])); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">تاريخ الإنشاء</label>
                            <div><?php echo formatDateTime($debt['created_at']); ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">تاريخ الاستحقاق</label>
                            <div>
                                <?php echo formatDate($debt['due_date']); ?>
                                <?php if ($is_overdue): ?>
                                    <span class="badge bg-danger">متأخر</span>
                                <?php elseif ($debt['status'] === 'pending'): ?>
                                    <small class="text-muted">
                                        (متبقي <?php echo $days_until_due; ?> يوم)
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted">الحالة</label>
                            <div>
                                <span class="badge bg-<?php echo getStatusColor($debt['status']); ?>">
                                    <?php echo getStatusText($debt['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Information -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">معلومات التدقيق</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">تم الإنشاء بواسطة</label>
                        <div>
                            <?php echo htmlspecialchars($debt['created_by_user']); ?>
                            <br>
                            <small class="text-muted">
                                <?php echo formatDateTime($debt['created_at']); ?>
                            </small>
                        </div>
                    </div>
                    <?php if ($debt['updated_by']): ?>
                    <div class="mb-3">
                        <label class="form-label text-muted">آخر تحديث بواسطة</label>
                        <div>
                            <?php echo htmlspecialchars($debt['updated_by_user']); ?>
                            <br>
                            <small class="text-muted">
                                <?php echo formatDateTime($debt['updated_at']); ?>
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 