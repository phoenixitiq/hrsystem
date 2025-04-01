-- إنشاء قاعدة البيانات
DROP DATABASE IF EXISTS hrsystem;
CREATE DATABASE hrsystem CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrsystem;

-- إنشاء جدول المستخدمين
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'hr', 'manager', 'employee') NOT NULL DEFAULT 'employee',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    login_attempts INT NOT NULL DEFAULT 0,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الصلاحيات
DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول صلاحيات المستخدمين
DROP TABLE IF EXISTS user_permissions;
CREATE TABLE user_permissions (
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الأقسام
DROP TABLE IF EXISTS departments;
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    manager_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول المناصب
DROP TABLE IF EXISTS positions;
CREATE TABLE positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الموظفين
DROP TABLE IF EXISTS employees;
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    national_id VARCHAR(20) UNIQUE NOT NULL,
    birth_date DATE,
    gender ENUM('male', 'female') NOT NULL,
    marital_status ENUM('single', 'married', 'divorced', 'widowed') NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    department_id INT,
    position_id INT,
    hire_date DATE NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    photo VARCHAR(255),
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    status ENUM('active', 'inactive', 'on_leave', 'terminated') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول أنواع الإجازات
DROP TABLE IF EXISTS leave_types;
CREATE TABLE leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    paid BOOLEAN NOT NULL DEFAULT TRUE,
    max_days INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الإجازات
DROP TABLE IF EXISTS leaves;
CREATE TABLE leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الحضور
DROP TABLE IF EXISTS attendance;
CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    break_start TIME,
    break_end TIME,
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    status ENUM('present', 'absent', 'late', 'early_leave', 'half_day') NOT NULL DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول القروض
DROP TABLE IF EXISTS loans;
CREATE TABLE loans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    interest_rate DECIMAL(5,2) DEFAULT 0,
    term_months INT NOT NULL,
    monthly_payment DECIMAL(10,2) NOT NULL,
    remaining_amount DECIMAL(10,2) NOT NULL,
    last_payment_date DATE,
    next_payment_date DATE,
    status ENUM('pending', 'approved', 'rejected', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الديون
DROP TABLE IF EXISTS debts;
CREATE TABLE debts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    reason TEXT,
    due_date DATE,
    status ENUM('active', 'paid', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء جدول الرواتب
DROP TABLE IF EXISTS salaries;
CREATE TABLE salaries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    month DATE NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    overtime_payment DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processed', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_salary (employee_id, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدخال البيانات الأساسية
INSERT INTO permissions (name, description) VALUES
('manage_users', 'إدارة المستخدمين'),
('manage_employees', 'إدارة الموظفين'),
('manage_departments', 'إدارة الأقسام'),
('manage_positions', 'إدارة المناصب'),
('manage_leaves', 'إدارة الإجازات'),
('manage_attendance', 'إدارة الحضور'),
('manage_loans', 'إدارة القروض'),
('manage_debts', 'إدارة الديون'),
('manage_salaries', 'إدارة الرواتب'),
('view_reports', 'عرض التقارير'),
('manage_settings', 'إدارة الإعدادات');

-- إدخال أنواع الإجازات الأساسية
INSERT INTO leave_types (name, description, paid, max_days) VALUES
('سنوية', 'إجازة سنوية مدفوعة', TRUE, 30),
('مرضية', 'إجازة مرضية', TRUE, 90),
('طارئة', 'إجازة طارئة', FALSE, 5),
('بدون راتب', 'إجازة بدون راتب', FALSE, 30);

-- إدخال الأقسام الأساسية
INSERT INTO departments (name, description) VALUES
('قسم تقنية المعلومات', 'قسم تقنية المعلومات'),
('قسم المالية', 'قسم المالية'),
('قسم الموارد البشرية', 'قسم الموارد البشرية'),
('قسم التسويق', 'قسم التسويق'),
('قسم المبيعات', 'قسم المبيعات');

-- إدخال المناصب الأساسية
INSERT INTO positions (name, description, department_id) VALUES
('مدير قسم تقنية المعلومات', 'مدير قسم تقنية المعلومات', 1),
('مطور برمجيات', 'مطور برمجيات', 1),
('محلل نظم', 'محلل نظم', 1),
('مدير مالي', 'مدير مالي', 2),
('محاسب', 'محاسب', 2),
('مدير موارد بشرية', 'مدير موارد بشرية', 3),
('موظف موارد بشرية', 'موظف موارد بشرية', 3),
('مدير تسويق', 'مدير تسويق', 4),
('مسؤول تسويق', 'مسؤول تسويق', 4),
('مدير مبيعات', 'مدير مبيعات', 5),
('مندوب مبيعات', 'مندوب مبيعات', 5);

-- إدخال المستخدم الافتراضي
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'مدير النظام', 'admin');

-- إدخال بيانات تجريبية للموظفين
INSERT INTO employees (name, national_id, birth_date, gender, marital_status, email, phone, address, emergency_contact, emergency_phone, department_id, position_id, hire_date, salary, bank_name, bank_account) VALUES
('أحمد محمد', '1234567890', '1990-01-01', 'male', 'married', 'ahmed@example.com', '1234567890', 'عنوان أحمد', 'زوجته', '9876543210', 1, 1, '2020-01-01', 5000.00, 'بنك الكويت الوطني', '123456789'),
('سارة أحمد', '0987654321', '1992-05-15', 'female', 'single', 'sara@example.com', '0987654321', 'عنوان سارة', 'أخيها', '1234567890', 2, 4, '2020-02-01', 4500.00, 'بنك الكويت الوطني', '987654321'),
('محمد علي', '1122334455', '1988-08-20', 'male', 'married', 'mohammed@example.com', '1122334455', 'عنوان محمد', 'زوجته', '1122334455', 3, 6, '2020-03-01', 4000.00, 'بنك الكويت الوطني', '112233445'),
('فاطمة حسن', '5544332211', '1995-03-10', 'female', 'single', 'fatima@example.com', '5544332211', 'عنوان فاطمة', 'أخيها', '5544332211', 4, 8, '2020-04-01', 3500.00, 'بنك الكويت الوطني', '554433221'),
('علي إبراهيم', '6677889900', '1991-12-25', 'male', 'married', 'ali@example.com', '6677889900', 'عنوان علي', 'زوجته', '6677889900', 5, 10, '2020-05-01', 3000.00, 'بنك الكويت الوطني', '667788990'),
('نور محمد', '0011223344', '1993-07-05', 'female', 'single', 'noor@example.com', '0011223344', 'عنوان نور', 'أخيها', '0011223344', 1, 2, '2020-06-01', 3800.00, 'بنك الكويت الوطني', '001122334'),
('يوسف أحمد', '5566778899', '1989-11-15', 'male', 'married', 'yousef@example.com', '5566778899', 'عنوان يوسف', 'زوجته', '5566778899', 2, 5, '2020-07-01', 4200.00, 'بنك الكويت الوطني', '556677889'),
('ليلى حسن', '9900112233', '1994-04-20', 'female', 'single', 'layla@example.com', '9900112233', 'عنوان ليلى', 'أخيها', '9900112233', 3, 7, '2020-08-01', 3600.00, 'بنك الكويت الوطني', '990011223'),
('خالد محمد', '4455667788', '1990-09-30', 'male', 'married', 'khaled@example.com', '4455667788', 'عنوان خالد', 'زوجته', '4455667788', 4, 9, '2020-09-01', 3400.00, 'بنك الكويت الوطني', '445566778'),
('رنا أحمد', '2233445566', '1992-02-12', 'female', 'single', 'rana@example.com', '2233445566', 'عنوان رنا', 'أخيها', '2233445566', 5, 11, '2020-10-01', 3200.00, 'بنك الكويت الوطني', '223344556'); 