<?php
require_once 'includes/config.php';
require_once 'includes/Database.php';
require_once 'includes/export.php';

// إنشاء بيانات تجريبية
$data = [
    [
        'الاسم' => 'أحمد محمد',
        'المنصب' => 'مدير',
        'القسم' => 'تقنية المعلومات',
        'تاريخ التعيين' => '2023-01-15',
        'الراتب' => '5000'
    ],
    [
        'الاسم' => 'سارة أحمد',
        'المنصب' => 'محلل نظم',
        'القسم' => 'تقنية المعلومات',
        'تاريخ التعيين' => '2023-03-20',
        'الراتب' => '3500'
    ],
    [
        'الاسم' => 'محمد علي',
        'المنصب' => 'مطور برمجيات',
        'القسم' => 'تقنية المعلومات',
        'تاريخ التعيين' => '2023-06-10',
        'الراتب' => '4000'
    ]
];

try {
    $export = Export::getInstance();

    // تصدير إلى CSV
    $csvFile = $export->toCSV($data, 'employees');
    echo "تم تصدير البيانات إلى ملف CSV: " . $csvFile . "\n";

    // تصدير إلى Excel
    $excelFile = $export->toExcel($data, 'employees');
    echo "تم تصدير البيانات إلى ملف Excel: " . $excelFile . "\n";

    // تصدير إلى PDF
    $pdfFile = $export->toPDF($data, 'employees');
    echo "تم تصدير البيانات إلى ملف PDF: " . $pdfFile . "\n";

    // تحميل الملفات
    echo "\nتحميل الملفات:\n";
    echo "1. CSV: <a href='download.php?file=" . basename($csvFile) . "'>تحميل CSV</a>\n";
    echo "2. Excel: <a href='download.php?file=" . basename($excelFile) . "'>تحميل Excel</a>\n";
    echo "3. PDF: <a href='download.php?file=" . basename($pdfFile) . "'>تحميل PDF</a>\n";

} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage() . "\n";
} 