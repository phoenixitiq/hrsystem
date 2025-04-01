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
if (!hasPermission('view_notifications')) {
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
        $notification_id = $_POST['notification_id'] ?? 0;

        try {
            switch ($action) {
                case 'mark_as_read':
                    if ($notification_id) {
                        $db = Database::getInstance();
                        $db->update(
                            'notifications',
                            [
                                'is_read' => 1,
                                'read_at' => date('Y-m-d H:i:s'),
                                'updated_by' => $_SESSION['user_id'],
                                'updated_at' => date('Y-m-d H:i:s')
                            ],
                            'id = ? AND user_id = ?',
                            [$notification_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم تحديث حالة الإشعار بنجاح');
                        logActivity($_SESSION['user_id'], 'notification', 'تم تحديث حالة إشعار');
                    }
                    break;

                case 'delete':
                    if ($notification_id) {
                        $db = Database::getInstance();
                        $db->delete(
                            'notifications',
                            'id = ? AND user_id = ?',
                            [$notification_id, $_SESSION['user_id']]
                        );
                        setAlert('success', 'تم حذف الإشعار بنجاح');
                        logActivity($_SESSION['user_id'], 'notification', 'تم حذف إشعار');
                    }
                    break;

                case 'mark_all_as_read':
                    $db = Database::getInstance();
                    $db->update(
                        'notifications',
                        [
                            'is_read' => 1,
                            'read_at' => date('Y-m-d H:i:s'),
                            'updated_by' => $_SESSION['user_id'],
                            'updated_at' => date('Y-m-d H:i:s')
                        ],
                        'user_id = ? AND is_read = 0',
                        [$_SESSION['user_id']]
                    );
                    setAlert('success', 'تم تحديث حالة جميع الإشعارات بنجاح');
                    logActivity($_SESSION['user_id'], 'notification', 'تم تحديث حالة جميع الإشعارات');
                    break;

                case 'clear_all':
                    $db = Database::getInstance();
                    $db->delete(
                        'notifications',
                        'user_id = ?',
                        [$_SESSION['user_id']]
                    );
                    setAlert('success', 'تم حذف جميع الإشعارات بنجاح');
                    logActivity($_SESSION['user_id'], 'notification', 'تم حذف جميع الإشعارات');
                    break;
            }
        } catch (Exception $e) {
            setAlert('error', 'حدث خطأ أثناء معالجة الطلب');
            logEvent("خطأ في معالجة الإشعارات: " . $e->getMessage(), 'error');
        }
    }
}

// جلب الإشعارات
try {
    $db = Database::getInstance();
    $notifications = $db->fetchAll(
        "SELECT * FROM notifications 
         WHERE user_id = ? 
         ORDER BY created_at DESC",
        [$_SESSION['user_id']]
    );
} catch (Exception $e) {
    setAlert('error', 'حدث خطأ أثناء جلب الإشعارات');
    logEvent("خطأ في جلب الإشعارات: " . $e->getMessage(), 'error');
    $notifications = [];
}

// تعيين عنوان الصفحة
$page_title = 'الإشعارات';

// تضمين الهيدر
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الإشعارات</h3>
                    <div class="card-tools">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="mark_all_as_read">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check-double"></i> تعليم الكل كمقروء
                            </button>
                        </form>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="action" value="clear_all">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('هل أنت متأكد من حذف جميع الإشعارات؟')">
                                <i class="fas fa-trash"></i> حذف الكل
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="alert alert-info">لا توجد إشعارات</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h5>
                                        <small><?php echo formatDate($notification['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <div class="mt-2">
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="action" value="mark_as_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check"></i> تعليم كمقروء
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا الإشعار؟')">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// تضمين الفوتر
include '../includes/footer.php';
?> 