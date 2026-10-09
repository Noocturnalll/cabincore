<?php

/**
 * Registry modules: simple record lists (Data PAS, Training, Asset, Compliance, Assessment, Form, Org Chart).
 * One generic page (App\Livewire\Modules\Registry\Index) renders every module from this file, so adding a module
 * is a config entry plus `php artisan db:seed --class=RegistryPermissionSeeder`.
 *
 *  group    sidebar group
 *  title    field key copied to the searchable title column
 *  due      optional date field key watched for expiry; yellow/red are windows in months before the date
 *  columns  field keys shown in the table (the due field is always shown last, with its colour marker)
 *  fields   key => [label, type: text|textarea|date|number|select, required, options]
 *
 * Permissions per module: registry.<key>.view and registry.<key>.manage; registry.view_all sees every division.
 */
return [
    'groups' => [
        'dev-ga' => 'Development & GA',
        'organisasi' => 'Organisasi & Assessment',
    ],

    'modules' => [
        'pas' => [
            'group' => 'dev-ga', 'label' => 'Data PAS Bandara', 'subtitle' => 'Pas bandara (airport pass) karyawan beserta masa berlakunya.',
            'title' => 'holder', 'due' => 'valid_until', 'yellow' => 2, 'red' => 1,
            'columns' => ['holder', 'nik', 'airport', 'codes', 'pas_no'],
            'fields' => [
                'holder' => ['Nama pemegang', 'text', true],
                'nik' => ['ID karyawan', 'text', false],
                'airport' => ['Bandara / STA', 'text', true],
                'codes' => ['Kode PAS (area)', 'text', false],
                'pas_no' => ['No. PAS', 'text', false],
                'pas_type' => ['Jenis PAS', 'select', false, ['Tetap', 'Sementara']],
                'valid_until' => ['Berlaku s/d', 'date', true],
                'notes' => ['Catatan', 'textarea', false],
            ],
        ],
        'training' => [
            'group' => 'dev-ga', 'label' => 'Data Training', 'subtitle' => 'Riwayat training dan sertifikat; penanda muncul bila sertifikat mendekati habis.',
            'title' => 'employee', 'due' => 'valid_until', 'yellow' => 2, 'red' => 1,
            'columns' => ['employee', 'training', 'provider', 'cert_no', 'training_date'],
            'fields' => [
                'employee' => ['Nama karyawan', 'text', true],
                'nik' => ['ID', 'text', false],
                'training' => ['Nama training', 'text', true],
                'provider' => ['Penyelenggara', 'text', false],
                'cert_no' => ['No. sertifikat', 'text', false],
                'training_date' => ['Tanggal training', 'date', true],
                'valid_until' => ['Sertifikat berlaku s/d', 'date', false],
                'notes' => ['Catatan', 'textarea', false],
            ],
        ],

        'assess_internal' => [
            'group' => 'organisasi', 'label' => 'Assessment Internal', 'subtitle' => 'Hasil assessment internal karyawan.',
            'title' => 'employee',
            'columns' => ['employee', 'period', 'score', 'grade', 'assessor'],
            'fields' => [
                'employee' => ['Nama karyawan', 'text', true],
                'period' => ['Periode', 'text', true],
                'score' => ['Skor', 'number', true],
                'grade' => ['Grade', 'select', false, ['A', 'B', 'C', 'D']],
                'assessor' => ['Assessor', 'text', false],
                'notes' => ['Catatan', 'textarea', false],
            ],
        ],
        'assess_hc' => [
            'group' => 'organisasi', 'label' => 'Assessment HC', 'subtitle' => 'Assessment Human Capital.',
            'title' => 'employee',
            'columns' => ['employee', 'period', 'score', 'grade', 'assessor'],
            'fields' => [
                'employee' => ['Nama karyawan', 'text', true],
                'period' => ['Periode', 'text', true],
                'score' => ['Skor', 'number', true],
                'grade' => ['Grade', 'select', false, ['A', 'B', 'C', 'D']],
                'assessor' => ['Assessor', 'text', false],
                'notes' => ['Catatan', 'textarea', false],
            ],
        ],
        'form' => [
            'group' => 'organisasi', 'label' => 'Form', 'subtitle' => 'Daftar form kerja beserta tautannya.',
            'title' => 'form_name',
            'columns' => ['form_name', 'form_no', 'revision', 'link'],
            'fields' => [
                'form_name' => ['Nama form', 'text', true],
                'form_no' => ['No. form', 'text', false],
                'revision' => ['Revisi', 'text', false],
                'link' => ['Tautan / lokasi file', 'text', false],
                'notes' => ['Keterangan', 'textarea', false],
            ],
        ],
        'orgchart' => [
            'group' => 'organisasi', 'label' => 'Organization Chart', 'subtitle' => 'Struktur organisasi: siapa melapor ke siapa.',
            'title' => 'person',
            'columns' => ['level', 'person', 'position', 'reports_to'],
            'fields' => [
                'level' => ['Level (1 = tertinggi)', 'number', true],
                'person' => ['Nama', 'text', true],
                'position' => ['Jabatan', 'text', true],
                'reports_to' => ['Melapor ke', 'text', false],
            ],
        ],
    ],
];
