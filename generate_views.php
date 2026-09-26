<?php

$bladeTemplate = <<<'BLADE'
<div>
    <div class="cbm-page-header">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: linear-gradient(135deg, {COLOR_1}, {COLOR_2}); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; box-shadow: 0 8px 16px {SHADOW};">
                {ICON}
            </div>
            <div>
                <h1 class="cbm-greeting" style="font-size: 1.5rem;">{TITLE}</h1>
                <p class="cbm-greeting-sub">{SUBTITLE}</p>
            </div>
        </div>
    </div>

    <div class="cbm-card">
        <div class="cbm-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--cbm-card-border); padding-bottom: 1.25rem; margin-bottom: 0;">
            <div style="position: relative; width: 300px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); width: 1.25rem; height: 1.25rem; color: var(--cbm-text-muted);">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                </svg>
                <input type="text" placeholder="Cari {TITLE}..." class="cbm-input" style="padding-left: 2.75rem; width: 100%; height: 2.75rem; border-radius: .875rem; background: var(--cbm-bg); border: 1px solid var(--cbm-card-border); color: var(--cbm-text);" wire:model.live.debounce.300ms="search">
            </div>
            <button class="cbm-btn cbm-btn-primary" style="background: linear-gradient(135deg, {COLOR_1}, {COLOR_2}); box-shadow: 0 4px 12px {SHADOW}; border: none; color: white; padding: 0 1.5rem; height: 2.75rem; border-radius: .875rem; font-weight: 700; display: flex; align-items: center; gap: .5rem; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;">
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
                Tambah Data
            </button>
        </div>
        
        <div class="cbm-card-body" style="padding: 0; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: var(--cbm-bg); border-bottom: 1px solid var(--cbm-card-border);">
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">{COL_1}</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">{COL_2}</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">{COL_3}</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em;">Status</th>
                        <th style="padding: 1rem 1.5rem; font-size: .75rem; font-weight: 700; color: var(--cbm-text-muted); text-transform: uppercase; letter-spacing: .05em; text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Mock Data Row 1 -->
                    <tr style="border-bottom: 1px solid var(--cbm-card-border); transition: background 0.2s;">
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text); font-weight: 600;">{VAL_1_1}</td>
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text-sub);">{VAL_1_2}</td>
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text-sub);">{VAL_1_3}</td>
                        <td style="padding: 1rem 1.5rem;">
                            <span style="background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34,197,94,0.2);">Aktif</span>
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right;">
                            <button style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer; padding: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path d="M2.695 14.763l-1.262 3.152a.5.5 0 00.65.65l3.152-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                            <button style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; margin-left: 0.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                        </td>
                    </tr>
                    <!-- Mock Data Row 2 -->
                    <tr style="border-bottom: 1px solid var(--cbm-card-border); transition: background 0.2s;">
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text); font-weight: 600;">{VAL_2_1}</td>
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text-sub);">{VAL_2_2}</td>
                        <td style="padding: 1rem 1.5rem; color: var(--cbm-text-sub);">{VAL_2_3}</td>
                        <td style="padding: 1rem 1.5rem;">
                            <span style="background: rgba(34,197,94,0.1); color: #22c55e; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; border: 1px solid rgba(34,197,94,0.2);">Aktif</span>
                        </td>
                        <td style="padding: 1rem 1.5rem; text-align: right;">
                            <button style="background: none; border: none; color: var(--cbm-text-muted); cursor: pointer; padding: 0.25rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path d="M2.695 14.763l-1.262 3.152a.5.5 0 00.65.65l3.152-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg></button>
                            <button style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.25rem; margin-left: 0.5rem;"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="width: 1.25rem; height: 1.25rem;"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="cbm-card-footer" style="padding: 1rem 1.5rem; border-top: 1px solid var(--cbm-card-border); display: flex; justify-content: space-between; align-items: center; color: var(--cbm-text-sub); font-size: 0.8125rem;">
            <div>Menampilkan 1 sampai 10 dari 2 data</div>
            <div style="display: flex; gap: 0.5rem;">
                <button style="padding: 0.35rem 0.75rem; border: 1px solid var(--cbm-card-border); background: var(--cbm-bg); border-radius: 0.5rem; cursor: not-allowed; opacity: 0.5;">Sebelumnya</button>
                <button style="padding: 0.35rem 0.75rem; border: 1px solid var(--cbm-card-border); background: var(--cbm-bg); border-radius: 0.5rem; cursor: not-allowed; opacity: 0.5;">Selanjutnya</button>
            </div>
        </div>
    </div>
</div>
BLADE;

