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
if (!hasPermission('view_leaves')) {
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
        $leave_id = $_POST['leave_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $type = $_POST['type'] ?? '';
                    $start_date = $_POST['start_date'] ?? '';
                    $end_date = $_POST['end_date'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($type) || empty($start_date) || empty($end_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        // التحقق من عدم وجود تداخل في التواريخ
                        $overlapping = $db->fetch(
                            "SELECT id FROM leaves 
                             WHERE employee_id = ? 
                             AND status != 'rejected'
                             AND (
                                 (start_date BETWEEN ? AND ?) OR
                                 (end_date BETWEEN ? AND ?) OR
                                 (start_date <= ? AND end_date >= ?)
                             )",
                            [$employee_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
                        );

                        if ($overlapping) {
                            setAlert('error', 'يوجد تداخل في تواريخ الإجازة مع إجازة أخرى');
                        } else {
                            $db->insert(
                                'leaves',
                                [
                                    'employee_id' => $employee_id,
                                    'type' => $type,
                                    'start_date' => $start_date,
                                    'end_date' => $end_date,
                                    'reason' => $reason,
                                    'status' => $status,
                                    'created_by' => $_SESSION['user_id'],
                                    'created_at' => date('Y-m-d H:i:s')
                                ]
                            );
                            setAlert('success', 'تم إضافة طلب الإجازة بنجاح');
                            logActivity($_SESSION['user_id'], 'leave', 'تم إضافة طلب إجازة');
                        }
                    }
                    break;

                case 'update':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $type = $_POST['type'] ?? '';
                    $start_date = $_POST['start_date'] ?? '';
                    $end_date = $_POST['end_date'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    $status = $_POST['status'] ?? 'pending';

                    if (empty($employee_id) || empty($type) || empty($start_date) || empty($end_date)) {
                        setAlert('error', 'جميع الحقول المطلوبة يجب ملؤها');
                    } else {
                        $db = Database::getInstance();
                        // التحقق من عدم وجود تداخل في التواريخ (باستثناء السجل الحالي)
                        $overlapping = $db->fetch(
                            "SELECT id FROM leaves 
                             WHERE employee_id = ? 
                             AND status != 'rejected'
                             AND id != ?
                             AND (
                                 (start_date BETWEEN ? AND ?) OR
                                 (end_date BETWEEN ? AND ?) OR
                                 (start_date <= ? AND end_date >= ?)
                             )",
                            [$employee_id, $leave_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]
                        );

                        if ($overlapping) {
                            setAlert('error', 'يوجد تداخل في تواريخ الإجازة مع إجازة أخرى');
                        } else {
                            $db->update(
                                'leaves',
                                [
                                    'employee_id' => $employee_id,
                                    'type' => $type,
                                    'start_date' => $start_date,
                                    'end_date' => $end_date,
                                    'reason' => $reason,
                                    'status' => $status,
                                    'updated_by' => $_SESSION['user_id'],
                                    'updated_at' => date('Y-m-d H:i:s')
                                ],
                                'id = ? AND created_by = ?',
                                [$leave_id, $_SESSION['user_id']]
                            );
                            setAlert('success', 'تم تحديث طلب الإجازة بنجاح');
                            logActivity($_SESSION['user_id'], 'leave', 'تم تحديث طلب إجازة');
                        }
                    }
                    break;

                case 'delete':
                    if ($leave_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'leaves',
                            'id = ? AND created_by = ?',
                            [$leave_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف طلب الإجازة بنجاح');
                        logActivity($_SESSION['user_id'], 'leave', 'تم حذف طلب إجازة');
                    }
                    break;

                case 'approve':
                    if ($leave_id) {
                        $db = Database::getInstance();
                        $db->update(
                            'leaves',
                            [
                                'status' => 'approved',
                                'approved_by' => $_SESSION['user_id'],
                                'approved_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND created_by = ?',
                            [$leave_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم الموافقة على طلب الإجازة بنجاح');
                        logActivity($_SESSION['user_id'], 'leave', 'تم الموافقة على طلب إجازة');
                    }
                    break;

                case 'reject':
                    if ($leave_id) {
                        $rejection_reason = $_POST['rejection_reason'] ?? '';
                        if (empty($rejection_reason)) {
                            setAlert('error', 'سبب الرفض مطلوب');
                        } else {
                            $db = Database::getInstance();
                            $db->update(
                                'leaves',
                                [
                                    'status' => 'rejected',
                                    'rejection_reason' => $rejection_reason,
                                    'rejected_by' => $_SESSION['user_id'],
                                    'rejected_at' => date('Y-m-d H:i:s'),
                                    'updated_by' => $_SESSION['user_id'],
                                    'updated_at' => date('Y-m-d H:i:s')
                                ],
                                'id = ? AND created_by = ?',
                                [$leave_id, $_SESSION['user_id']]
                            );
                            setAlert('success', 'تم رفض طلب الإجازة بنجاح');
                            logActivity($_SESSION['user_id'], 'leave', 'تم رفض طلب إجازة');
                        }
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الإجازات: " . $e->getMessage(), 'error');
        }
    }
}

// جلب طلبات الإجازة
try {
    $db = Database::getInstance();
    $leaves = $db->fetchAll(
        "SELECT l.*, e.name as employee_name 
         FROM leaves l 
         JOIN employees e ON l.employee_id = e.id 
         WHERE l.created_by = ? 
         ORDER BY l.created_at DESC",
        [$_SESSION['user_id']]
    );

    // جلب الموظفين النشطين
    $employees = $db->fetchAll(
        "SELECT id, name FROM employees 
         WHERE status = 'active' AND created_by = ? 
         ORDER BY name",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب البيانات');
    logEvent("خطأ في جلب بيانات الإجازات: " . $e->getMessage(), 'error');
    $leaves = [];
    $employees = [];
}

// تعيين عنوان الصفحة
$page_title = 'الإجازات';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الإجازات</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addLeaveModal">
                            <i class="fas fa-plus"></i> إضافة طلب إجازة
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($leaves)): ?>
                        <div class="alert alert-info">لا يوجد طلبات إجازة</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>نوع الإجازة</th>
                                        <th>تاريخ البداية</th>
                                        <th>تاريخ النهاية</th>
                                        <th>السبب</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leaves as $leave): ?>
                                        <tr>
                                            <td><?php echo $leave['id']; ?></td>
                                            <td><?php echo htmlspecialchars($leave['employee_name']); ?></td>
                                            <td>
                                                <?php
                                                switch ($leave['type']) {
                                                    case 'annual':
                                                        echo 'سنوية';
                                                        break;
                                                    case 'sick':
                                                        echo 'مرضية';
                                                        break;
                                                    case 'emergency':
                                                        echo 'طارئة';
                                                        break;
                                                    case 'unpaid':
                                                        echo 'بدون راتب';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo formatDate($leave['start_date']); ?></td>
                                            <td><?php echo formatDate($leave['end_date']); ?></td>
                                            <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                            <td>
                                                <?php
                                                switch ($leave['status']) {
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
                                                <?php if ($leave['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="approveLeave(<?php echo $leave['id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="rejectLeave(<?php echo $leave['id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editLeave(<?php echo $leave['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteLeave(<?php echo $leave['id']; ?>)">
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

<!-- Modal إضافة طلب إجازة -->
<div class="modal fade" id="addLeaveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة طلب إجازة</h5>
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
                        <label>نوع الإجازة</label>
                        <select name="type" class="form-control" required>
                            <option value="annual">سنوية</option>
                            <option value="sick">مرضية</option>
                            <option value="emergency">طارئة</option>
                            <option value="unpaid">بدون راتب</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ البداية</label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>تاريخ النهاية</label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>السبب</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
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

<!-- Modal تعديل طلب إجازة -->
<div class="modal fade" id="editLeaveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل طلب إجازة</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="leave_id" id="edit_leave_id">
                    
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
                        <label>نوع الإجازة</label>
                        <select name="type" id="edit_type" class="form-control" required>
                            <option value="annual">سنوية</option>
                            <option value="sick">مرضية</option>
                            <option value="emergency">طارئة</option>
                            <option value="unpaid">بدون راتب</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>تاريخ البداية</label>
                        <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>تاريخ النهاية</label>
                        <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>السبب</label>
                        <textarea name="reason" id="edit_reason" class="form-control" rows="3" required></textarea>
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

<!-- Modal رفض طلب إجازة -->
<div class="modal fade" id="rejectLeaveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">رفض طلب إجازة</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="leave_id" id="reject_leave_id">
                    
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
function editLeave(id) {
    // جلب بيانات طلب الإجازة
    fetch(`get_leave.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const leave = data.leave;
                document.getElementById('edit_leave_id').value = leave.id;
                document.getElementById('edit_employee_id').value = leave.employee_id;
                document.getElementById('edit_type').value = leave.type;
                document.getElementById('edit_start_date').value = leave.start_date;
                document.getElementById('edit_end_date').value = leave.end_date;
                document.getElementById('edit_reason').value = leave.reason;
                document.getElementById('edit_status').value = leave.status;
                $('#editLeaveModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات طلب الإجازة');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات طلب الإجازة');
        });
}

function deleteLeave(id) {
    if (confirm('هل أنت متأكد من حذف هذا الطلب؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="leave_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function approveLeave(id) {
    if (confirm('هل أنت متأكد من الموافقة على هذا الطلب؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="leave_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function rejectLeave(id) {
    document.getElementById('reject_leave_id').value = id;
    $('#rejectLeaveModal').modal('show');
}
</script>

<?php
// تضمين الفوتر
include '../includes/footer.php';
?> 