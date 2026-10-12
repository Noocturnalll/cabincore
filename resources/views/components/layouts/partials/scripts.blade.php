<script>
(function () {
    var root = document.documentElement;
    var app  = document.getElementById('cbm-app');
    var sun  = document.getElementById('cbm-app-sun');
    var moon = document.getElementById('cbm-app-moon');

    function applyTheme(theme) {
        if (theme === 'light') {
            root.classList.add('cbm-light');
            if (sun)  sun.style.display  = 'none';
            if (moon) moon.style.display = 'block';
        } else {
            root.classList.remove('cbm-light');
            if (sun)  sun.style.display  = 'block';
            if (moon) moon.style.display = 'none';
        }
        // Re-render charts if they exist
        if (window.cbmCharts) {
            window.cbmCharts.forEach(function(c) { c.update(); });
        }
    }

    var saved = localStorage.getItem('cbm-theme');
    if (saved) {
        applyTheme(saved);
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
        applyTheme('light');
    } else {
        applyTheme('dark');
    }

    window.cbmToggleTheme = function () {
        var isLight = root.classList.contains('cbm-light');
        var next = isLight ? 'dark' : 'light';
        localStorage.setItem('cbm-theme', next);
        applyTheme(next);
        // Re-initialize charts with correct theme colors (destroy + rebuild)
        if (window.cbmInitCharts) {
            requestAnimationFrame(function () { window.cbmInitCharts(); });
        }
    };

    window.cbmOpenSidebar = function () {
        document.getElementById('cbm-sidebar').classList.add('cbm-open');
        var overlay = document.getElementById('cbm-overlay');
        overlay.style.display = 'block';
        requestAnimationFrame(function() { overlay.classList.add('cbm-visible'); });
    };

    window.cbmCloseSidebar = function () {
        document.getElementById('cbm-sidebar').classList.remove('cbm-open');
        var overlay = document.getElementById('cbm-overlay');
        overlay.classList.remove('cbm-visible');
        setTimeout(function() { overlay.style.display = 'none'; }, 300);
    };

    // Sidebar scroll persistence across Livewire navigation
    function getSidebarNav() {
        return document.querySelector('.cbm-sidebar-nav');
    }

    function saveSidebarScroll() {
        var nav = getSidebarNav();
        if (nav) {
            sessionStorage.setItem('cbm-sidebar-scroll', nav.scrollTop);
        }
    }

    function restoreSidebarScroll() {
        var nav = getSidebarNav();
        if (!nav) return;
        var saved = sessionStorage.getItem('cbm-sidebar-scroll');
        if (saved !== null) {
            var top = parseInt(saved, 10);
            nav.scrollTop = top;
            requestAnimationFrame(function () {
                nav.scrollTop = top;
            });
        } else {
            var active = nav.querySelector('.cbm-nav-item.cbm-active');
            if (active) {
                active.scrollIntoView({ block: 'nearest' });
            }
        }
    }

    document.addEventListener('scroll', function (e) {
        if (e.target && e.target.classList && e.target.classList.contains('cbm-sidebar-nav')) {
            sessionStorage.setItem('cbm-sidebar-scroll', e.target.scrollTop);
        }
    }, { capture: true, passive: true });

    document.addEventListener('livewire:navigating', saveSidebarScroll);
    window.addEventListener('beforeunload', saveSidebarScroll);

    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('.cbm-sidebar-nav')) {
            saveSidebarScroll();
        }
    }, { capture: true, passive: true });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreSidebarScroll);
    } else {
        restoreSidebarScroll();
    }
    document.addEventListener('livewire:navigated', restoreSidebarScroll);
})();

// Listen for SweetAlert Toast notifications from Livewire
document.addEventListener('livewire:initialized', () => {
    Livewire.on('notify', (event) => {
        // Event data could be an array of arguments, so handle appropriately
        const data = Array.isArray(event) ? event[0] : event;
        
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: data.timer || 3000,
            timerProgressBar: true,
            background: 'var(--cbm-sidebar-bg)',
            color: 'var(--cbm-text)',
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
        
        Toast.fire({
            icon: data.icon || "success",
            title: data.title || data.message || "Berhasil"
        });
    });

    Livewire.on('swal:modal', (event) => {
        const data = Array.isArray(event) ? event[0] : event;
        if (window.Swal) {
            Swal.fire({
                icon: data.icon || 'info',
                title: data.title || '',
                text: data.text || data.message || '',
                confirmButtonText: data.confirmButtonText || 'Mengerti',
                confirmButtonColor: 'var(--cbm-primary, #3b82f6)',
                background: 'var(--cbm-card-bg, #1e293b)',
                color: 'var(--cbm-text, #f8fafc)',
            });
        }
    });

    Livewire.on('swal:error', (event) => {
        const data = Array.isArray(event) ? event[0] : event;
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: data.title || 'Error',
                text: data.text || data.message || '',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#ef4444',
                background: 'var(--cbm-card-bg, #1e293b)',
                color: 'var(--cbm-text, #f8fafc)',
            });
        }
    });
});
</script>
