<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'إدارة الديون';
$current_page = 'debts';
require_once 'includes/header.php';

// Handle delete request
if (isset($_POST['delete_debt']) && hasPermission('admin')) {
    $debt_id = $_POST['debt_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Delete the debt
        $stmt = $pdo->prepare("DELETE FROM debts WHERE id = ?");
        $stmt->execute([$debt_id]);
        
        $pdo->commit();
        $_SESSION['success'] = "تم حذف الدين بنجاح";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "حدث خطأ أثناء حذف الدين";
    }
    
    header("Location: debts.php");
    exit();
}

// Get search parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';

// Build the SQL query
$sql = "SELECT d.*, e.first_name, e.last_name, e.employee_id as emp_id 
        FROM debts d 
        JOIN employees e ON d.employee_id = e.id 
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_id LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($status) {
    $sql .= " AND d.status = ?";
    $params[] = $status;
}

if ($from_date) {
    $sql .= " AND d.created_at >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $sql .= " AND d.created_at <= ?";
    $params[] = $to_date . ' 23:59:59';
}

$sql .= " ORDER BY d.created_at DESC";

// Execute the query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$debts = $stmt->fetchAll();

// Calculate totals
$total_amount = array_sum(array_column($debts, 'amount'));
$total_paid = array_sum(array_map(function($debt) {
    return $debt['status'] === 'paid' ? $debt['amount'] : 0;
}, $debts));
$total_pending = array_sum(array_map(function($debt) {
    return $debt['status'] === 'pending' ? $debt['amount'] : 0;
}, $debts));
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
                            إدارة الديون
                        </h5>
                        <?php if (hasPermission('admin')): ?>
                        <a href="debt_add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> إضافة دين جديد
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">إجمالي الديون</h6>
                    <h3 class="mb-0"><?php echo formatCurrency($total_amount); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">الديون المدفوعة</h6>
                    <h3 class="mb-0"><?php echo formatCurrency($total_paid); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">الديون المعلقة</h6>
                    <h3 class="mb-0"><?php echo formatCurrency($total_pending); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="debts.php" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">بحث</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="اسم الموظف أو الرقم الوظيفي">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">الكل</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>معلق</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>مدفوع</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" 
                           value="<?php echo htmlspecialchars($from_date); ?>">
                </div>
                <div class="col-md-2">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="to_date" name="to_date" 
                           value="<?php echo htmlspecialchars($to_date); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Debts Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>الرقم الوظيفي</th>
                            <th>اسم الموظف</th>
                            <th>المبلغ</th>
                            <th>السبب</th>
                            <th>تاريخ الإنشاء</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($debts as $debt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($debt['emp_id']); ?></td>
                            <td>
                                <a href="employee_view.php?id=<?php echo $debt['employee_id']; ?>">
                                    <?php echo htmlspecialchars($debt['first_name'] . ' ' . $debt['last_name']); ?>
                                </a>
                            </td>
                            <td><?php echo formatCurrency($debt['amount']); ?></td>
                            <td><?php echo htmlspecialchars($debt['reason']); ?></td>
                            <td><?php echo formatDate($debt['created_at']); ?></td>
                            <td><?php echo formatDate($debt['due_date']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($debt['status']); ?>">
                                    <?php echo getStatusText($debt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="debt_view.php?id=<?php echo $debt['id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (hasPermission('admin')): ?>
                                    <a href="debt_edit.php?id=<?php echo $debt['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger" 
                                            onclick="confirmDelete(<?php echo $debt['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($debts)): ?>
                        <tr>
                            <td colspan="8" class="text-center">لا توجد ديون</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تأكيد الحذف</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف هذا الدين؟
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="debt_id" id="debtIdToDelete">
                    <input type="hidden" name="delete_debt" value="1">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">حذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(debtId) {
    document.getElementById('debtIdToDelete').value = debtId;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once 'includes/footer.php'; ?>