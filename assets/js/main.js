/**
 * After-Sales Service Information System (ASSIS)
 * Core Client-Side Behaviors
 */

document.addEventListener('DOMContentLoaded', function () {
    // Auto-dismiss alert notifications after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // Universal confirmation triggers (data-confirm="Message")
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you wish to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Print button triggers (class: btn-print-sheet)
    document.querySelectorAll('.btn-print-sheet').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            window.print();
        });
    });
});
