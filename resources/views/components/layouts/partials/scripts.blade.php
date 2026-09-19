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
})();
</script>
