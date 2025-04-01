// تأكيد الحذف
function confirmDelete(event) {
    if (!confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
        event.preventDefault();
    }
}

// تنسيق المبالغ المالية
function formatCurrency(input) {
    let value = input.value.replace(/[^\d.]/g, '');
    if (value) {
        value = parseFloat(value).toFixed(2);
        input.value = value;
    }
}

// تنسيق التاريخ
function formatDate(input) {
    let value = input.value;
    if (value) {
        let date = new Date(value);
        if (!isNaN(date.getTime())) {
            input.value = date.toISOString().split('T')[0];
        }
    }
}

// حساب القسط الشهري للقرض
function calculateMonthlyPayment() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
    const termMonths = parseInt(document.getElementById('term_months').value) || 0;
    
    if (amount && termMonths) {
        let monthlyPayment;
        if (interestRate > 0) {
            // قرض بفائدة
            const monthlyRate = interestRate / 100 / 12;
            monthlyPayment = amount * (monthlyRate * Math.pow(1 + monthlyRate, termMonths)) / (Math.pow(1 + monthlyRate, termMonths) - 1);
        } else {
            // قرض بدون فائدة
            monthlyPayment = amount / termMonths;
        }
        
        document.getElementById('monthly_payment').value = monthlyPayment.toFixed(2);
    } else {
        document.getElementById('monthly_payment').value = '';
    }
}

// تهيئة DataTables
$(document).ready(function() {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/ar.json'
            },
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true
        });
    }

    // تهيئة Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // تهيئة Popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// التحقق من صحة النموذج
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// تحديث حالة العنصر
function updateStatus(elementId, status) {
    const element = document.getElementById(elementId);
    if (!element) return;

    element.className = 'status-' + status;
    element.textContent = status;
}

// تحميل الصور
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        preview.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
}

// إرسال نموذج AJAX
function submitFormAjax(formId, successCallback, errorCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    const formData = new FormData(form);
    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (successCallback) successCallback(data);
        } else {
            if (errorCallback) errorCallback(data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (errorCallback) errorCallback({ message: 'حدث خطأ أثناء معالجة الطلب' });
    });
}

// تحديث البيانات تلقائياً
function autoRefresh(interval = 30000) {
    setInterval(function() {
        const refreshElements = document.querySelectorAll('[data-refresh]');
        refreshElements.forEach(element => {
            const url = element.getAttribute('data-refresh');
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    element.innerHTML = html;
                })
                .catch(error => console.error('Error:', error));
        });
    }, interval);
}

// إدارة التنبيهات
const AlertManager = {
    show: function(message, type = 'success') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed bottom-0 end-0 m-3`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => {
            const alert = new bootstrap.Alert(alertDiv);
            alert.close();
        }, 5000);
    },
    success: function(message) {
        this.show(message, 'success');
    },
    error: function(message) {
        this.show(message, 'danger');
    },
    warning: function(message) {
        this.show(message, 'warning');
    },
    info: function(message) {
        this.show(message, 'info');
    }
};
