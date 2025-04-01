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
if (!hasPermission('view_debts')) {
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
        $debt_id = $_POST['debt_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $amount = $_POST['amount'] ?? 0;
                    $description = $_POST['description'] ?? '';
                    $due_date = $_POST['due_date'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($amount) || empty($due_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->insert(
                            'debts',
                            [
                                'employee_id' => $employee_id,
                                'amount' => $amount,
                                'description' => $description,
                                'due_date' => $due_date,
                                'status' => $status,
                                'created_by' => $_SESSION['user_id'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        setAlert('success', 'تم إضافة الدين بنجاح');
                        logActivity($_SESSION['user_id'], 'debt', 'تم إضافة دين جديد');
                    }
                    break;

                case 'update':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $amount = $_POST['amount'] ?? 0;
                    $description = $_POST['description'] ?? '';
                    $due_date = $_POST['due_date'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($amount) || empty($due_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->update(
                            'debts',
                            [
                                'employee_id' => $employee_id,
                                'amount' => $amount,
                                'description' => $description,
                                'due_date' => $due_date,
                                'status' => $status,
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$debt_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث الدين بنجاح');
                        logActivity($_SESSION['user_id'], 'debt', 'تم تحديث دين');
                    }
                    break;

                case 'delete':
                    if ($debt_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'debts',
                            'id = ? AND created_by = ?',
                            [$debt_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف الدين بنجاح');
                        logActivity($_SESSION['user_id'], 'debt', 'تم حذف دين');
                    }
                    break;

                case 'mark_as_paid':
                    if ($debt_id) {
                        $db = Database::getInstance();
                        $db->update(
                            'debts',
                            [
                                'status' => 'paid',
                                'paid_by' => $_SESSION['user_id'],
                                'paid_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$debt_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث حالة الدين بنجاح');
                        logActivity($_SESSION['user_id'], 'debt', 'تم تحديث حالة دين');
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الديون: " . $e->getMessage(), 'error');
        }
    }
}

// جلب الديون
try {
    $db = Database::getInstance();
    $debts = $db->fetchAll(
        "SELECT d.*, e.name as employee_name 
         FROM debts d 
         JOIN employees e ON d.employee_id = e.id 
         WHERE d.created_by = ? 
         ORDER BY d.created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب الديون');
    logEvent("خطأ في جلب الديون: " . $e->getMessage(), 'error');
    $debts = [];
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
$page_title = 'الديون';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الديون</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addDebtModal">
                            <i class="fas fa-plus"></i> إضافة دين جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($debts)): ?>
                        <div class="alert alert-info">لا توجد ديون</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>المبلغ</th>
                                        <th>الوصف</th>
                                        <th>تاريخ الاستحقاق</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($debts as $debt): ?>
                                        <tr>
                                            <td><?php echo $debt['id']; ?></td>
                                            <td><?php echo htmlspecialchars($debt['employee_name']); ?></td>
                                            <td><?php echo formatNumber($debt['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($debt['description']); ?></td>
                                            <td><?php echo formatDate($debt['due_date']); ?></td>
                                            <td>
                                                <?php
                                                switch ($debt['status']) {
                                                    case 'pending':
                                                        echo '<span class="badge badge-warning">قيد الانتظار</span>';
                                                        break;
                                                    case 'paid':
                                                        echo '<span class="badge badge-success">تم الدفع</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($debt['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="markAsPaid(<?php echo $debt['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editDebt(<?php echo $debt['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteDebt(<?php echo $debt['id']; ?>)">
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

<!-- Modal إضافة دين -->
<div class="modal fade" id="addDebtModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة دين جديد</h5>
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
                        <label>الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" class="form-control" required>
                            <option value="pending">قيد الانتظار</option>
                            <option value="paid">تم الدفع</option>
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

<!-- Modal تعديل دين -->
<div class="modal fade" id="editDebtModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل دين</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="debt_id" id="edit_debt_id">
                    
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
                        <label>الوصف</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>تاريخ الاستحقاق</label>
                        <input type="date" name="due_date" id="edit_due_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="pending">قيد الانتظار</option>
                            <option value="paid">تم الدفع</option>
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

<script>
function editDebt(id) {
    // جلب بيانات الدين
    fetch(`get_debt.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const debt = data.debt;
                document.getElementById('edit_debt_id').value = debt.id;
                document.getElementById('edit_employee_id').value = debt.employee_id;
                document.getElementById('edit_amount').value = debt.amount;
                document.getElementById('edit_description').value = debt.description;
                document.getElementById('edit_due_date').value = debt.due_date;
                document.getElementById('edit_status').value = debt.status;
                $('#editDebtModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات الدين');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات الدين');
        });
}

function deleteDebt(id) {
    if (confirm('هل أنت متأكد من حذف هذا الدين؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="debt_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function markAsPaid(id) {
    if (confirm('هل أنت متأكد من تحديث حالة هذا الدين إلى تم الدفع؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="mark_as_paid">
            <input type="hidden" name="debt_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// تضمين الفوتر
include '../includes/footer.php';
?> 