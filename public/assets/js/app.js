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
                }
            });
        });
    });

    // ---- Mostrar / ocultar contraseña ----
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.querySelector(button.dataset.togglePassword);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });

    // ---- Cronómetro de alquiler activo ----
    document.querySelectorAll('[data-timer-start]').forEach(function (box) {
        const start = new Date(box.dataset.timerStart.replace(' ', 'T')).getTime();
        const rate = parseFloat(box.dataset.timerRate || '0');
        const elapsedEl = box.querySelector('[data-timer-elapsed]');
        const costEl = box.querySelector('[data-timer-cost]');

        function tick() {
            const diff = Math.max(0, Date.now() - start);
            const totalMinutes = Math.floor(diff / 60000);
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            const seconds = Math.floor(diff / 1000) % 60;
            const estimatedMinutes = Math.max(1, Math.ceil(diff / 60000));

            if (elapsedEl) {
                elapsedEl.textContent = String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            }
            if (costEl) {
                costEl.textContent = '$ ' + (estimatedMinutes * rate).toLocaleString('es-CO') + ' COP';
            }
        }
        tick();
        setInterval(tick, 1000);
    });

    // ---- Tooltips ----
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ---- Copiar código QR ----
    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            navigator.clipboard.writeText(button.dataset.copy).then(function () {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Código copiado',
                    showConfirmButton: false,
                    timer: 1800
                });
            });
        });
    });
});
