// نظام إدارة الموارد البشرية - الوظائف الأساسية

// عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل التلميحات
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // تفعيل النوافذ المنبثقة
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // إخفاء رسائل التنبيه تلقائياً
    var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // تفعيل تحديد التواريخ
    var datepickers = document.querySelectorAll('.datepicker');
    datepickers.forEach(function(el) {
        new Datepicker(el, {
            format: 'yyyy-mm-dd',
            autohide: true,
            language: 'ar'
        });
    });

    // تفعيل الاختيار المتعدد
    var selects = document.querySelectorAll('.select2');
    selects.forEach(function(el) {
        new Choices(el, {
            removeItemButton: true,
            searchEnabled: true,
            placeholder: true
        });
    });
});

// دالة تأكيد الحذف
function confirmDelete(message = 'هل أنت متأكد من عملية الحذف؟') {
    return confirm(message);
}

// دالة عرض شاشة التحميل
function showLoading() {
    var loading = document.createElement('div');
    loading.className = 'loading';
    loading.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>';
    document.body.appendChild(loading);
}

// دالة إخفاء شاشة التحميل
function hideLoading() {
    var loading = document.querySelector('.loading');
    if (loading) {
        loading.remove();
    }
}

// دالة تنسيق العملة
function formatCurrency(amount, currency = 'IQD') {
    return new Intl.NumberFormat('ar-IQ', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

// دالة تنسيق التاريخ
function formatDate(date) {
    return new Intl.DateTimeFormat('ar-IQ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    }).format(new Date(date));
}

// دالة التحقق من صحة النموذج
function validateForm(form) {
    var isValid = true;
    var requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// دالة تحميل الملفات
function uploadFile(input, previewElement) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
            if (previewElement.tagName === 'IMG') {
                previewElement.src = e.target.result;
            }
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// دالة طباعة المحتوى
function printContent(elementId) {
    var element = document.getElementById(elementId);
    var originalContent = document.body.innerHTML;
    
    document.body.innerHTML = element.innerHTML;
    window.print();
    document.body.innerHTML = originalContent;
    
    // إعادة تهيئة السكريبتات
    location.reload();
}

// دالة تصدير إلى Excel
function exportToExcel(tableId, fileName) {
    var table = document.getElementById(tableId);
    var wb = XLSX.utils.table_to_book(table, {sheet: "Sheet1"});
    XLSX.writeFile(wb, fileName || 'export.xlsx');
}

// معالجة الأخطاء
window.onerror = function(message, source, lineno, colno, error) {
    console.error('خطأ:', message, 'في السطر:', lineno, 'في الملف:', source);
    return false;
};
