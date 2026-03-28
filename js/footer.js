// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!e.target.closest('.nav-item')) {
        document.querySelectorAll('.dropdown').forEach(d => d.style.display = '');
    }
});

// Tabs
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        const target = this.dataset.tab;
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        if (target) document.getElementById(target)?.classList.add('active');
    });
});

// Modales
document.querySelectorAll('[data-modal]').forEach(btn => {
    btn.addEventListener('click', function() {
        const id = this.dataset.modal;
        document.getElementById(id)?.classList.add('active');
    });
});
document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
        }
    });
});