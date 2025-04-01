<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'إضافة دين جديد';
$current_page = 'debts';
require_once 'includes/header.php';

// Check if user has permission
if (!hasPermission('admin')) {
    header("Location: debts.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    
    $errors = [];
    
    // Validate input
    if (empty($employee_id)) {
        $errors[] = "يرجى اختيار الموظف";
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = "يرجى إدخال مبلغ صحيح";
    }
    
    if (empty($reason)) {
        $errors[] = "يرجى إدخال سبب الدين";
    }
    
    if (empty($due_date)) {
        $errors[] = "يرجى إدخال تاريخ الاستحقاق";
    }
    
    // If no errors, insert the debt
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("
                INSERT INTO debts (employee_id, amount, reason, due_date, status, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $employee_id,
                $amount,
                $reason,
                $due_date,
                $status,
                $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            $_SESSION['success'] = "تم إضافة الدين بنجاح";
            header("Location: debts.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "حدث خطأ أثناء إضافة الدين";
        }
    }
}

// Fetch active employees for dropdown
$stmt = $pdo->query("
    SELECT id, employee_id, first_name, last_name 
    FROM employees 
    WHERE status = 'active' 
    ORDER BY first_name, last_name
");
$employees = $stmt->fetchAll();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="mb-0">
                        <i class="fas fa-plus"></i>
                        إضافة دين جديد
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

    <!-- Add Debt Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label for="employee_id" class="form-label">الموظف <span class="text-danger">*</span></label>
                    <select class="form-select" id="employee_id" name="employee_id" required>
                        <option value="">اختر الموظف</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>" 
                                    <?php echo isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name'] . ' (' . $employee['employee_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="amount" name="amount" 
                               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>" 
                               step="0.01" min="0" required>
                        <span class="input-group-text">د.ع</span>
                    </div>
                </div>

                <div class="col-md-12">
                    <label for="reason" class="form-label">سبب الدين <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                </div>

                <div class="col-md-6">
                    <label for="due_date" class="form-label">تاريخ الاستحقاق <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="due_date" name="due_date" 
                           value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>" 
                           required>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label">الحالة</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'pending') ? 'selected' : ''; ?>>معلق</option>
                        <option value="paid" <?php echo (isset($_POST['status']) && $_POST['status'] === 'paid') ? 'selected' : ''; ?>>مدفوع</option>
                        <option value="cancelled" <?php echo (isset($_POST['status']) && $_POST['status'] === 'cancelled') ? 'selected' : ''; ?>>ملغي</option>
                    </select>
                </div>

                <div class="col-12">
                    <hr>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ
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