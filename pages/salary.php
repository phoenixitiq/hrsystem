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
if (!hasPermission('view_salary')) {
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
        $salary_id = $_POST['salary_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $basic_salary = $_POST['basic_salary'] ?? 0;
                    $allowances = $_POST['allowances'] ?? 0;
                    $deductions = $_POST['deductions'] ?? 0;
                    $payment_method = $_POST['payment_method'] ?? '';
                    $payment_date = $_POST['payment_date'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($basic_salary) || empty($payment_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->insert(
                            'salary',
                            [
                                'employee_id' => $employee_id,
                                'basic_salary' => $basic_salary,
                                'allowances' => $allowances,
                                'deductions' => $deductions,
                                'payment_method' => $payment_method,
                                'payment_date' => $payment_date,
                                'status' => $status,
                                'created_by' => $_SESSION['user_id'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        setAlert('success', 'تم إضافة الراتب بنجاح');
                        logActivity($_SESSION['user_id'], 'salary', 'تم إضافة راتب جديد');
                    }
                    break;

                case 'update':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $basic_salary = $_POST['basic_salary'] ?? 0;
                    $allowances = $_POST['allowances'] ?? 0;
                    $deductions = $_POST['deductions'] ?? 0;
                    $payment_method = $_POST['payment_method'] ?? '';
                    $payment_date = $_POST['payment_date'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($basic_salary) || empty($payment_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->update(
                            'salary',
                            [
                                'employee_id' => $employee_id,
                                'basic_salary' => $basic_salary,
                                'allowances' => $allowances,
                                'deductions' => $deductions,
                                'payment_method' => $payment_method,
                                'payment_date' => $payment_date,
                                'status' => $status,
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$salary_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث الراتب بنجاح');
                        logActivity($_SESSION['user_id'], 'salary', 'تم تحديث راتب');
                    }
                    break;

                case 'delete':
                    if ($salary_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'salary',
                            'id = ? AND created_by = ?',
                            [$salary_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف الراتب بنجاح');
                        logActivity($_SESSION['user_id'], 'salary', 'تم حذف راتب');
                    }
                    break;

                case 'approve':
                    if ($salary_id) {
                        $db = Database::getInstance();
                        $db->update(
                            'salary',
                            [
                                'status' => 'approved',
                                'approved_by' => $_SESSION['user_id'],
                                'approved_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$salary_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم الموافقة على الراتب بنجاح');
                        logActivity($_SESSION['user_id'], 'salary', 'تم الموافقة على راتب');
                    }
                    break;

                case 'reject':
                    $rejection_reason = $_POST['rejection_reason'] ?? '';
                    if ($salary_id && !empty($rejection_reason)) {
                        $db = Database::getInstance();
                        $db->update(
                            'salary',
                            [
                                'status' => 'rejected',
                                'rejection_reason' => $rejection_reason,
                                'rejected_by' => $_SESSION['user_id'],
                                'rejected_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$salary_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم رفض الراتب بنجاح');
                        logActivity($_SESSION['user_id'], 'salary', 'تم رفض راتب');
                    } else {
                        setAlert('error', 'يرجى إدخال سبب الرفض');
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الرواتب: " . $e->getMessage(), 'error');
        }
    }
}

// جلب الرواتب
try {
    $db = Database::getInstance();
    $salaries = $db->fetchAll(
        "SELECT s.*, e.name as employee_name 
         FROM salary s 
         JOIN employees e ON s.employee_id = e.id 
         WHERE s.created_by = ? 
         ORDER BY s.created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب الرواتب');
    logEvent("خطأ في جلب الرواتب: " . $e->getMessage(), 'error');
    $salaries = [];
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
$page_title = 'الرواتب';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الرواتب</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addSalaryModal">
                            <i class="fas fa-plus"></i> إضافة راتب جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($salaries)): ?>
                        <div class="alert alert-info">لا توجد رواتب</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>الراتب الأساسي</th>
                                        <th>البدلات</th>
                                        <th>الخصومات</th>
                                        <th>الصافي</th>
                                        <th>طريقة الدفع</th>
                                        <th>تاريخ الدفع</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($salaries as $salary): ?>
                                        <tr>
                                            <td><?php echo $salary['id']; ?></td>
                                            <td><?php echo htmlspecialchars($salary['employee_name']); ?></td>
                                            <td><?php echo formatNumber($salary['basic_salary']); ?></td>
                                            <td><?php echo formatNumber($salary['allowances']); ?></td>
                                            <td><?php echo formatNumber($salary['deductions']); ?></td>
                                            <td><?php echo formatNumber($salary['basic_salary'] + $salary['allowances'] - $salary['deductions']); ?></td>
                                            <td><?php echo htmlspecialchars($salary['payment_method']); ?></td>
                                            <td><?php echo formatDate($salary['payment_date']); ?></td>
                                            <td>
                                                <?php
                                                switch ($salary['status']) {
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
                                                <?php if ($salary['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="approveSalary(<?php echo $salary['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="rejectSalary(<?php echo $salary['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editSalary(<?php echo $salary['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteSalary(<?php echo $salary['id']; ?>)">
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

<!-- Modal إضافة راتب -->
<div class="modal fade" id="addSalaryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة راتب جديد</h5>
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
                        <label>الراتب الأساسي</label>
                        <input type="number" name="basic_salary" class="form-control" required min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>البدلات</label>
                        <input type="number" name="allowances" class="form-control" min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>الخصومات</label>
                        <input type="number" name="deductions" class="form-control" min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>طريقة الدفع</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="cash">نقدي</option>
                            <option value="check">شيك</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ الدفع</label>
                        <input type="date" name="payment_date" class="form-control" required>
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

<!-- Modal تعديل راتب -->
<div class="modal fade" id="editSalaryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل راتب</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="salary_id" id="edit_salary_id">
                    
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
                        <label>الراتب الأساسي</label>
                        <input type="number" name="basic_salary" id="edit_basic_salary" class="form-control" required min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>البدلات</label>
                        <input type="number" name="allowances" id="edit_allowances" class="form-control" min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>الخصومات</label>
                        <input type="number" name="deductions" id="edit_deductions" class="form-control" min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label>طريقة الدفع</label>
                        <select name="payment_method" id="edit_payment_method" class="form-control" required>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="cash">نقدي</option>
                            <option value="check">شيك</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ الدفع</label>
                        <input type="date" name="payment_date" id="edit_payment_date" class="form-control" required>
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

<!-- Modal رفض راتب -->
<div class="modal fade" id="rejectSalaryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">رفض راتب</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="salary_id" id="reject_salary_id">
                    
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
function editSalary(id) {
    // جلب بيانات الراتب
    fetch(`get_salary.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const salary = data.salary;
                document.getElementById('edit_salary_id').value = salary.id;
                document.getElementById('edit_employee_id').value = salary.employee_id;
                document.getElementById('edit_basic_salary').value = salary.basic_salary;
                document.getElementById('edit_allowances').value = salary.allowances;
                document.getElementById('edit_deductions').value = salary.deductions;
                document.getElementById('edit_payment_method').value = salary.payment_method;
                document.getElementById('edit_payment_date').value = salary.payment_date;
                document.getElementById('edit_status').value = salary.status;
                $('#editSalaryModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات الراتب');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات الراتب');
        });
}

function deleteSalary(id) {
    if (confirm('هل أنت متأكد من حذف هذا الراتب؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="salary_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function approveSalary(id) {
    if (confirm('هل أنت متأكد من الموافقة على هذا الراتب؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="salary_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectSalary(id) {
    document.getElementById('reject_salary_id').value = id;
    $('#rejectSalaryModal').modal('show');
}
</script>

<?php
// تضمين الفوتر
include '../includes/footer.php';
?> 