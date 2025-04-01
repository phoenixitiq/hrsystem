<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'تعديل الدين';
$current_page = 'debts';
require_once 'includes/header.php';

// Check if user has permission
if (!hasPermission('admin')) {
    header("Location: debts.php");
    exit();
}

// Get debt ID
$debt_id = $_GET['id'] ?? null;
if (!$debt_id) {
    $_SESSION['error'] = "معرف الدين غير صالح";
    header("Location: debts.php");
    exit();
}

// Fetch debt data
$stmt = $pdo->prepare("
    SELECT d.*, e.first_name, e.last_name, e.employee_id as emp_id 
    FROM debts d 
    JOIN employees e ON d.employee_id = e.id 
    WHERE d.id = ?
");
$stmt->execute([$debt_id]);
$debt = $stmt->fetch();

if (!$debt) {
    $_SESSION['error'] = "الدين غير موجود";
    header("Location: debts.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    
    $errors = [];
    
    // Validate input
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = "يرجى إدخال مبلغ صحيح";
    }
    
    if (empty($reason)) {
        $errors[] = "يرجى إدخال سبب الدين";
    }
    
    if (empty($due_date)) {
        $errors[] = "يرجى إدخال تاريخ الاستحقاق";
    }
    
    // If no errors, update the debt
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                UPDATE debts 
                SET amount = ?, reason = ?, due_date = ?, status = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $amount,
                $reason,
                $due_date,
                $status,
                $_SESSION['user_id'],
                $debt_id
            ]);
            
            $pdo->commit();
            $_SESSION['success'] = "تم تحديث الدين بنجاح";
            header("Location: debts.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "حدث خطأ أثناء تحديث الدين";
        }
    }
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i>
                        تعديل الدين
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Edit Debt Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">الموظف</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($debt['first_name'] . ' ' . $debt['last_name'] . ' (' . $debt['emp_id'] . ')'); ?>" 
                           readonly>
                </div>

                <div class="col-md-6">
                    <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="amount" name="amount" 
                               value="<?php echo htmlspecialchars($debt['amount']); ?>" 
                               step="0.01" min="0" required>
                        <span class="input-group-text">د.ع</span>
                    </div>
                </div>

                <div class="col-md-12">
                    <label for="reason" class="form-label">سبب الدين <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo htmlspecialchars($debt['reason']); ?></textarea>
                </div>

                <div class="col-md-6">
                    <label for="due_date" class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="due_date" name="due_date" 
                           value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($debt['due_date']))); ?>" 
                           required>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?php echo $debt['status'] === 'pending' ? 'selected' : ''; ?>>معلق</option>
                        <option value="paid" <?php echo $debt['status'] === 'paid' ? 'selected' : ''; ?>>مدفوع</option>
                        <option value="cancelled" <?php echo $debt['status'] === 'cancelled' ? 'selected' : ''; ?>>ملغي</option>
                    </select>
                </div>

                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                    <a href="debts.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 