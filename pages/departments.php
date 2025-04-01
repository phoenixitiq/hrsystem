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
if (!hasPermission('view_departments')) {
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
        $department_id = $_POST['department_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $name = $_POST['name'] ?? '';
                    $description = $_POST['description'] ?? '';
                    $status = $_POST['status'] ?? 'active';

                    if (empty($name)) {
                        setAlert('error', 'اسم القسم مطلوب');
                    } else {
                        $db = Database::getInstance();
                        $db->insert(
                            'departments',
                            [
                                'name' => $name,
                                'description' => $description,
                                'status' => $status,
                                'created_by' => $_SESSION['user_id'],
                                'created_at' => date('Y-m-d H:i:s')
                            ]
                        );
                        setAlert('success', 'تم إضافة القسم بنجاح');
                        logActivity($_SESSION['user_id'], 'department', 'تم إضافة قسم جديد');
                    }
                    break;

                case 'update':
                    $name = $_POST['name'] ?? '';
                    $description = $_POST['description'] ?? '';
                    $status = $_POST['status'] ?? 'active';

                    if (empty($name)) {
                        setAlert('error', 'اسم القسم مطلوب');
                    } else {
                        $db = Database::getInstance();
                        $db->update(
                            'departments',
                            [
                                'name' => $name,
                                'description' => $description,
                                'status' => $status,
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$department_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث بيانات القسم بنجاح');
                        logActivity($_SESSION['user_id'], 'department', 'تم تحديث بيانات قسم');
                    }
                    break;

                case 'delete':
                    if ($department_id) {
                        $db = Database::getInstance();
                        // التحقق من وجود موظفين في القسم
                        $employees_count = $db->fetch(
                            "SELECT COUNT(*) as count FROM employees WHERE department_id = ?",
                            [$department_id]
                        )['count'];

                        if ($employees_count > 0) {
                            setAlert('error', 'لا يمكن حذف القسم لوجود موظفين مرتبطين به');
                        } else {
                            $db->delete(
                                'departments',
                                'id = ? AND created_by = ?',
                                [$department_id, $_SESSION['user_id']]
                            );
                            setAlert('success', 'تم حذف القسم بنجاح');
                            logActivity($_SESSION['user_id'], 'department', 'تم حذف قسم');
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الأقسام: " . $e->getMessage(), 'error');
        }
    }
}

// جلب الأقسام
try {
    $db = Database::getInstance();
    $departments = $db->fetchAll(
        "SELECT d.*, 
                (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id) as employees_count
         FROM departments d 
         WHERE d.created_by = ? 
         ORDER BY d.created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب الأقسام');
    logEvent("خطأ في جلب الأقسام: " . $e->getMessage(), 'error');
    $departments = [];
}

// تعيين عنوان الصفحة
$page_title = 'الأقسام';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الأقسام</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addDepartmentModal">
                            <i class="fas fa-plus"></i> إضافة قسم جديد
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($departments)): ?>
                        <div class="alert alert-info">لا يوجد أقسام</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الاسم</th>
                                        <th>الوصف</th>
                                        <th>عدد الموظفين</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $department): ?>
                                        <tr>
                                            <td><?php echo $department['id']; ?></td>
                                            <td><?php echo htmlspecialchars($department['name']); ?></td>
                                            <td><?php echo htmlspecialchars($department['description']); ?></td>
                                            <td><?php echo $department['employees_count']; ?></td>
                                            <td>
                                                <?php
                                                switch ($department['status']) {
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
                                                        onclick="editDepartment(<?php echo $department['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteDepartment(<?php echo $department['id']; ?>)">
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

<!-- Modal إضافة قسم -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة قسم جديد</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>اسم القسم</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
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

<!-- Modal تعديل قسم -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل بيانات قسم</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="department_id" id="edit_department_id">
                    
                    <div class="form-group">
                        <label>اسم القسم</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>الوصف</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
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
function editDepartment(id) {
    // جلب بيانات القسم
    fetch(`get_department.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const department = data.department;
                document.getElementById('edit_department_id').value = department.id;
                document.getElementById('edit_name').value = department.name;
                document.getElementById('edit_description').value = department.description;
                document.getElementById('edit_status').value = department.status;
                $('#editDepartmentModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات القسم');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات القسم');
        });
}

function deleteDepartment(id) {
    if (confirm('هل أنت متأكد من حذف هذا القسم؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="department_id" value="${id}">
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