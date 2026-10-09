<?php

/**
 * Master data: the lookup lists the system is configured from. Nothing here is hard-coded in the screens - change a
 * value in Data Master and the calculations follow. Each type gets two permissions, master.<type>.view and
 * master.<type>.manage, so a role can be allowed to read a list without being able to change it.
 *
 *  label    menu / page title
 *  fields   attribute key => [label, type text|number|select, required, options]
 *  defaults rows created by MasterDataSeeder (code => [label, attrs])
 */
return [
    'types' => [
        'team' => [
            'label' => 'Tim Kerja',
            'subtitle' => 'Tim yang tampil di roster dan dashboard. "Hitung kapasitas" menentukan apakah timnya masuk perhitungan man hours.',
            'fields' => [
                'capacity' => ['Hitung kapasitas', 'select', true, ['Ya', 'Tidak']],
                'division' => ['Divisi pemilik', 'text', false],
            ],
            'defaults' => [
                'CBM' => ['Cabin Maintenance', ['capacity' => 'Ya', 'division' => 'Cabin']],
                'AIEC' => ['Aircraft Interior Exterior Cleaning', ['capacity' => 'Ya', 'division' => 'AIEC']],
                'PAINTING' => ['Aircraft Painting', ['capacity' => 'Ya', 'division' => 'Painting']],
                'IRREG' => ['Team Irreg', ['capacity' => 'Ya', 'division' => 'Cabin']],
                'FINISHING' => ['Aircraft Finishing', ['capacity' => 'Ya', 'division' => 'Finishing']],
                'PI' => ['Production Inspection', ['capacity' => 'Tidak', 'division' => '']],
                'COD' => ['Controller On Duty', ['capacity' => 'Tidak', 'division' => '']],
            ],
        ],

        'shift' => [
            'label' => 'Shift & Jam Efektif',
            'subtitle' => 'Jam efektif per orang per shift (shift 12 jam dikurangi istirahat dan serah terima). Dipakai untuk kapasitas man hours.',
            'fields' => [
                'effective_hours' => ['Jam efektif', 'number', true],
            ],
            'defaults' => [
                'PAGI' => ['Pagi', ['effective_hours' => 8]],
                'SIANG' => ['Siang', ['effective_hours' => 8]],
                'MALAM' => ['Malam', ['effective_hours' => 10]],
            ],
        ],

        'attendance_status' => [
            'label' => 'Status Presensi',
            'subtitle' => 'Kode status yang dikenali impor presensi dan roster, beserta kelompoknya untuk perhitungan disiplin.',
            'fields' => [
                'group' => ['Kelompok', 'select', true, ['hadir', 'sakit', 'cuti', 'izin', 'alpa', 'libur', 'training']],
            ],
            'defaults' => [
                'H' => ['Hadir', ['group' => 'hadir']],
                'S' => ['Sakit', ['group' => 'sakit']],
                'C' => ['Cuti', ['group' => 'cuti']],
                'I' => ['Izin', ['group' => 'izin']],
                'A' => ['Alpa (tanpa keterangan)', ['group' => 'alpa']],
                'OFF' => ['Libur', ['group' => 'libur']],
                'P00' => ['Training', ['group' => 'training']],
            ],
        ],

        'attendance_rule' => [
            'label' => 'Aturan Disiplin',
            'subtitle' => 'Ambang batas penilaian presensi. Nilai diubah di sini tanpa mengubah program.',
            'fields' => [
                'value' => ['Nilai', 'number', true],
                'unit' => ['Satuan', 'text', false],
            ],
            'defaults' => [
                'LATE_TOLERANCE_MIN' => ['Toleransi terlambat', ['value' => 10, 'unit' => 'menit']],
                'LATE_MAX_PER_MONTH' => ['Terlambat dianggap sering bila lebih dari', ['value' => 3, 'unit' => 'kali / bulan']],
                'SICK_MAX_PER_MONTH' => ['Sakit dianggap terlalu banyak bila lebih dari', ['value' => 3, 'unit' => 'hari / bulan']],
                'LEAVE_MAX_PER_MONTH' => ['Cuti dianggap terlalu banyak bila lebih dari', ['value' => 5, 'unit' => 'hari / bulan']],
                'DILIGENT_MIN_PERCENT' => ['Rajin: hadir tepat waktu minimal', ['value' => 97, 'unit' => '% dari hari kerja']],
                'EARLY_LEAVE_TOLERANCE_MIN' => ['Toleransi pulang lebih awal', ['value' => 10, 'unit' => 'menit']],
            ],
        ],

        'asset_category' => [
            'label' => 'Kategori Asset',
            'subtitle' => 'Pengelompokan asset (Stretcher, GSE, Tools, APD, ...).',
            'fields' => [],
            'defaults' => [
                'STRETCHER' => ['Stretcher (tandu pesawat)', []],
                'GSE' => ['Ground Support Equipment', []],
                'TOOLS' => ['Tools', []],
                'APD' => ['Alat Pelindung Diri', []],
                'CHEMICAL' => ['Chemical & Consumable', []],
                'IT' => ['Perangkat IT', []],
                'LAINNYA' => ['Lainnya', []],
            ],
        ],

        'asset_condition' => [
            'label' => 'Kondisi Asset',
            'subtitle' => 'Kondisi yang dipilih saat mendata asset. "Bisa dipinjamkan" menentukan apakah asset ikut dihitung tersedia.',
            'fields' => [
                'available' => ['Bisa dipinjamkan', 'select', true, ['Ya', 'Tidak']],
            ],
            'defaults' => [
                'BAIK' => ['Baik', ['available' => 'Ya']],
                'PERBAIKAN' => ['Perlu perbaikan', ['available' => 'Tidak']],
                'RUSAK' => ['Rusak', ['available' => 'Tidak']],
                'HILANG' => ['Hilang', ['available' => 'Tidak']],
            ],
        ],

        'integration' => [
            'label' => 'Pengaturan Integrasi',
            'subtitle' => 'Saklar sinkronisasi otomatis. "Ya" = hasil sheet langsung diterapkan; "Tidak" = disiapkan sebagai pratinjau dan menunggu tombol Terapkan di Laporan Leader.',
            'fields' => [
                'value' => ['Nilai', 'select', true, ['Ya', 'Tidak']],
            ],
            'defaults' => [
                'AUTO_APPLY_CBM_CLOSING' => ['Terapkan otomatis closing CBM dari sheet', ['value' => 'Tidak']],
            ],
        ],

        'kpi_target' => [
            'label' => 'Target Closed per Hari',
            'subtitle' => 'Target NSRDI/WO closed per hari per station dan AOC (dari workbook resume). Kode: STATION-AOC, contoh CGK-JT.',
            'fields' => [
                'station' => ['Station', 'text', true],
                'aoc' => ['AOC', 'text', true],
                'target' => ['Target / hari', 'number', true],
            ],
            'defaults' => [],
        ],
    ],
];
