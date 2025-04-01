<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'تعديل السلفة';
$current_page = 'loans';
require_once 'includes/header.php';

// Check if user has permission
if (!hasPermission('admin')) {
    $_SESSION['error'] = "ليس لديك صلاحية الوصول لهذه الصفحة";
    header("Location: loans.php");
    exit();
}

// Get loan ID
$loan_id = $_GET['id'] ?? null;

if (!$loan_id) {
    $_SESSION['error'] = "لم يتم تحديد السلفة";
    header("Location: loans.php");
    exit();
}

// Get loan data
$stmt = $pdo->prepare("
    SELECT l.*, e.first_name, e.last_name, e.employee_id as emp_id 
    FROM loans l 
    JOIN employees e ON l.employee_id = e.id 
    WHERE l.id = ?
");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();

if (!$loan) {
    $_SESSION['error'] = "السلفة غير موجودة";
    header("Location: loans.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $term_months = $_POST['term_months'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $status = $_POST['status'] ?? '';
    
    $errors = [];
    
    // Validate amount
    if (!$amount || $amount <= 0) {
        $errors[] = "يجب أن يكون المبلغ أكبر من صفر";
    }
    
    // Validate interest rate
    if ($interest_rate < 0) {
        $errors[] = "يجب أن تكون نسبة الفائدة 0% أو أكثر";
    }
    
    // Validate term months
    if (!$term_months || $term_months <= 0) {
        $errors[] = "يجب أن تكون مدة السلفة شهر واحد على الأقل";
    }
    
    // Validate reason
    if (empty($reason)) {
        $errors[] = "يجب إدخال سبب السلفة";
    }
    
    // Validate status
    if (empty($status)) {
        $errors[] = "يجب تحديد حالة السلفة";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Calculate monthly payment
            $monthly_interest = $interest_rate / 100 / 12;
            if ($monthly_interest > 0) {
                $monthly_payment = $amount * ($monthly_interest * pow(1 + $monthly_interest, $term_months)) / (pow(1 + $monthly_interest, $term_months) - 1);
            } else {
                $monthly_payment = $amount / $term_months;
            }
            
            // Update loan
            $stmt = $pdo->prepare("
                UPDATE loans 
                SET amount = ?, 
                    interest_rate = ?, 
                    term_months = ?, 
                    monthly_payment = ?, 
                    reason = ?, 
                    status = ?, 
                    updated_at = NOW(), 
                    updated_by = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $amount,
                $interest_rate,
                $term_months,
                $monthly_payment,
                $reason,
                $status,
                $_SESSION['user_id'],
                $loan_id
            ]);
            
            $pdo->commit();
            $_SESSION['success'] = "تم تحديث السلفة بنجاح";
            header("Location: loans.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "حدث خطأ أثناء تحديث السلفة";
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
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-edit"></i>
                            تعديل السلفة
                        </h5>
                        <a href="loans.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-right"></i> عودة
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loan Form -->
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">
                        <!-- Employee Info (Read Only) -->
                        <div class="col-md-12">
                            <label class="form-label">الموظف</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name'] . ' (' . $loan['emp_id'] . ')'); ?>" 
                                   readonly>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                       value="<?php echo htmlspecialchars($loan['amount']); ?>" 
                                       required>
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>

                        <!-- Interest Rate -->
                        <div class="col-md-6">
                            <label for="interest_rate" class="form-label">نسبة الفائدة</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" 
                                       value="<?php echo htmlspecialchars($loan['interest_rate']); ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Term Months -->
                        <div class="col-md-6">
                            <label for="term_months" class="form-label">مدة السداد <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="term_months" name="term_months" 
                                       value="<?php echo htmlspecialchars($loan['term_months']); ?>" 
                                       required>
                                <span class="input-group-text">شهر</span>
                            </div>
                        </div>

                        <!-- Monthly Payment Preview -->
                        <div class="col-md-6">
                            <label class="form-label">القسط الشهري (تقريبي)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="monthly_payment_preview" 
                                       value="<?php echo number_format($loan['monthly_payment'], 2); ?>" 
                                       readonly>
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="col-md-12">
                            <label for="reason" class="form-label">سبب السلفة <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo htmlspecialchars($loan['reason']); ?></textarea>
                        </div>

                        <!-- Status -->
                        <div class="col-md-12">
                            <label for="status" class="form-label">الحالة <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" <?php echo $loan['status'] === 'pending' ? 'selected' : ''; ?>>معلق</option>
                                <option value="approved" <?php echo $loan['status'] === 'approved' ? 'selected' : ''; ?>>موافق عليه</option>
                                <option value="rejected" <?php echo $loan['status'] === 'rejected' ? 'selected' : ''; ?>>مرفوض</option>
                                <option value="completed" <?php echo $loan['status'] === 'completed' ? 'selected' : ''; ?>>مكتمل</option>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ التغييرات
                            </button>
                            <a href="loans.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate monthly payment preview
function calculateMonthlyPayment() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
    const termMonths = parseInt(document.getElementById('term_months').value) || 0;
    
    if (amount > 0 && termMonths > 0) {
        const monthlyInterest = interestRate / 100 / 12;
        let monthlyPayment;
        
        if (monthlyInterest > 0) {
            monthlyPayment = amount * (monthlyInterest * Math.pow(1 + monthlyInterest, termMonths)) / (Math.pow(1 + monthlyInterest, termMonths) - 1);
        } else {
            monthlyPayment = amount / termMonths;
        }
        
        document.getElementById('monthly_payment_preview').value = monthlyPayment.toFixed(2);
    } else {
        document.getElementById('monthly_payment_preview').value = '';
    }
}

// Add event listeners
document.getElementById('amount').addEventListener('input', calculateMonthlyPayment);
document.getElementById('interest_rate').addEventListener('input', calculateMonthlyPayment);
document.getElementById('term_months').addEventListener('input', calculateMonthlyPayment);
</script>

<?php require_once 'includes/footer.php'; ?> 