$pages = [
    'master/airports' => [
        'TITLE' => 'Bandara / Stasiun', 'SUBTITLE' => 'Kelola data bandara dan stasiun operasional',
        'COLOR_1' => '#0ea5e9', 'COLOR_2' => '#3b82f6', 'SHADOW' => 'rgba(14,165,233,0.3)',
        'ICON' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>',
        'COL_1' => 'Kode IATA', 'COL_2' => 'Nama Bandara', 'COL_3' => 'Kota',
        'VAL_1_1' => 'CGK', 'VAL_1_2' => 'Soekarno-Hatta', 'VAL_1_3' => 'Tangerang',
        'VAL_2_1' => 'KNO', 'VAL_2_2' => 'Kualanamu', 'VAL_2_3' => 'Deli Serdang',
    ],
    'master/aircraft' => [
        'TITLE' => 'Registrasi Pesawat', 'SUBTITLE' => 'Kelola armada pesawat dan tipe pesawat',
        'COLOR_1' => '#8b5cf6', 'COLOR_2' => '#6366f1', 'SHADOW' => 'rgba(139,92,246,0.3)',
        'ICON' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>',
        'COL_1' => 'Registrasi', 'COL_2' => 'Tipe Pesawat', 'COL_3' => 'Maskapai',
        'VAL_1_1' => 'PK-LBF', 'VAL_1_2' => 'Boeing 737-800', 'VAL_1_3' => 'Batik Air',
        'VAL_2_1' => 'PK-LTI', 'VAL_2_2' => 'Airbus A320', 'VAL_2_3' => 'Lion Air',
    ],
    'master/categories' => [
        'TITLE' => 'Kategori Pekerjaan', 'SUBTITLE' => 'Kelola kategori pekerjaan dan temuan',
        'COLOR_1' => '#f59e0b', 'COLOR_2' => '#ea580c', 'SHADOW' => 'rgba(245,158,11,0.3)',
        'ICON' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>',
        'COL_1' => 'Kode Kategori', 'COL_2' => 'Nama Kategori', 'COL_3' => 'Deskripsi',
        'VAL_1_1' => 'CAT-01', 'VAL_1_2' => 'Cabin Appearance', 'VAL_1_3' => 'Kebersihan dan estetika kabin',
        'VAL_2_1' => 'CAT-02', 'VAL_2_2' => 'Seat Damage', 'VAL_2_3' => 'Kerusakan pada kursi penumpang',
    ],
    'modules/aircraft-cleaning/general' => [
        'TITLE' => 'General Cleaning', 'SUBTITLE' => 'Log dan laporan pencucian umum pesawat',
        'COLOR_1' => '#22c55e', 'COLOR_2' => '#10b981', 'SHADOW' => 'rgba(34,197,94,0.3)',
        'ICON' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.84 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" /></svg>',
        'COL_1' => 'Registrasi', 'COL_2' => 'Tanggal Masuk', 'COL_3' => 'Shift/Regu',
        'VAL_1_1' => 'PK-LBF', 'VAL_1_2' => '24 Sep 2026', 'VAL_1_3' => 'Alpha',
        'VAL_2_1' => 'PK-LTI', 'VAL_2_2' => '23 Sep 2026', 'VAL_2_3' => 'Bravo',
    ],
    'modules/aircraft-cleaning/interior' => [
        'TITLE' => 'Interior Cleaning (DCI)', 'SUBTITLE' => 'Deep Cleaning Interior pesewat (DCI)',
        'COLOR_1' => '#3b82f6', 'COLOR_2' => '#06b6d4', 'SHADOW' => 'rgba(59,130,246,0.3)',
        'ICON' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
        'COL_1' => 'WO Number', 'COL_2' => 'Registrasi', 'COL_3' => 'Tim DCI',
        'VAL_1_1' => 'WO-DCI-001', 'VAL_1_2' => 'PK-BGF', 'VAL_1_3' => 'Tim A',
        'VAL_2_1' => 'WO-DCI-002', 'VAL_2_2' => 'PK-LOO', 'VAL_2_3' => 'Tim B',
    ],
    'modules/aircraft-cleaning/exterior' => [
        'TITLE' => 'Exterior Cleaning (DCE)', 'SUBTITLE' => 'Deep Cleaning Exterior pesawat (DCE)',
        'COLOR_1' => '#a855f7', 'COLOR_2' => '#d946ef', 'SHADOW' => 'rgba(168,85,247,0.3)',
        'ICON' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.75rem;height:1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 21v-4.872c0-1.144-.45-2.247-1.253-3.064l-2.074-2.115A5.85 5.85 0 014.125 6.75c0-1.554 1.26-2.813 2.813-2.813 1.135 0 2.115.68 2.584 1.666m10.353 3.651c.365 1.58.106 3.238-.724 4.606l-2.074 2.115c-.803.817-1.253 1.92-1.253 3.064V21m-4.5-8.25v-3.75m0 0a2.25 2.25 0 00-4.5 0m4.5 0a2.25 2.25 0 014.5 0" /></svg>',
        'COL_1' => 'WO Number', 'COL_2' => 'Registrasi', 'COL_3' => 'Area Cuci',
        'VAL_1_1' => 'WO-DCE-001', 'VAL_1_2' => 'PK-BGF', 'VAL_1_3' => 'Hangar 1',
        'VAL_2_1' => 'WO-DCE-002', 'VAL_2_2' => 'PK-LOO', 'VAL_2_3' => 'Apron B',
    ],
];

foreach ($pages as $path => $data) {
    $content = $bladeTemplate;
    foreach ($data as $key => $val) {
        $content = str_replace('{'.$key.'}', $val, $content);
    }
    $filePath = "c:/Users/achai/cbm/resources/views/livewire/{$path}.blade.php";
    file_put_contents($filePath, $content);
}

echo 'All views updated successfully!';
