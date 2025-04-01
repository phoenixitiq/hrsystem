<?php
require_once 'includes/config.php';
requireLogin();

$page_title = 'إضافة سلفة جديدة';
$current_page = 'loans';
require_once 'includes/header.php';

// Check if user has permission
if (!hasPermission('admin')) {
    $_SESSION['error'] = "ليس لديك صلاحية الوصول لهذه الصفحة";
    header("Location: loans.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $interest_rate = $_POST['interest_rate'] ?? 0;
    $term_months = $_POST['term_months'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    $errors = [];
    
    // Validate employee
    if (!$employee_id) {
        $errors[] = "يجب اختيار الموظف";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ? AND status = 'active'");
        $stmt->execute([$employee_id]);
        if (!$stmt->fetch()) {
            $errors[] = "الموظف غير موجود أو غير نشط";
        }
    }
    
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
            
            // Insert loan
            $stmt = $pdo->prepare("
                INSERT INTO loans (
                    employee_id, amount, interest_rate, term_months, 
                    monthly_payment, reason, status, created_at, 
                    created_by
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, 'pending', NOW(), 
                    ?
                )
            ");
            
            $stmt->execute([
                $employee_id, $amount, $interest_rate, $term_months,
                $monthly_payment, $reason, $_SESSION['user_id']
            ]);
            
            $pdo->commit();
            $_SESSION['success'] = "تمت إضافة السلفة بنجاح";
            header("Location: loans.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "حدث خطأ أثناء إضافة السلفة";
        }
    }
}

// Get all active employees
$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, employee_id 
    FROM employees 
    WHERE status = 'active' 
    ORDER BY first_name, last_name
");
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-plus"></i>
                            إضافة سلفة جديدة
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
                        <!-- Employee Selection -->
                        <div class="col-md-12">
                            <label for="employee_id" class="form-label">الموظف <span class="text-danger">*</span></label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">اختر الموظف</option>
                                <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" 
                                        <?php echo isset($_POST['employee_id']) && $_POST['employee_id'] == $employee['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?> 
                                    (<?php echo htmlspecialchars($employee['employee_id']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label for="amount" class="form-label">المبلغ <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                                       value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>" 
                                       required>
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>

                        <!-- Interest Rate -->
                        <div class="col-md-6">
                            <label for="interest_rate" class="form-label">نسبة الفائدة</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="interest_rate" name="interest_rate" 
                                       value="<?php echo isset($_POST['interest_rate']) ? htmlspecialchars($_POST['interest_rate']) : '0'; ?>">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Term Months -->
                        <div class="col-md-6">
                            <label for="term_months" class="form-label">مدة السداد <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="term_months" name="term_months" 
                                       value="<?php echo isset($_POST['term_months']) ? htmlspecialchars($_POST['term_months']) : ''; ?>" 
                                       required>
                                <span class="input-group-text">شهر</span>
                            </div>
                        </div>

                        <!-- Monthly Payment Preview -->
                        <div class="col-md-6">
                            <label class="form-label">القسط الشهري (تقريبي)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="monthly_payment_preview" readonly>
                                <span class="input-group-text"><?php echo CURRENCY_SYMBOL; ?></span>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="col-md-12">
                            <label for="reason" class="form-label">سبب السلفة <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12">
                            <hr>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ
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