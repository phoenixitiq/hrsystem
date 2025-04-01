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
if (!hasPermission('view_attendance')) {
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
        $attendance_id = $_POST['attendance_id'] ?? 0;

        try {
            switch ($action) {
                case 'add':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $date = $_POST['date'] ?? '';
                    $check_in = $_POST['check_in'] ?? '';
                    $check_out = $_POST['check_out'] ?? '';
                    $status = $_POST['status'] ?? 'present';
                    $notes = $_POST['notes'] ?? '';

                    if (empty($employee_id) || empty($date)) {
                        setAlert('error', 'الموظف والتاريخ مطلوبان');
                    } else {
                        $db = Database::getInstance();
                        // التحقق من عدم وجود تسجيل حضور سابق لنفس الموظف في نفس اليوم
                        $existing = $db->fetch(
                            "SELECT id FROM attendance 
                             WHERE employee_id = ? AND date = ?",
                            [$employee_id, $date]
                        );

                        if ($existing) {
                            setAlert('error', 'يوجد تسجيل حضور سابق لهذا الموظف في نفس اليوم');
                        } else {
                            $db->insert(
                                'attendance',
                                [
                                    'employee_id' => $employee_id,
                                    'date' => $date,
                                    'check_in' => $check_in,
                                    'check_out' => $check_out,
                                    'status' => $status,
                                    'notes' => $notes,
                                    'created_by' => $_SESSION['user_id'],
                                    'created_at' => date('Y-m-d H:i:s')
                                ]
                            );
                            setAlert('success', 'تم إضافة تسجيل الحضور بنجاح');
                            logActivity($_SESSION['user_id'], 'attendance', 'تم إضافة تسجيل حضور');
                        }
                    }
                    break;

                case 'update':
                    $employee_id = $_POST['employee_id'] ?? 0;
                    $date = $_POST['date'] ?? '';
                    $check_in = $_POST['check_in'] ?? '';
                    $check_out = $_POST['check_out'] ?? '';
                    $status = $_POST['status'] ?? 'present';
                    $notes = $_POST['notes'] ?? '';

                    if (empty($employee_id) || empty($date)) {
                        setAlert('error', 'الموظف والتاريخ مطلوبان');
                    } else {
                        $db = Database::getInstance();
                        // التحقق من عدم وجود تسجيل حضور سابق لنفس الموظف في نفس اليوم (باستثناء السجل الحالي)
                        $existing = $db->fetch(
                            "SELECT id FROM attendance 
                             WHERE employee_id = ? AND date = ? AND id != ?",
                            [$employee_id, $date, $attendance_id]
                        );

                        if ($existing) {
                            setAlert('error', 'يوجد تسجيل حضور سابق لهذا الموظف في نفس اليوم');
                        } else {
                            $db->update(
                                'attendance',
                                [
                                    'employee_id' => $employee_id,
                                    'date' => $date,
                                    'check_in' => $check_in,
                                    'check_out' => $check_out,
                                    'status' => $status,
                                    'notes' => $notes,
                                    'updated_by' => $_SESSION['user_id'],
                                    'updated_at' => date('Y-m-d H:i:s')
                                ],
                                'id = ? AND created_by = ?',
                                [$attendance_id, $_SESSION['user_id']]
                            );
                            setAlert('success', 'تم تحديث تسجيل الحضور بنجاح');
                            logActivity($_SESSION['user_id'], 'attendance', 'تم تحديث تسجيل حضور');
                        }
                    }
                    break;

                case 'delete':
                    if ($attendance_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'attendance',
                            'id = ? AND created_by = ?',
                            [$attendance_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف تسجيل الحضور بنجاح');
                        logActivity($_SESSION['user_id'], 'attendance', 'تم حذف تسجيل حضور');
                    }
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الحضور: " . $e->getMessage(), 'error');
        }
    }
}

// جلب تسجيلات الحضور
try {
    $db = Database::getInstance();
    $attendance = $db->fetchAll(
        "SELECT a.*, e.name as employee_name 
         FROM attendance a 
         JOIN employees e ON a.employee_id = e.id 
         WHERE a.created_by = ? 
         ORDER BY a.date DESC, a.created_at DESC",
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
    logEvent("خطأ في جلب بيانات الحضور: " . $e->getMessage(), 'error');
    $attendance = [];
    $employees = [];
}

// تعيين عنوان الصفحة
$page_title = 'الحضور والانصراف';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الحضور والانصراف</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addAttendanceModal">
                            <i class="fas fa-plus"></i> إضافة تسجيل حضور
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($attendance)): ?>
                        <div class="alert alert-info">لا يوجد تسجيلات حضور</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>التاريخ</th>
                                        <th>وقت الحضور</th>
                                        <th>وقت الانصراف</th>
                                        <th>الحالة</th>
                                        <th>ملاحظات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td><?php echo $record['id']; ?></td>
                                            <td><?php echo htmlspecialchars($record['employee_name']); ?></td>
                                            <td><?php echo formatDate($record['date']); ?></td>
                                            <td><?php echo $record['check_in'] ? formatTime($record['check_in']) : '-'; ?></td>
                                            <td><?php echo $record['check_out'] ? formatTime($record['check_out']) : '-'; ?></td>
                                            <td>
                                                <?php
                                                switch ($record['status']) {
                                                    case 'present':
                                                        echo '<span class="badge badge-success">حاضر</span>';
                                                        break;
                                                    case 'absent':
                                                        echo '<span class="badge badge-danger">غائب</span>';
                                                        break;
                                                    case 'late':
                                                        echo '<span class="badge badge-warning">متأخر</span>';
                                                        break;
                                                    case 'early_leave':
                                                        echo '<span class="badge badge-info">مغادرة مبكرة</span>';
                                                        break;
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['notes']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm" 
                                                        onclick="editAttendance(<?php echo $record['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                        onclick="deleteAttendance(<?php echo $record['id']; ?>)">
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

<!-- Modal إضافة تسجيل حضور -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إضافة تسجيل حضور</h5>
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
                        <label>التاريخ</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>وقت الحضور</label>
                        <input type="time" name="check_in" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>وقت الانصراف</label>
                        <input type="time" name="check_out" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" class="form-control" required>
                            <option value="present">حاضر</option>
                            <option value="absent">غائب</option>
                            <option value="late">متأخر</option>
                            <option value="early_leave">مغادرة مبكرة</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
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

<!-- Modal تعديل تسجيل حضور -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تعديل تسجيل حضور</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="attendance_id" id="edit_attendance_id">
                    
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
                        <label>التاريخ</label>
                        <input type="date" name="date" id="edit_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>وقت الحضور</label>
                        <input type="time" name="check_in" id="edit_check_in" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>وقت الانصراف</label>
                        <input type="time" name="check_out" id="edit_check_out" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>الحالة</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="present">حاضر</option>
                            <option value="absent">غائب</option>
                            <option value="late">متأخر</option>
                            <option value="early_leave">مغادرة مبكرة</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>ملاحظات</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
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
function editAttendance(id) {
    // جلب بيانات تسجيل الحضور
    fetch(`get_attendance.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const attendance = data.attendance;
                document.getElementById('edit_attendance_id').value = attendance.id;
                document.getElementById('edit_employee_id').value = attendance.employee_id;
                document.getElementById('edit_date').value = attendance.date;
                document.getElementById('edit_check_in').value = attendance.check_in;
                document.getElementById('edit_check_out').value = attendance.check_out;
                document.getElementById('edit_status').value = attendance.status;
                document.getElementById('edit_notes').value = attendance.notes;
                $('#editAttendanceModal').modal('show');
            } else {
                alert('حدث خطأ أثناء جلب بيانات تسجيل الحضور');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ أثناء جلب بيانات تسجيل الحضور');
        });
}

function deleteAttendance(id) {
    if (confirm('هل أنت متأكد من حذف هذا التسجيل؟')) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="attendance_id" value="${id}">
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