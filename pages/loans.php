<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين الملفات المطلوبة
require_once '../includes/config.php';
require_once '../includes/functions.php';

// التحقق من تسجيل الدخول
requireLogin();

// التحقق من الصلاحيات
if (!hasPermission('view_loans')) {
    setAlert('error', 'ليس لديك صلاحية للوصول إلى هذه الصفحة');
    redirect('index.php');
}

// معالجة الطلبات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('error', 'رمز التحقق غير صالح');
    } else {
        $action = $_POST['action'] ?? '';
        $loan_id = $_POST['loan_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $amount = $_POST['amount'] ?? 0;
                    $interest_rate = $_POST['interest_rate'] ?? 0;
                    $term = $_POST['term'] ?? 0;
                    $description = $_POST['description'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($amount) || empty($term)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->insert(
                            'loans',
                            [
                                'employee_id' => $employee_id,
                                'amount' => $amount,
                                'interest_rate' => $interest_rate,
                                'term' => $term,
                                'description' => $description,
                                'status' => $status,
                                'created_by' => $_SESSION['user_id'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        setAlert('success', 'تم إضافة القرض بنجاح');
                        logActivity($_SESSION['user_id'], 'loan', 'تم إضافة قرض جديد');
                    }
                    break;

                case 'update':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $amount = $_POST['amount'] ?? 0;
                    $interest_rate = $_POST['interest_rate'] ?? 0;
                    $term = $_POST['term'] ?? 0;
                    $description = $_POST['description'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($amount) || empty($term)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->update(
                            'loans',
                            [
                                'employee_id' => $employee_id,
                                'amount' => $amount,
                                'interest_rate' => $interest_rate,
                                'term' => $term,
                                'description' => $description,
                                'status' => $status,
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$loan_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث القرض بنجاح');
                        logActivity($_SESSION['user_id'], 'loan', 'تم تحديث قرض');
                    }
                    break;

                case 'delete':
                    if ($loan_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'loans',
                            'id = ? AND created_by = ?',
                            [$loan_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف القرض بنجاح');
                        logActivity($_SESSION['user_id'], 'loan', 'تم حذف قرض');
                    }
                    break;

                case 'approve':
                    if ($loan_id) {
                        $db = Database::getInstance();
                        $db->update(
                            'loans',
                            [
                                'status' => 'approved',
                                'approved_by' => $_SESSION['user_id'],
                                'approved_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$loan_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم الموافقة على القرض بنجاح');
                        logActivity($_SESSION['user_id'], 'loan', 'تم الموافقة على قرض');
                    }
                    break;

                case 'reject':
                    $rejection_reason = $_POST['rejection_reason'] ?? '';
                    if ($loan_id && !empty($rejection_reason)) {
                        $db = Database::getInstance();
                        $db->update(
                            'loans',
                            [
                                'status' => 'rejected',
                                'rejection_reason' => $rejection_reason,
                                'rejected_by' => $_SESSION['user_id'],
                                'rejected_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$loan_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم رفض القرض بنجاح');
                        logActivity($_SESSION['user_id'], 'loan', 'تم رفض قرض');
                    } else {
                        setAlert('error', 'يرجى إدخال سبب الرفض');
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة القروض: " . $e->getMessage(), 'error');
        }
    }
}

// جلب القروض
try {
    $db = Database::getInstance();
    $loans = $db->fetchAll(
        "SELECT l.*, e.name as employee_name 
         FROM loans l 
         JOIN employees e ON l.employee_id = e.id 
         WHERE l.created_by = ? 
         ORDER BY l.created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب القروض');
    logEvent("خطأ في جلب القروض: " . $e->getMessage(), 'error');
    $loans = [];
}

// جلب الموظفين النشطين
try {
    $db = Database::getInstance();
    $employees = $db->fetchAll(
        "SELECT id, name FROM employees WHERE status = 'active' ORDER BY name"
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب الموظفين');
    logEvent("خطأ في جلب الموظفين: " . $e->getMessage(), 'error');
    $employees = [];
}

// تعيين عنوان الصفحة
$page_title = 'القروض';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">القروض</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addLoanModal">
                            <i class="fas fa-plus"></i> إضافة قرض جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($loans)): ?>
                        <div class="alert alert-info">لا توجد قروض</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>المبلغ</th>
                                        <th>معدل الفائدة</th>
                                        <th>المدة (بالأشهر)</th>
                                        <th>الوصف</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loans as $loan): ?>
                                        <tr>
                                            <td><?php echo $loan['id']; ?></td>
                                            <td><?php echo htmlspecialchars($loan['employee_name']); ?></td>
                                            <td><?php echo formatNumber($loan['amount']); ?></td>
                                            <td><?php echo formatNumber($loan['interest_rate'], 2); ?>%</td>
                                            <td><?php echo $loan['term']; ?></td>
                                            <td><?php echo htmlspecialchars($loan['description']); ?></td>
                                            <td>
                                                <?php
                                                switch ($loan['status']) {
                                                    case 'pending':
                                                        echo '<span class="badge badge-warning">قيد الانتظار</span>';
                                                        break;
                                                    case 'approved':
                                                        echo '<span class="badge badge-success">تمت الموافقة</span>';
                                                        break;
                                                    case 'rejected':
                                                        echo '<span class="badge badge-danger">مرفوض</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($loan['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="approveLoan(<?php echo $loan['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="rejectLoan(<?php echo $loan['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editLoan(<?php echo $loan['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteLoan(<?php echo $loan['id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

<!-- Modal إضافة قرض -->
<div class="modal fade" id="addLoanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة قرض جديد</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>الموظف</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">اختر الموظف</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>المبلغ</label>
                        <input type="number" name="amount" class="form-control" required min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>معدل الفائدة (%)</label>
                        <input type="number" name="interest_rate" class="form-control" required min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>المدة (بالأشهر)</label>
                        <input type="number" name="term" class="form-control" required min="1">
                    </div>

                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" class="form-control" required>
                            <option value="pending">قيد الانتظار</option>
                            <option value="approved">تمت الموافقة</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعديل قرض -->
<div class="modal fade" id="editLoanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل قرض</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="loan_id" id="edit_loan_id">
                    
                    <div class="form-group">
                        <label>الموظف</label>
                        <select name="employee_id" id="edit_employee_id" class="form-control" required>
                            <option value="">اختر الموظف</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>المبلغ</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>معدل الفائدة (%)</label>
                        <input type="number" name="interest_rate" id="edit_interest_rate" class="form-control" required min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>المدة (بالأشهر)</label>
                        <input type="number" name="term" id="edit_term" class="form-control" required min="1">
                    </div>

                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="pending">قيد الانتظار</option>
                            <option value="approved">تمت الموافقة</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal رفض قرض -->
<div class="modal fade" id="rejectLoanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">رفض قرض</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="loan_id" id="reject_loan_id">
                    
                    <div class="form-group">
                        <label>سبب الرفض</label>
                        <textarea name="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">رفض</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLoan(id) {
    // جلب بيانات القرض
    fetch(`get_loan.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const loan = data.loan;
                document.getElementById('edit_loan_id').value = loan.id;
                document.getElementById('edit_employee_id').value = loan.employee_id;
                document.getElementById('edit_amount').value = loan.amount;
                document.getElementById('edit_interest_rate').value = loan.interest_rate;
                document.getElementById('edit_term').value = loan.term;
                document.getElementById('edit_description').value = loan.description;
                document.getElementById('edit_status').value = loan.status;
                $('#editLoanModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات القرض');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات القرض');
        });
}

function deleteLoan(id) {
    if (confirm('هل أنت متأكد من حذف هذا القرض؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="loan_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function approveLoan(id) {
    if (confirm('هل أنت متأكد من الموافقة على هذا القرض؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="loan_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectLoan(id) {
    document.getElementById('reject_loan_id').value = id;
    $('#rejectLoanModal').modal('show');
}
</script>

<?php
// تضمين الفوتر
include '../includes/footer.php';
?> 