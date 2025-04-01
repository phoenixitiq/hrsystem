# نظام إدارة الموارد البشرية

نظام متكامل لإدارة الموارد البشرية مبني باستخدام PHP. يتضمن وظائف تصدير البيانات إلى مختلف التنسيقات (CSV, Excel, PDF).

## المتطلبات

- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- Composer

## التثبيت

1. قم بنسخ المشروع إلى المجلد المطلوب:
```bash
git clone https://github.com/yourusername/hrsystem.git
cd hrsystem
```

2. قم بتثبيت المكتبات المطلوبة:
```bash
composer install
```

3. قم بإنشاء قاعدة البيانات وتحديث ملف `includes/config.php` بمعلومات الاتصال الخاصة بك.

4. تأكد من أن المجلد `exports` قابل للكتابة:
```bash
chmod 755 exports
```

## الاستخدام

### تصدير البيانات

يمكنك استخدام النظام لتصدير البيانات إلى مختلف التنسيقات:

```php
$export = Export::getInstance();

// تصدير إلى CSV
$csvFile = $export->toCSV($data, 'filename');

// تصدير إلى Excel
$excelFile = $export->toExcel($data, 'filename');

// تصدير إلى PDF
$pdfFile = $export->toPDF($data, 'filename');
```

### تحميل الملفات

يمكنك تحميل الملفات المصدرة باستخدام:
```php
$export->downloadFile($filepath);
```

## الميزات

- تصدير البيانات إلى CSV
- تصدير البيانات إلى Excel
- تصدير البيانات إلى PDF
- دعم كامل للغة العربية
- معالجة الأخطاء وتسجيلها
- واجهة برمجة تطبيقات (API) سهلة الاستخدام

## المساهمة

نرحب بمساهماتكم! يرجى اتباع الخطوات التالية:

1. قم بعمل Fork للمشروع
2. قم بإنشاء فرع جديد (`git checkout -b feature/amazing-feature`)
3. قم بعمل Commit للتغييرات (`git commit -m 'إضافة ميزة جديدة'`)
4. قم بعمل Push إلى الفرع (`git push origin feature/amazing-feature`)
5. قم بفتح طلب Pull Request

## الترخيص

هذا المشروع مرخص تحت رخصة MIT. راجع ملف `LICENSE` لمزيد من المعلومات. 