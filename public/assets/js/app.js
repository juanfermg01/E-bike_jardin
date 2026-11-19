document.addEventListener('DOMContentLoaded', function () {
    // ---- Mensajes flash con SweetAlert2 ----
    if (window.APP && Array.isArray(window.APP.flash)) {
        window.APP.flash.forEach(function (flash, index) {
            const icons = { success: 'success', error: 'error', warning: 'warning', info: 'info' };
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icons[flash.type] || 'info',
                title: flash.message,
                showConfirmButton: false,
                timer: 4200 + index * 350,
                timerProgressBar: true,
                didOpen: function (toast) {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        });
    }

    // ---- Sidebar móvil ----
    const body = document.body;
    const toggle = document.getElementById('sidebarToggle');
    const close = document.getElementById('sidebarClose');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggle) {
        toggle.addEventListener('click', function () {
            body.classList.add('sidebar-open');
        });
    }
    [close, backdrop].forEach(function (el) {
        if (el) {
            el.addEventListener('click', function () {
                body.classList.remove('sidebar-open');
            });
        }
    });

    // ---- Confirmaciones con SweetAlert2 ----
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1') {
                return;
            }
            event.preventDefault();
            Swal.fire({
                title: form.dataset.confirm,
                text: form.dataset.text || 'Esta acción no se puede deshacer.',
                icon: form.dataset.icon || 'warning',
                showCancelButton: true,
                confirmButtonText: form.dataset.confirmText || 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-brand ms-2',
                    cancelButton: 'btn btn-outline-secondary'
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.submit();
