<?php
// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// تضمين الملفات المطلوبة
require_once 'config.php';
require_once 'functions.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// التحقق من نوع التصدير
$type = $_GET['type'] ?? '';
$data_type = $_GET['data_type'] ?? '';

// التحقق من الصلاحيات
if (!checkPermission('export_data')) {
    header("Location: error.php?code=403");
    exit();
}

// تحديد نوع البيانات المراد تصديرها
switch ($data_type) {
    case 'employees':
        $query = "SELECT e.*, d.name as department_name, p.name as position_name 
                 FROM employees e 
                 LEFT JOIN departments d ON e.department_id = d.id 
                 LEFT JOIN positions p ON e.position_id = p.id 
                 ORDER BY e.created_at DESC";
        $filename = 'employees_' . date('Y-m-d');
        break;
        
    case 'loans':
        $query = "SELECT l.*, e.first_name, e.last_name, e.employee_id 
                 FROM loans l 
                 JOIN employees e ON l.employee_id = e.id 
                 ORDER BY l.created_at DESC";
        $filename = 'loans_' . date('Y-m-d');
        break;
        
    case 'debts':
        $query = "SELECT d.*, e.first_name, e.last_name, e.employee_id 
                 FROM debts d 
                 JOIN employees e ON d.employee_id = e.id 
                 ORDER BY d.created_at DESC";
        $filename = 'debts_' . date('Y-m-d');
        break;
        
    case 'salaries':
        $query = "SELECT s.*, e.first_name, e.last_name, e.employee_id 
                 FROM salaries s 
                 JOIN employees e ON s.employee_id = e.id 
                 ORDER BY s.created_at DESC";
        $filename = 'salaries_' . date('Y-m-d');
        break;
        
    default:
        die('نوع البيانات غير صالح');
}

// جلب البيانات
try {
    $stmt = executeQueryWithRetry($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("خطأ في جلب البيانات للتصدير: " . $e->getMessage());
    die('حدث خطأ في جلب البيانات');
}

// تصدير البيانات حسب النوع المطلوب
switch ($type) {
    case 'excel':
        // تصدير إلى Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        echo '<table border="1">';
        // رؤوس الأعمدة
        if (!empty($data)) {
            echo '<tr>';
            foreach (array_keys($data[0]) as $header) {
                echo '<th>' . htmlspecialchars($header) . '</th>';
            }
            echo '</tr>';
            
            // البيانات
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $value) {
                    echo '<td>' . htmlspecialchars($value) . '</td>';
                }
                echo '</tr>';
            }
        }
        echo '</table>';
        break;
        
    case 'pdf':
        // تصدير إلى PDF
        require_once 'tcpdf/tcpdf.php';
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // إعداد المستند
        $pdf->SetCreator('نظام إدارة الموارد البشرية');
        $pdf->SetAuthor('إدارة الموارد البشرية');
        $pdf->SetTitle($filename);
        
        // إضافة صفحة
        $pdf->AddPage();
        
        // إضافة الجدول
        $html = '<table border="1">';
        if (!empty($data)) {
            // رؤوس الأعمدة
            $html .= '<tr>';
            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            
            // البيانات
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        $html .= '</table>';
        
        // كتابة HTML
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // إخراج PDF
        $pdf->Output($filename . '.pdf', 'D');
        break;
        
    case 'csv':
        // تصدير إلى CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // إضافة BOM للدعم العربي
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // رؤوس الأعمدة
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // البيانات
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        break;
        
    default:
        die('نوع التصدير غير صالح');
}

class Export {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function toCSV($data, $filename) {
        try {
            // إنشاء مجلد التصدير إذا لم يكن موجوداً
            if (!file_exists(EXPORT_PATH)) {
                mkdir(EXPORT_PATH, 0755, true);
            }

            $filepath = EXPORT_PATH . '/' . $filename . '_' . date('Y-m-d_H-i-s') . '.csv';
            
            // فتح الملف للكتابة
            $fp = fopen($filepath, 'w');
            if ($fp === false) {
                throw new Exception('فشل في إنشاء ملف CSV');
            }

            // إضافة رأس الملف
            if (!empty($data)) {
                fputcsv($fp, array_keys($data[0]));
            }

            // إضافة البيانات
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }

            fclose($fp);
            return $filepath;
        } catch (Exception $e) {
            error_log("خطأ في تصدير CSV: " . $e->getMessage());
            throw new Exception('فشل في تصدير البيانات');
        }
    }

    public function toExcel($data, $filename) {
        try {
            require_once BASEPATH . '/vendor/autoload.php';

            // إنشاء مجلد التصدير إذا لم يكن موجوداً
            if (!file_exists(EXPORT_PATH)) {
                mkdir(EXPORT_PATH, 0755, true);
            }

            $filepath = EXPORT_PATH . '/' . $filename . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // إضافة رأس الملف
            if (!empty($data)) {
                $column = 1;
                foreach (array_keys($data[0]) as $header) {
                    $sheet->setCellValueByColumnAndRow($column++, 1, $header);
                }
            }

            // إضافة البيانات
            $row = 2;
            foreach ($data as $rowData) {
                $column = 1;
                foreach ($rowData as $value) {
                    $sheet->setCellValueByColumnAndRow($column++, $row, $value);
                }
                $row++;
            }

            // حفظ الملف
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);

            return $filepath;
        } catch (Exception $e) {
            error_log("خطأ في تصدير Excel: " . $e->getMessage());
            throw new Exception('فشل في تصدير البيانات');
        }
    }

    public function toPDF($data, $filename, $template = null) {
        try {
            require_once BASEPATH . '/vendor/autoload.php';

            // إنشاء مجلد التصدير إذا لم يكن موجوداً
            if (!file_exists(EXPORT_PATH)) {
                mkdir(EXPORT_PATH, 0755, true);
            }

            $filepath = EXPORT_PATH . '/' . $filename . '_' . date('Y-m-d_H-i-s') . '.pdf';
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15
            ]);

            // إضافة محتوى HTML
            $html = '<html dir="rtl" lang="ar">';
            $html .= '<head>';
            $html .= '<meta charset="UTF-8">';
            $html .= '<style>';
            $html .= 'body { font-family: DejaVu Sans, sans-serif; }';
            $html .= 'table { width: 100%; border-collapse: collapse; }';
            $html .= 'th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }';
            $html .= 'th { background-color: #f5f5f5; }';
            $html .= '</style>';
            $html .= '</head>';
            $html .= '<body>';

            if (!empty($data)) {
                $html .= '<table>';
                $html .= '<thead><tr>';
                foreach (array_keys($data[0]) as $header) {
                    $html .= '<th>' . htmlspecialchars($header) . '</th>';
                }
                $html .= '</tr></thead>';
                $html .= '<tbody>';

                foreach ($data as $row) {
                    $html .= '<tr>';
                    foreach ($row as $value) {
                        $html .= '<td>' . htmlspecialchars($value) . '</td>';
                    }
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';
            }

            $html .= '</body></html>';

            $mpdf->WriteHTML($html);
            $mpdf->Output($filepath, 'F');

            return $filepath;
        } catch (Exception $e) {
            error_log("خطأ في تصدير PDF: " . $e->getMessage());
            throw new Exception('فشل في تصدير البيانات');
        }
    }

    public function downloadFile($filepath) {
        if (file_exists($filepath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
        throw new Exception('الملف غير موجود');
    }
} 