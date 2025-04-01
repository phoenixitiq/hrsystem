<?php
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

class Notification {
    private static $instance = null;
    private $db;
    private $notifications = [];

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add($userId, $message, $type = 'info', $link = null) {
        try {
            $sql = "INSERT INTO notifications (user_id, message, type, link, created_at) VALUES (?, ?, ?, ?, NOW())";
            $this->db->query($sql, [$userId, $message, $type, $link]);
            return true;
        } catch (Exception $e) {
            error_log("خطأ في إضافة الإشعار: " . $e->getMessage());
            return false;
        }
    }

    public function getUnread($userId) {
        try {
            $sql = "SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL ORDER BY created_at DESC";
            return $this->db->query($sql, [$userId])->fetchAll();
        } catch (Exception $e) {
            error_log("خطأ في جلب الإشعارات غير المقروءة: " . $e->getMessage());
            return [];
        }
    }

    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?";
            return $this->db->query($sql, [$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("خطأ في تحديث حالة الإشعار: " . $e->getMessage());
            return false;
        }
    }

    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL";
            return $this->db->query($sql, [$userId]);
        } catch (Exception $e) {
            error_log("خطأ في تحديث حالة جميع الإشعارات: " . $e->getMessage());
            return false;
        }
    }

    public function delete($notificationId, $userId) {
        try {
            $sql = "DELETE FROM notifications WHERE id = ? AND user_id = ?";
            return $this->db->query($sql, [$notificationId, $userId]);
        } catch (Exception $e) {
            error_log("خطأ في حذف الإشعار: " . $e->getMessage());
            return false;
        }
    }

    public function getCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL";
            $result = $this->db->query($sql, [$userId])->fetch();
            return $result['count'];
        } catch (Exception $e) {
            error_log("خطأ في حساب عدد الإشعارات: " . $e->getMessage());
            return 0;
        }
    }

    public function sendEmail($to, $subject, $message) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            return $mail->send();
        } catch (Exception $e) {
            error_log("خطأ في إرسال البريد الإلكتروني: " . $e->getMessage());
            return false;
        }
    }
}

// دالة مساعدة لعرض الإشعارات في الواجهة
function displayNotifications() {
    if (!isLoggedIn()) {
        return;
    }

    $notifications = Notification::getInstance();
    $unreadCount = $notifications->getCount($_SESSION['user_id']);
    $unreadNotifications = $notifications->getUnread($_SESSION['user_id']);
    ?>
    <li class="nav-item dropdown">
        <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="badge bg-danger"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
            <h6 class="dropdown-header">الإشعارات</h6>
            <?php if (empty($unreadNotifications)): ?>
                <div class="dropdown-item text-center">لا توجد إشعارات جديدة</div>
            <?php else: ?>
                <?php foreach ($unreadNotifications as $notification): ?>
                    <a class="dropdown-item" href="<?php echo $notification['link'] ?? '#'; ?>">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-<?php 
                                    echo $notification['type'] == 'success' ? 'check-circle text-success' : 
                                        ($notification['type'] == 'warning' ? 'exclamation-triangle text-warning' : 
                                        ($notification['type'] == 'danger' ? 'times-circle text-danger' : 'info-circle text-info')); 
                                ?>"></i>
                            </div>
                            <div>
                                <div class="small text-gray-500"><?php echo formatArabicDate($notification['created_at']); ?></div>
                                <span class="font-weight-bold"><?php echo htmlspecialchars($notification['title']); ?></span>
                                <div class="small text-gray-500"><?php echo htmlspecialchars($notification['message']); ?></div>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a class="dropdown-item text-center small text-gray-500" href="notifications.php">
                عرض جميع الإشعارات
            </a>
        </div>
    </li>
    <?php
} 