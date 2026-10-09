<?php

/*
 | Konfigurasi import rotasi pesawat.
 | PENTING: jam di file Excel adalah JAM LOKAL station. Daftar zona waktu di bawah
 | dipakai untuk menghitung durasi & posisi bar yang benar (mis. MDC->TTE beda 1 jam).
 | Station yang belum terdaftar dianggap WIB dan akan muncul di peringatan import.
 | Mohon verifikasi daftar ini sesuai operasional Anda.
 */
return [
    // Zona waktu sumbu timeline (7 = WIB)
    'display_offset' => 7,
    'display_label' => 'WIB',
    'default_offset' => 7,

    // Kolom nomor flight yang dipindai (E..AM). Kolom jumlah flight (AN) = 40.
    'first_col' => 5,
    'last_col' => 39,
    'count_col' => 40,

    // offset UTC => daftar kode IATA
    'timezones' => [
        7 => ['CGK', 'HLP', 'BDO', 'JOG', 'SOC', 'YIA', 'SRG', 'SUB', 'MLG', 'BWX', 'DJB', 'PLM', 'PGK', 'BKS', 'TKG', 'TJQ',
            'PKU', 'PDG', 'KNO', 'BTH', 'BTJ', 'LSW', 'FLZ', 'GNS', 'KTG', 'NTX', 'PNK', 'SMQ', 'MES'],
        8 => ['DPS', 'LOP', 'UPG', 'MDC', 'BPN', 'BDJ', 'AAP', 'BEJ', 'KOE', 'LBJ', 'MOF', 'ENE', 'RTG', 'RTI', 'SWQ', 'BMU',
            'WGP', 'TMC', 'LKA', 'LWE', 'PLW', 'GTO', 'LUW', 'MJU', 'KXB', 'LLO', 'MOH', 'BUW', 'KBU', 'BTW', 'BJW', 'ARD',
            'ABU', 'KUL', 'PEN', 'SIN'],
        9 => ['AMQ', 'BXB', 'DJJ', 'DOB', 'EWE', 'FKQ', 'KNG', 'LAH', 'LUV', 'NAM', 'NBX', 'OTI', 'SOQ', 'SXK', 'TIM', 'TTE',
            'WMX', 'MKW'],
    ],
];
