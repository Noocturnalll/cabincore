@php
    $kpiSummary = $kpi['summary'] ?? [];
    $kpiTarget = $kpi['target'] ?? 90;
    $kpiGauges = $kpi['gauges'] ?? [];
@endphp

<style>
.kpi-wrap { margin-top: .5rem; }
.kpi-summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:.875rem; margin-bottom:1rem; }
.kpi-sum-card {
    position:relative; overflow:hidden;
    background:var(--cbm-card-bg); border:1px solid var(--cbm-card-border);
    border-radius:1rem; padding:1rem 1.1rem; box-shadow:var(--cbm-card-shadow);
    transition:transform .2s ease, box-shadow .2s ease;
}
.kpi-sum-card:hover { transform:translateY(-2px); box-shadow:0 0.625rem 1.75rem rgba(0,0,0,.18); }
.kpi-sum-card::before { content:''; position:absolute; inset:0 auto 0 0; width:3px; background:var(--kpi-accent,#3b82f6); }
.kpi-sum-label { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:var(--cbm-text-muted); }
.kpi-sum-value { font-size:1.6rem; font-weight:800; color:var(--cbm-text); line-height:1.2; margin-top:.3rem; }
.kpi-sum-hint { font-size:.72rem; color:var(--cbm-text-muted); margin-top:.2rem; }
.kpi-row { display:grid; gap:1rem; margin-bottom:1rem; }
.kpi-row-2 { grid-template-columns:1.4fr 1fr; }
.kpi-row-2b { grid-template-columns:1fr 1fr; }
@media (max-width:1024px) { .kpi-row-2, .kpi-row-2b { grid-template-columns:1fr; } }
.kpi-card {
    background:var(--cbm-card-bg); border:1px solid var(--cbm-card-border);
    border-radius:1.125rem; padding:1.15rem 1.25rem; box-shadow:var(--cbm-card-shadow);
    display:flex; flex-direction:column; min-width:0;
}
.kpi-card-title { font-size:.9rem; font-weight:800; color:var(--cbm-text); }
.kpi-card-sub { font-size:.72rem; color:var(--cbm-text-muted); margin-top:.15rem; margin-bottom:.75rem; }
.kpi-chart-box { position:relative; height:16.25rem; }
.kpi-gauge-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; }
.kpi-gauge { text-align:center; position:relative; }
.kpi-gauge-canvas { position:relative; height:5.9375rem; }
.kpi-gauge-value { position:absolute; left:0; right:0; bottom:2px; font-size:1.35rem; font-weight:800; color:var(--cbm-text); }
.kpi-gauge-label { font-size:.75rem; font-weight:700; color:var(--cbm-text-muted); margin-top:.35rem; }
.kpi-gauge-badge { display:inline-block; margin-top:.3rem; font-size:.62rem; font-weight:800; padding:.15rem .5rem; border-radius:62.4375rem; }
.kpi-badge-ok { background:rgba(52,211,153,.15); color:#10b981; }
.kpi-badge-warn { background:rgba(251,191,36,.15); color:#d97706; }
.kpi-badge-bad { background:rgba(248,113,113,.15); color:#ef4444; }
</style>

<div id="sec-kpi" class="cbm-section-title cbm-section" style="scroll-margin-top:8.125rem;">
    KPI &amp; Performance ({{ $kpi['period'] ?? '' }})
</div>

<div class="kpi-wrap">
    {{-- Summary --}}
    <div class="kpi-summary-grid">
        <div class="kpi-sum-card" style="--kpi-accent:#3b82f6;">
            <div class="kpi-sum-label">Total Laporan (30 Hari)</div>
            <div class="kpi-sum-value">{{ number_format($kpiSummary['month_total'] ?? 0) }}</div>
            <div class="kpi-sum-hint">WO, DMI, NSRDI, CML, AC, ICT</div>
        </div>
        <div class="kpi-sum-card" style="--kpi-accent:#34d399;">
            <div class="kpi-sum-label">Closed / Open</div>
            <div class="kpi-sum-value">{{ number_format($kpiSummary['month_closed'] ?? 0) }} <span style="font-size:1rem;color:var(--cbm-text-muted);">/ {{ number_format($kpiSummary['month_open'] ?? 0) }}</span></div>
            <div class="kpi-sum-hint">Close rate {{ $kpiSummary['close_rate'] ?? 0 }}% (target {{ $kpiTarget }}%)</div>
        </div>
        <div class="kpi-sum-card" style="--kpi-accent:#a855f7;">
            <div class="kpi-sum-label">Rata-rata Harian</div>
            <div class="kpi-sum-value">{{ $kpiSummary['avg_daily'] ?? 0 }}</div>
            <div class="kpi-sum-hint">Tersibuk: {{ $kpiSummary['busiest_day'] ?? '-' }} ({{ $kpiSummary['busiest_count'] ?? 0 }})</div>
        </div>
        <div class="kpi-sum-card" style="--kpi-accent:#f59e0b;">
            <div class="kpi-sum-label">Man Hours WO (30 Hari)</div>
            <div class="kpi-sum-value">{{ number_format($kpiSummary['man_hours'] ?? 0, 1) }}</div>
            <div class="kpi-sum-hint">Akumulasi jam kerja WO 30 hari terakhir</div>
        </div>
        <div class="kpi-sum-card" style="--kpi-accent:#ef4444;">
            <div class="kpi-sum-label">NSRDI Open</div>
            <div class="kpi-sum-value">{{ $kpiSummary['nsrdi_open'] ?? 0 }}</div>
            <div class="kpi-sum-hint">{{ $kpiSummary['nsrdi_critical'] ?? 0 }} sudah lebih dari 30 hari</div>
        </div>
        <div class="kpi-sum-card" style="--kpi-accent:#06b6d4;">
            <div class="kpi-sum-label">Stasiun Aktif</div>
            <div class="kpi-sum-value">{{ $kpiSummary['active_stations'] ?? 0 }} <span style="font-size:1rem;color:var(--cbm-text-muted);">/ {{ $kpiSummary['total_stations'] ?? 0 }}</span></div>
            <div class="kpi-sum-hint">Stasiun dengan laporan 30 hari terakhir</div>
        </div>
    </div>

    {{-- Gauges --}}
    <div class="kpi-card" style="margin-bottom:1rem;">
        <div class="kpi-card-title">Gauge Ketercapaian Target</div>
        <div class="kpi-card-sub">Close rate 30 hari terakhir per modul, target {{ $kpiTarget }}%</div>
        <div class="kpi-gauge-grid" wire:ignore>
            @foreach($kpiGauges as $gaugeName => $gaugeValue)
                @php
                    $badgeClass = $gaugeValue >= $kpiTarget ? 'kpi-badge-ok' : ($gaugeValue >= $kpiTarget - 20 ? 'kpi-badge-warn' : 'kpi-badge-bad');
                    $badgeText = $gaugeValue >= $kpiTarget ? 'On Target' : ($gaugeValue >= $kpiTarget - 20 ? 'Mendekati' : 'Di Bawah Target');
                @endphp
                <div class="kpi-gauge">
                    <div class="kpi-gauge-canvas">
                        <canvas class="kpi-gauge-chart" data-value="{{ $gaugeValue }}" data-target="{{ $kpiTarget }}"></canvas>
                        <div class="kpi-gauge-value">{{ $gaugeValue }}%</div>
                    </div>
                    <div class="kpi-gauge-label">{{ $gaugeName }}</div>
                    <span class="kpi-gauge-badge {{ $badgeClass }}">{{ $badgeText }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Area + Donut --}}
    <div class="kpi-row kpi-row-2">
        <div class="kpi-card">
            <div class="kpi-card-title">Akumulasi Volume Laporan (30 Hari)</div>
            <div class="kpi-card-sub">Area chart kumulatif WO, DMI, NSRDI &amp; CML</div>
            <div class="kpi-chart-box" wire:ignore><canvas id="kpi-area-chart"></canvas></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-title">Komposisi Laporan per Modul</div>
            <div class="kpi-card-sub">Proporsi (%) 30 hari terakhir</div>
            <div class="kpi-chart-box" wire:ignore><canvas id="kpi-pie-chart"></canvas></div>
        </div>
    </div>

    {{-- Line + Aging --}}
    <div class="kpi-row kpi-row-2">
        <div class="kpi-card">
            <div class="kpi-card-title">Tren Close Rate Harian (30 Hari)</div>
            <div class="kpi-card-sub">Persentase laporan closed per hari vs garis target</div>
            <div class="kpi-chart-box" wire:ignore><canvas id="kpi-line-chart"></canvas></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-title">Aging NSRDI Open</div>
            <div class="kpi-card-sub">Umur laporan NSRDI yang masih open</div>
            <div class="kpi-chart-box" wire:ignore><canvas id="kpi-aging-chart"></canvas></div>
        </div>
    </div>

    {{-- Station bar + Scatter --}}
    <div class="kpi-row kpi-row-2b">
        <div class="kpi-card">
            <div class="kpi-card-title">Closed vs Open per Stasiun (30 Hari)</div>
            <div class="kpi-card-sub">Stasiun dari data master bandara aktif</div>
            <div class="kpi-chart-box" wire:ignore><canvas id="kpi-station-chart"></canvas></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card-title">Korelasi Volume vs Close Rate Stasiun</div>
            <div class="kpi-card-sub">Scatter: sumbu X jumlah laporan, sumbu Y close rate (%)</div>
            <div class="kpi-chart-box" wire:ignore><canvas id="kpi-scatter-chart"></canvas></div>
        </div>
    </div>
</div>

<script>
(function () {
    var kpiData = @json($kpi ?? []);
    var kpiCharts = [];

    function light() { var h = document.getElementById('cbm-html'); return h && h.classList.contains('cbm-light'); }
    function muted() { return light() ? '#64748b' : '#94a3b8'; }
    function grid() { return light() ? 'rgba(148,163,184,.18)' : 'rgba(255,255,255,.06)'; }
    function tip() {
        return { backgroundColor: light() ? '#ffffff' : '#1e293b', titleColor: light() ? '#0f172a' : '#f1f5f9',
                 bodyColor: muted(), borderColor: light() ? 'rgba(148,163,184,.3)' : 'rgba(255,255,255,.1)',
                 borderWidth: 1, padding: 10, cornerRadius: 10 };
    }
    function legend() { return { position: 'top', align: 'end', labels: { color: muted(), usePointStyle: true, pointStyle: 'circle', boxWidth: 8, font: { weight: '700', size: 11 } } }; }
    function axis(extra) { return Object.assign({ grid: { color: grid() }, ticks: { color: muted(), font: { weight: '700', size: 10 } }, border: { display: false } }, extra || {}); }

    function ensureChartJs(cb) {
        if (typeof Chart !== 'undefined') { return cb(); }
        if (!document.getElementById('kpi-chartjs-cdn')) {
            var s = document.createElement('script');
            s.id = 'kpi-chartjs-cdn';
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            document.head.appendChild(s);
        }
        setTimeout(function () { ensureChartJs(cb); }, 100);
    }

    function make(el, config) {
        if (!el) { return; }
        var existing = Chart.getChart(el);
        if (existing) { existing.destroy(); }
        kpiCharts.push(new Chart(el.getContext('2d'), config));
    }

    function gradient(el, rgb) {
        var g = el.getContext('2d').createLinearGradient(0, 0, 0, 260);
        g.addColorStop(0, 'rgba(' + rgb + ',.35)');
        g.addColorStop(1, 'rgba(' + rgb + ',0)');
        return g;
    }

    function init() {
        if (!document.getElementById('kpi-area-chart')) { return; }
        kpiCharts.forEach(function (c) { c.destroy(); });
        kpiCharts = [];

        /* Gauges */
        document.querySelectorAll('.kpi-gauge-chart').forEach(function (el) {
            var v = parseFloat(el.dataset.value || 0), t = parseFloat(el.dataset.target || 90);
            var color = v >= t ? '#34d399' : (v >= t - 20 ? '#fbbf24' : '#f87171');
            make(el, {
                type: 'doughnut',
                data: { datasets: [{ data: [v, Math.max(0, 100 - v)], backgroundColor: [color, light() ? 'rgba(148,163,184,.2)' : 'rgba(255,255,255,.07)'], borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, rotation: -90, circumference: 180, cutout: '78%',
                           plugins: { legend: { display: false }, tooltip: { enabled: false } } }
            });
        });

        var labels = kpiData.labels || [];

        /* Area: cumulative */
        var areaEl = document.getElementById('kpi-area-chart');
        var areaColors = { WO: '59,130,246', DMI: '245,158,11', NSRDI: '168,85,247', CML: '20,184,166' };
        var cum = kpiData.cumulative || {};
        make(areaEl, {
            type: 'line',
            data: { labels: labels, datasets: Object.keys(cum).map(function (k) {
                return { label: k, data: cum[k], fill: true, tension: .35, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4,
                         borderColor: 'rgb(' + areaColors[k] + ')', backgroundColor: gradient(areaEl, areaColors[k]) };
            }) },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                       plugins: { legend: legend(), tooltip: tip() },
                       scales: { x: axis({ grid: { display: false }, ticks: { color: muted(), maxTicksLimit: 8, font: { weight: '700', size: 10 } } }), y: axis({ beginAtZero: true }) } }
        });

        /* Donut / Pie: composition */
        var comp = kpiData.composition || {};
        var compKeys = Object.keys(comp);
        var compTotal = compKeys.reduce(function (s, k) { return s + comp[k]; }, 0);
        make(document.getElementById('kpi-pie-chart'), {
            type: 'doughnut',
            data: { labels: compKeys, datasets: [{ data: compKeys.map(function (k) { return comp[k]; }),
                    backgroundColor: ['#3b82f6', '#f59e0b', '#a855f7', '#14b8a6', '#22c55e', '#f472b6'],
                    borderColor: light() ? '#ffffff' : '#0d1117', borderWidth: 3, hoverOffset: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                       plugins: { legend: { position: 'right', labels: { color: muted(), usePointStyle: true, pointStyle: 'circle', font: { weight: '700', size: 11 } } },
                                  tooltip: Object.assign(tip(), { callbacks: { label: function (c) {
                                      var pct = compTotal > 0 ? (c.raw / compTotal * 100).toFixed(1) : 0;
                                      return ' ' + c.label + ': ' + c.raw + ' (' + pct + '%)';
                                  } } }) } }
        });

        /* Line: daily close rate vs target */
        make(document.getElementById('kpi-line-chart'), {
            type: 'line',
            data: { labels: labels, datasets: [
                { label: 'Close Rate %', data: kpiData.daily_rate || [], borderColor: '#60a5fa', backgroundColor: '#60a5fa',
                  borderWidth: 2.5, tension: .35, spanGaps: true, pointRadius: 3, pointHoverRadius: 5 },
                { label: 'Target', data: labels.map(function () { return kpiData.target || 90; }), borderColor: '#f87171',
                  borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                       plugins: { legend: legend(), tooltip: tip() },
                       scales: { x: axis({ grid: { display: false }, ticks: { color: muted(), maxTicksLimit: 8, font: { weight: '700', size: 10 } } }),
                                 y: axis({ min: 0, max: 100, ticks: { color: muted(), callback: function (v) { return v + '%'; } } }) } }
        });

        /* Bar (horizontal): NSRDI aging */
        var aging = kpiData.aging || {};
        make(document.getElementById('kpi-aging-chart'), {
            type: 'bar',
            data: { labels: Object.keys(aging), datasets: [{ label: 'NSRDI Open', data: Object.values(aging),
                    backgroundColor: ['#34d399', '#fbbf24', '#fb923c', '#f87171'], borderRadius: 6, barPercentage: .65 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                       plugins: { legend: { display: false }, tooltip: tip() },
                       scales: { x: axis({ beginAtZero: true, ticks: { color: muted(), precision: 0 } }), y: axis({ grid: { display: false } }) } }
        });

        /* Column: station closed vs open */
        var sb = kpiData.station_bar || { labels: [], closed: [], open: [] };
        make(document.getElementById('kpi-station-chart'), {
            type: 'bar',
            data: { labels: sb.labels, datasets: [
                { label: 'Closed', data: sb.closed, backgroundColor: 'rgba(52,211,153,.85)', borderRadius: 4 },
                { label: 'Open', data: sb.open, backgroundColor: 'rgba(248,113,113,.85)', borderRadius: 4 }
            ] },
            options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                       plugins: { legend: legend(), tooltip: tip() },
                       scales: { x: axis({ stacked: true, grid: { display: false } }), y: axis({ stacked: true, beginAtZero: true, ticks: { color: muted(), precision: 0 } }) } }
        });

        /* Scatter: volume vs close rate */
        var pts = kpiData.scatter || [];
        make(document.getElementById('kpi-scatter-chart'), {
            type: 'scatter',
            data: { datasets: [{ label: 'Stasiun', data: pts,
                    backgroundColor: pts.map(function (p) { return p.y >= (kpiData.target || 90) ? 'rgba(52,211,153,.8)' : 'rgba(96,165,250,.8)'; }),
                    pointRadius: 7, pointHoverRadius: 10 }] },
            options: { responsive: true, maintainAspectRatio: false,
                       plugins: { legend: { display: false }, tooltip: Object.assign(tip(), { callbacks: { label: function (c) {
                           return ' ' + c.raw.label + ': ' + c.raw.x + ' laporan, ' + c.raw.y + '% closed';
                       } } }) },
                       scales: { x: axis({ beginAtZero: true, title: { display: true, text: 'Jumlah Laporan', color: muted() } }),
                                 y: axis({ min: 0, max: 100, title: { display: true, text: 'Close Rate %', color: muted() } }) } }
        });
    }

    function boot() { ensureChartJs(function () { requestAnimationFrame(init); }); }
    window.__kpiBoot = boot;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
    if (!window.__kpiListenersBound) {
        window.__kpiListenersBound = true;
        document.addEventListener('livewire:navigated', function () { window.__kpiBoot && window.__kpiBoot(); });
        new MutationObserver(function () { window.__kpiBoot && window.__kpiBoot(); })
            .observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    }
})();
</script>
