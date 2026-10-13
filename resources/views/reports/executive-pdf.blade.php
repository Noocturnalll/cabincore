<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Production Report - {{ $docNumber }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            padding: 20px;
            font-size: 11px;
            line-height: 1.4;
        }
        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .btn-print {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        .btn-close {
            background: #334155;
            color: #ffffff;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
        }
        .pdf-container {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 24px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            max-width: 1100px;
            margin: 0 auto;
        }
        /* Header Official */
        .official-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .company-title {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .sub-title {
            font-size: 13px;
            font-weight: 700;
            color: #334155;
            margin-top: 2px;
        }
        .meta-text {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 4px;
        }
        .doc-badge {
            text-align: right;
            font-size: 9.5px;
            color: #475569;
        }
        .doc-no {
            font-family: monospace;
            font-weight: 700;
            font-size: 11px;
            color: #0f172a;
            background: #f1f5f9;
            padding: 3px 6px;
            border-radius: 3px;
            border: 1px solid #e2e8f0;
            display: inline-block;
            margin-bottom: 4px;
        }

        /* Summary Cards */
        .cards-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-card {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px 12px;
            background: #f8fafc;
        }
        .stat-title {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .stat-sub {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 3px;
        }

        /* Section Title */
        .section-header {
            background: #f1f5f9;
            border-left: 4px solid #0f172a;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            margin: 14px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        /* Tables */
        table.pdf-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9.5px;
        }
        table.pdf-table th {
            background: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: left;
            padding: 5px 7px;
            border: 1px solid #0f172a;
            text-transform: uppercase;
            font-size: 9px;
        }
        table.pdf-table td {
            padding: 4.5px 7px;
            border: 1px solid #cbd5e1;
            color: #1e293b;
        }
        table.pdf-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }

        /* Signatures */
        .sig-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 24px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
            page-break-inside: avoid;
        }
        .sig-box {
            text-align: center;
            font-size: 9.5px;
        }
        .sig-role {
            font-weight: 700;
            color: #334155;
            margin-bottom: 45px;
            text-transform: uppercase;
        }
        .sig-name {
            font-weight: 700;
            color: #0f172a;
            border-bottom: 1px solid #334155;
            display: inline-block;
            min-width: 140px;
            padding-bottom: 2px;
        }
        .sig-title {
            color: #64748b;
            font-size: 8.5px;
            margin-top: 2px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .pdf-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    {{-- Toolbar (Hidden during print) --}}
    <div class="no-print-bar">
        <div>
            <strong>Dokumen Resmi Produksi Lion Group</strong> &mdash; Siap cetak atau simpan ke PDF untuk meeting direksi.
        </div>
        <div style="display:flex;gap:8px;">
            <button type="button" onclick="window.print()" class="btn-print">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Cetak / Simpan PDF
            </button>
            <a href="{{ route('reports.executive') }}" class="btn-close">Kembali ke Web</a>
        </div>
    </div>

    {{-- Document Container --}}
    <div class="pdf-container">

        {{-- Official Header (NO 'engineer' or 'engineering' keyword) --}}
        <div class="official-header">
            <div>
                <div class="company-title">LION GROUP AIRCRAFT CABIN TECHNICIAN</div>
                <div class="sub-title">PRODUCTION SUMMARY &amp; DAILY EXECUTIVE REPORT</div>
                <div class="meta-text">
                    Periode Laporan: <strong>{{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }}</strong> s/d <strong>{{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}</strong>
                    &bull; Stasiun: <strong>{{ $station ?: 'Nasional (Semua Stasiun)' }}</strong>
                </div>
            </div>
            <div class="doc-badge">
                <div class="doc-no">{{ $docNumber }}</div>
                <div>Dicetak: {{ $printedAt }}</div>
                <div>Oleh: {{ $printedBy }}</div>
            </div>
        </div>

        {{-- 3 Pillars KPI Summary --}}
        <div class="cards-row">
            {{-- Pilar 1: CBM --}}
            <div class="stat-card">
                <div class="stat-title">1. Cabin Maintenance (CBM)</div>
                <div class="stat-value">{{ $report['kpis']['cbm']['rate'] }}%</div>
                <div class="stat-sub">
                    Closed: <strong>{{ number_format($report['kpis']['cbm']['closed']) }}</strong> / {{ number_format($report['kpis']['cbm']['total']) }} Total
                    &bull; Open: <span style="color:#dc2626;font-weight:700;">{{ number_format($report['kpis']['cbm']['open']) }}</span>
                </div>
                <div class="stat-sub" style="font-size:8.5px;color:#475569;margin-top:4px;">
                    Target: {{ number_format($report['kpis']['cbm']['target_period']) }} (Achv: {{ $report['kpis']['cbm']['target_achievement'] }}%) |
                    DJA: {{ $report['kpis']['cbm']['planned'] }} Plan / {{ $report['kpis']['cbm']['unplanned'] }} Unplan
                </div>
            </div>

            {{-- Pilar 2: Painting --}}
            <div class="stat-card">
                <div class="stat-title">2. Aircraft Painting</div>
                <div class="stat-value">{{ number_format($report['kpis']['painting']['total']) }} <span style="font-size:12px;font-weight:600;">Job</span></div>
                <div class="stat-sub">
                    Planned (DJA): <strong>{{ $report['kpis']['painting']['planned'] }}</strong> &bull; Unplanned: <strong>{{ $report['kpis']['painting']['unplanned'] }}</strong>
                </div>
                <div class="stat-sub" style="font-size:8.5px;color:#475569;margin-top:4px;">
                    Closed: {{ $report['kpis']['painting']['closed'] }} (Rate: {{ $report['kpis']['painting']['rate'] }}%) &bull; Open: {{ $report['kpis']['painting']['open'] }}
                </div>
            </div>

            {{-- Pilar 3: Cleaning --}}
            <div class="stat-card">
                <div class="stat-title">3. Aircraft Cleaning (AIEC)</div>
                <div class="stat-value">{{ number_format($report['kpis']['cleaning']['total']) }} <span style="font-size:12px;font-weight:600;">Pesawat</span></div>
                <div class="stat-sub">
                    DCI: <strong>{{ $report['kpis']['cleaning']['dci'] }}</strong> &bull; DCE: <strong>{{ $report['kpis']['cleaning']['dce'] }}</strong> &bull; Transit: <strong>{{ $report['kpis']['cleaning']['transit'] }}</strong>
                </div>
                <div class="stat-sub" style="font-size:8.5px;color:#475569;margin-top:4px;">
                    Rate: {{ $report['kpis']['cleaning']['rate'] }}% &bull; General: {{ $report['kpis']['cleaning']['general'] }} &bull; DBI: {{ $report['kpis']['cleaning']['dbi'] }}
                </div>
            </div>
        </div>

        {{-- Matriks Stasiun --}}
        <div class="section-header">Matriks Hasil Produksi per Stasiun Kerja</div>
        <table class="pdf-table">
            <thead>
                <tr>
                    <th style="width:35px;" class="text-center">No</th>
                    <th style="width:70px;">Stasiun</th>
                    <th class="text-center">NSRDI (Cl/Tot)</th>
                    <th class="text-center">WO (Cl/Tot)</th>
                    <th class="text-center">DMI (Cl/Tot)</th>
                    <th class="text-center">CML (Cl/Tot)</th>
                    <th class="text-center">Total CBM</th>
                    <th class="text-center">Closed CBM</th>
                    <th class="text-center">Open CBM</th>
                    <th class="text-center">Rate CBM</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['matrices']['cbm_stations'] as $idx => $m)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td class="font-bold">{{ $m['station'] }}</td>
                        <td class="text-center">{{ $m['nsrdi_closed'] }} / {{ $m['nsrdi'] }}</td>
                        <td class="text-center">{{ $m['wo_closed'] }} / {{ $m['wo'] }}</td>
                        <td class="text-center">{{ $m['dmi_closed'] }} / {{ $m['dmi'] }}</td>
                        <td class="text-center">{{ $m['cml_closed'] }} / {{ $m['cml'] }}</td>
                        <td class="text-center font-bold" style="color:#2563eb;">{{ $m['total'] }}</td>
                        <td class="text-center font-bold" style="color:#16a34a;">{{ $m['closed'] }}</td>
                        <td class="text-center" style="{{ $m['open'] > 0 ? 'color:#dc2626;font-weight:700;' : '' }}">{{ $m['open'] }}</td>
                        <td class="text-center font-bold">{{ $m['rate'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center" style="padding:10px;color:#64748b;">Tidak ada data operasional pada periode yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Analisis Pareto & Top Issues --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <div class="section-header">Top Kategori Defect Temuan Kabin</div>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Kategori / Sistem</th>
                            <th class="text-center" style="width:70px;">Kasus</th>
                            <th class="text-center" style="width:70px;">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($report['pareto']['top_defects'], 0, 5) as $def)
                            <tr>
                                <td>{{ $def['name'] }}</td>
                                <td class="text-center font-bold">{{ $def['count'] }}</td>
                                <td class="text-center">{{ $def['percentage'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center" style="color:#64748b;">N/A</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                <div class="section-header">Top 5 Kendala Status Pekerjaan Open</div>
                <table class="pdf-table">
                    <thead>
                        <tr>
                            <th>Alasan / Kode Kendala</th>
                            <th class="text-center" style="width:70px;">Kasus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(array_slice($report['pareto']['top_reasons_open'], 0, 5) as $rea)
                            <tr>
                                <td class="font-bold">{{ $rea['reason'] }}</td>
                                <td class="text-center font-bold" style="color:#dc2626;">{{ $rea['count'] }} job</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center" style="color:#16a34a;font-weight:600;">Semua pekerjaan berstatus Closed ✓</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Signatures --}}
        <div class="sig-container">
            <div class="sig-box">
                <div class="sig-role">Prepared By</div>
                <div class="sig-name">{{ $printedBy }}</div>
                <div class="sig-title">PIC Production &amp; Bank Data</div>
            </div>
            <div class="sig-box">
                <div class="sig-role">Checked By</div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-title">Manager Aircraft Cabin Technician</div>
            </div>
            <div class="sig-box">
                <div class="sig-role">Approved By</div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-title">General Manager Aircraft Cabin Technician</div>
            </div>
        </div>

    </div>

</body>
</html>
