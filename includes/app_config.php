<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// تحديد بيئة التشغيل
if (!isset($_SERVER['ENV'])) {
    $_SERVER['ENV'] = 'development'; // يمكن تغييره إلى 'production' في بيئة الإنتاج
}

// إعدادات التطبيق
define('APP_NAME', 'نظام إدارة الموارد البشرية');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'https://hr.teebaalahmd.com.iq');
define('APP_PATH', dirname(dirname(__FILE__)));

// إعدادات البريد الإلكتروني
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@example.com');
define('SMTP_PASS', 'your-password');
define('SMTP_FROM', 'noreply@example.com');
define('SMTP_FROM_NAME', APP_NAME);

// إعدادات الملفات
define('UPLOAD_PATH', APP_PATH . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf']);

// إعدادات الأمان
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 3600); // ساعة واحدة
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 دقيقة
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_LIFETIME', 3600); // ساعة واحدة

// إعدادات التقارير
define('REPORT_PATH', APP_PATH . '/reports');
define('REPORT_TEMPLATE_PATH', APP_PATH . '/templates/reports');
define('REPORT_FORMATS', ['pdf', 'excel', 'csv']);
define('REPORT_MAX_ROWS', 1000);

// إعدادات التصدير
define('EXPORT_PATH', APP_PATH . '/exports');
define('EXPORT_FORMATS', ['pdf', 'excel', 'csv']);
define('EXPORT_MAX_ROWS', 1000);

// إعدادات التخزين المؤقت
define('CACHE_PATH', APP_PATH . '/cache');
define('CACHE_LIFETIME', 3600); // ساعة واحدة
define('CACHE_ENABLED', true);

// إعدادات السجلات
define('LOG_PATH', APP_PATH . '/logs');
define('LOG_LEVEL', $_SERVER['ENV'] === 'production' ? 'ERROR' : 'DEBUG');
define('LOG_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('LOG_MAX_FILES', 30); // الاحتفاظ بملفات السجل لمدة 30 يوم

// إعدادات التصميم
define('THEME_PATH', APP_PATH . '/assets/themes');
define('DEFAULT_THEME', 'default');
define('RTL_SUPPORT', true);

// إعدادات اللغة
define('DEFAULT_LANGUAGE', 'ar');
define('AVAILABLE_LANGUAGES', ['ar', 'en']);
define('LANGUAGE_PATH', APP_PATH . '/languages');

// إعدادات التقويم
define('CALENDAR_FIRST_DAY', 1); // 1 = الأحد
define('CALENDAR_SHOW_WEEK_NUMBERS', true);
define('CALENDAR_SHOW_TODAY', true);

// إعدادات العمل
define('WORKING_HOURS_START', '08:00');
define('WORKING_HOURS_END', '16:00');
define('WORKING_DAYS', [1, 2, 3, 4, 5]); // 1 = الأحد
define('OVERTIME_RATE', 1.5); // معدل ساعات العمل الإضافية

// إعدادات الرواتب
define('SALARY_CALCULATION_METHOD', 'monthly'); // monthly, daily, hourly
define('SALARY_DEDUCTION_RATE', 0.1); // نسبة الخصومات
define('SALARY_BONUS_RATE', 0.05); // نسبة المكافآت
define('SALARY_TAX_RATE', 0.15); // نسبة الضريبة

// إعدادات القروض
define('LOAN_MAX_AMOUNT', 10000); // الحد الأقصى للقرض
define('LOAN_MIN_AMOUNT', 1000); // الحد الأدنى للقرض
define('LOAN_MAX_TERM', 12); // الحد الأقصى لمدة القرض بالأشهر
define('LOAN_INTEREST_RATE', 0.05); // نسبة الفائدة السنوية

// إعدادات الإشعارات
define('NOTIFICATION_ENABLED', true);
define('NOTIFICATION_TYPES', ['email', 'sms', 'browser']);
define('NOTIFICATION_PRIORITIES', ['low', 'medium', 'high']);
define('NOTIFICATION_EXPIRY_DAYS', 30); 