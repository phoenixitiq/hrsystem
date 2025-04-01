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
if (!hasPermission('view_employees')) {
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
        $employee_id = $_POST['employee_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $name = $_POST['name'] ?? '';
                    $email = $_POST['email'] ?? '';
                    $phone = $_POST['phone'] ?? '';
                    $department = $_POST['department'] ?? '';
                    $position = $_POST['position'] ?? '';
                    $hire_date = $_POST['hire_date'] ?? '';
                    $status = $_POST['status'] ?? 'active';

                    if (empty($name) || empty($email) || empty($phone) || empty($department) || empty($position) || empty($hire_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->insert(
                            'employees',
                            [
                                'name' => $name,
                                'email' => $email,
                                'phone' => $phone,
                                'department' => $department,
                                'position' => $position,
                                'hire_date' => $hire_date,
                                'status' => $status,
                                'created_by' => $_SESSION['user_id'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        setAlert('success', 'تم إضافة الموظف بنجاح');
                        logActivity($_SESSION['user_id'], 'employee', 'تم إضافة موظف جديد');
                    }
                    break;

                case 'update':
                    $name = $_POST['name'] ?? '';
                    $email = $_POST['email'] ?? '';
                    $phone = $_POST['phone'] ?? '';
                    $department = $_POST['department'] ?? '';
                    $position = $_POST['position'] ?? '';
                    $hire_date = $_POST['hire_date'] ?? '';
                    $status = $_POST['status'] ?? 'active';

                    if (empty($name) || empty($email) || empty($phone) || empty($department) || empty($position) || empty($hire_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        $db->update(
                            'employees',
                            [
                                'name' => $name,
                                'email' => $email,
                                'phone' => $phone,
                                'department' => $department,
                                'position' => $position,
                                'hire_date' => $hire_date,
                                'status' => $status,
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$employee_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث بيانات الموظف بنجاح');
                        logActivity($_SESSION['user_id'], 'employee', 'تم تحديث بيانات موظف');
                    }
                    break;

                case 'delete':
                    if ($employee_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'employees',
                            'id = ? AND created_by = ?',
                            [$employee_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف الموظف بنجاح');
                        logActivity($_SESSION['user_id'], 'employee', 'تم حذف موظف');
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الموظفين: " . $e->getMessage(), 'error');
        }
    }
}

// جلب الموظفين
try {
    $db = Database::getInstance();
    $employees = $db->fetchAll(
        "SELECT * FROM employees 
         WHERE created_by = ? 
         ORDER BY created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب الموظفين');
    logEvent("خطأ في جلب الموظفين: " . $e->getMessage(), 'error');
    $employees = [];
}

// تعيين عنوان الصفحة
$page_title = 'الموظفين';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الموظفين</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addEmployeeModal">
                            <i class="fas fa-plus"></i> إضافة موظف جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($employees)): ?>
                        <div class="alert alert-info">لا يوجد موظفين</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>البريد الإلكتروني</th>
                                        <th>رقم الهاتف</th>
                                        <th>القسم</th>
                                        <th>المنصب</th>
                                        <th>تاريخ التعيين</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><?php echo $employee['id']; ?></td>
                                            <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                            <td><?php echo formatDate($employee['hire_date']); ?></td>
                                            <td>
                                                <?php
                                                switch ($employee['status']) {
                                                    case 'active':
                                                        echo '<span class="badge badge-success">نشط</span>';
                                                        break;
                                                    case 'inactive':
                                                        echo '<span class="badge badge-danger">غير نشط</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editEmployee(<?php echo $employee['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteEmployee(<?php echo $employee['id']; ?>)">
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

<!-- Modal إضافة موظف -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة موظف جديد</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>الاسم</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>رقم الهاتف</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>القسم</label>
                        <input type="text" name="department" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>المنصب</label>
                        <input type="text" name="position" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>تاريخ التعيين</label>
                        <input type="date" name="hire_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" class="form-control" required>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
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

<!-- Modal تعديل موظف -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل بيانات موظف</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="employee_id" id="edit_employee_id">
                    
                    <div class="form-group">
                        <label>الاسم</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>رقم الهاتف</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>القسم</label>
                        <input type="text" name="department" id="edit_department" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>المنصب</label>
                        <input type="text" name="position" id="edit_position" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>تاريخ التعيين</label>
                        <input type="date" name="hire_date" id="edit_hire_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="active">نشط</option>
                            <option value="inactive">غير نشط</option>
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
function editEmployee(id) {
    // جلب بيانات الموظف
    fetch(`get_employee.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const employee = data.employee;
                document.getElementById('edit_employee_id').value = employee.id;
                document.getElementById('edit_name').value = employee.name;
                document.getElementById('edit_email').value = employee.email;
                document.getElementById('edit_phone').value = employee.phone;
                document.getElementById('edit_department').value = employee.department;
                document.getElementById('edit_position').value = employee.position;
                document.getElementById('edit_hire_date').value = employee.hire_date;
                document.getElementById('edit_status').value = employee.status;
                $('#editEmployeeModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات الموظف');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات الموظف');
        });
}

function deleteEmployee(id) {
    if (confirm('هل أنت متأكد من حذف هذا الموظف؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="employee_id" value="${id}">
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