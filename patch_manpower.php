<?php

$files = glob('c:/Users/achai/cbm/resources/views/livewire/dashboard-*.blade.php');

$htmlToInject = <<<'HTML'
{{-- â• â• â• â•  MAN POWER & MAN HOURS ROW â• â• â• â•  --}}
<div class="cbm-kpi-row" style="margin-bottom: 1.5rem; grid-template-columns: repeat(2, 1fr);">
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(59,130,246,.05) 0%, rgba(99,102,241,.02) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1);box-shadow:0 6px 16px rgba(59,130,246,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4.5 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM14.25 8.625a3.375 3.375 0 116.75 0 3.375 3.375 0 01-6.75 0zM1.5 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM17.25 19.128l-.001.144a2.25 2.25 0 01-.233.96 10.088 10.088 0 005.06-1.01.75.75 0 00.42-.643 4.875 4.875 0 00-6.957-4.611 8.586 8.586 0 011.71 5.157v.003z" /></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Total Man Power (PIC)</div>
            <div class="cbm-kpi-value">{{ $stats['man_power'] ?? 0 }} <span style="font-size:1rem;color:var(--cbm-text-muted);">Personel</span></div>
            <div class="cbm-kpi-sub">Total teknisi / PIC di sistem</div>
        </div>
    </div>
    <div class="cbm-kpi-card" style="background: linear-gradient(145deg, rgba(168,85,247,.05) 0%, rgba(236,72,153,.02) 100%);">
        <div class="cbm-kpi-icon" style="background:linear-gradient(135deg,#a855f7,#ec4899);box-shadow:0 6px 16px rgba(168,85,247,.4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z" clip-rule="evenodd" /></svg>
        </div>
        <div>
            <div class="cbm-kpi-label">Man Hours (WO)</div>
            <div class="cbm-kpi-value">{{ $stats['man_hours'] ?? 0 }} <span style="font-size:1rem;color:var(--cbm-text-muted);">Jam</span></div>
            <div class="cbm-kpi-sub">Total estimasi man hour dari log WO hari ini</div>
        </div>
    </div>
</div>
HTML;

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'MAN POWER') === false) {
        $target = '<div class="cbm-kpi-row">'; // the first one
        $pos = strpos($content, $target);
        if ($pos !== false) {
            $content = substr_replace($content, $htmlToInject."\n", $pos, 0);
            $content = str_replace('â€”', '&mdash;', $content);
            $content = str_replace('â€“', '&ndash;', $content);
            file_put_contents($file, $content);
        }
    }
}
