<?php

$files = glob('c:/Users/achai/cbm/resources/views/livewire/dashboard-*.blade.php');

$htmlToInject = <<<'HTML'
    <div class="cbm-charts-row" style="margin-top: 1.5rem; grid-template-columns: 1fr;">
        <div class="cbm-chart-card">
            <div class="cbm-card-header">
                <div>
                    <div class="cbm-card-title">NSRDI Overdue (Status: Open)</div>
                    <div class="cbm-card-sub">Laporan NSRDI yang melewati batas Plan Date (Batik, Lion, SAJ, Wings)</div>
                </div>
            </div>
            <div class="cbm-chart-container" wire:ignore>
                <canvas id="nsrdi-overdue-chart" height="230"
                    data-values='@json(array_values($stats["nsrdi_overdue"] ?? []))'
                    data-labels='@json(array_keys($stats["nsrdi_overdue"] ?? []))'>
                </canvas>
            </div>
        </div>
    </div>
HTML;

$jsToInject = <<<'JS'
    /* ─── NSRDI OVERDUE CHART ─── */
    var nsrdiEl = document.getElementById('nsrdi-overdue-chart');
    if (nsrdiEl) {
        var nLabels = JSON.parse(nsrdiEl.dataset.labels || '[]');
        var nValues = JSON.parse(nsrdiEl.dataset.values || '[]');
        new Chart(nsrdiEl.getContext('2d'), {
            type: 'bar',
            data: {
                labels: nLabels,
                datasets: [{
                    label: 'Overdue (Open)',
                    data: nValues,
                    backgroundColor: 'rgba(248,113,113,.85)',
                    borderRadius: 5,
                    barPercentage: 0.4,
                    categoryPercentage: 0.6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display:false },
                    tooltip: { backgroundColor:tooltipBg(), titleColor:textColor(), bodyColor:mutedColor(), borderColor:isLight?'rgba(148,163,184,.3)':'rgba(255,255,255,.1)', borderWidth:1, padding:12, cornerRadius:10 }
                },
                scales: {
                    x: { grid:{display:false}, ticks:{color:mutedColor(),font:{weight:'700',size:11}}, border:{display:false} },
                    y: { grid:{color:gridColor()}, ticks:Object.assign({color:mutedColor(),font:{weight:'700'}}, cbmIntTicks()), border:{display:false} }
                }
            }
        });
    }
JS;

foreach ($files as $file) {
    $content = file_get_contents($file);

    // Inject HTML below the bottom table if not already injected
    if (strpos($content, 'id="nsrdi-overdue-chart"') === false) {
        // Find the end of the bottom table div
        $tableEndMarker = "    </div>\n</div>\n\n<script";
        if (strpos($content, $tableEndMarker) !== false) {
            $content = str_replace($tableEndMarker, "    </div>\n</div>\n\n".$htmlToInject."\n\n<script", $content);
        } else {
            // fallback: before <script
            $content = preg_replace('/<script>/i', $htmlToInject."\n<script>", $content);
        }

        // Inject JS before closing function }
        $jsTarget = 'function initCharts() {';
        if (strpos($content, $jsTarget) !== false) {
            // we want to put it inside initCharts, maybe before the end of the function
            $jsEndMarker = '    } // end initCharts';
            if (strpos($content, $jsEndMarker) !== false) {
                $content = str_replace($jsEndMarker, $jsToInject."\n".$jsEndMarker, $content);
            } else {
                // fallback to finding the last chart block
                $fallbackTarget = 'ictMonthlyChart = new Chart';
                if (strpos($content, $fallbackTarget) !== false) {
                    $content = str_replace($fallbackTarget, $jsToInject."\n\n        ".$fallbackTarget, $content);
                }
            }
        }
        file_put_contents($file, $content);
        echo "Updated $file\n";
    }
}
