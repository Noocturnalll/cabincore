# ERD — Cabin Core

Relasi data utama. Tabel yang lahir dari relasi **many-to-many** ditandai ⇄ dan semuanya ditampilkan di aplikasi
(kolom "Tampil di" menunjuk halamannya), karena hasilnya dipakai untuk review.

```mermaid
erDiagram
    DIVISIONS ||--o{ USERS : "punya"
    DIVISIONS ||--o{ EMPLOYEES : "punya"
    DIVISIONS ||--o{ ASSETS : "memiliki"
    POSITIONS ||--o{ EMPLOYEES : "jabatan"
    USERS }o--o{ ROLES : "model_has_roles (RBAC)"
    ROLES }o--o{ PERMISSIONS : "role_has_permissions (RBAC)"

    EMPLOYEES ||--o{ ROSTER_ENTRIES : "dijadwalkan"
    EMPLOYEES ||--o{ ATTENDANCE_RECORDS : "presensi"
    SHIFT_CODES ||--o{ ROSTER_ENTRIES : "kode shift"

    EMPLOYEES ||--o{ JOB_CREW : "mengerjakan"
    AIRCRAFT_CLEANINGS ||--o{ JOB_CREW : "kru"
    WO_LOGS ||--o{ JOB_CREW : "kru"
    DMI_LOGS ||--o{ JOB_CREW : "kru"
    NSRDI_LOGS ||--o{ JOB_CREW : "kru"
    CML_LOGS ||--o{ JOB_CREW : "kru"

    ASSETS ||--o{ ASSET_ASSIGNMENTS : "dipinjamkan"
    EMPLOYEES ||--o{ ASSET_ASSIGNMENTS : "memegang"

    AOCS ||--o{ AIRCRAFTS : "operator"
    DAILY_JOB_ASSIGNMENTS ||--o{ WO_LOGS : "dja_id"
    DAILY_JOB_ASSIGNMENTS ||--o{ DMI_LOGS : "dja_id"
    DAILY_JOB_ASSIGNMENTS ||--o{ NSRDI_LOGS : "dja_id"
    DAILY_JOB_ASSIGNMENTS ||--o{ DJA_SYNC_REVIEWS : "antrean review"

    LEADER_REPORT_IMPORTS ||--o{ LEADER_REPORT_ROWS : "baris"
    LEADER_REPORT_IMPORTS ||--o{ WO_LOGS : "leader_import_id"

    MASTER_ENTRIES }o--|| MASTER_ENTRIES : "daftar acuan (tim, shift, aturan, kategori, target)"
```

## Tabel pivot (many-to-many)

| Tabel | Menghubungkan | Isi tambahan | Tampil di |
|---|---|---|---|
| `job_crew` ⇄ | pekerjaan (cleaning, WO, DMI, NSRDI, CML) ↔ karyawan | man hours bagian tiap orang | Presensi & Disiplin → profil, kolom Man hours |
| `asset_assignments` ⇄ | asset ↔ karyawan / station | jumlah, tanggal pinjam, jatuh tempo, tanggal kembali | Data Asset → Riwayat; profil karyawan |
| `roster_entries` ⇄ | karyawan ↔ tanggal ↔ shift | kode shift, tim, station | Manpower Harian, Dashboard KPI, Presensi |
| `model_has_roles`, `role_has_permissions` ⇄ | pengguna ↔ role ↔ izin | — | Manajemen Pengguna (RBAC) |

## Data master (satu tabel, banyak jenis)

`master_entries` menyimpan daftar acuan yang menjadi dasar pengaturan. Jenisnya didefinisikan di `config/master.php`
dan tiap jenis punya izin sendiri `master.<jenis>.view` / `master.<jenis>.manage`:

| Jenis | Dipakai oleh |
|---|---|
| `team` | roster, dashboard (tim mana yang dihitung kapasitas, divisi pemilik) |
| `shift` | kapasitas man hours (jam efektif per shift) |
| `attendance_status`, `attendance_rule` | penilaian presensi dan disiplin |
| `asset_category`, `asset_condition` | Data Asset (kondisi mana yang bisa dipinjamkan) |
| `kpi_target` | target closed per hari per station dan AOC (disinkron dari sheet resume) |
| `integration` | saklar sinkronisasi (terapkan otomatis closing CBM atau tunggu tinjauan) |

## Alur data

```
Planner ──(DJA, AC Movement)──► sinkron baca-saja ──► WO / DMI / NSRDI logs
Sheet hasil kerja ──► Sumber Data ──► Laporan Leader (cocokkan DJA / unplanned / CML) ──► logs + job_crew
Excel (roster, presensi, LGT, accuracy, AIEC) ──► perintah artisan ──► roster_entries, attendance_records, ...
Semua ──► Dashboard KPI (pivot per station dan tim, dibatasi menurut role)
```

## Divisi, role PIC & menu (RBAC)

5 divisi → 5 role PIC. Akses menu diatur permission `menu.*` (seeder: `RegistryPermissionSeeder::MENUS`), dan dipaksa juga di level route (middleware), bukan hanya disembunyikan di sidebar.

| Menu | Manager | Admin CGK | PIC CBM | PIC Painting | PIC AIEC | PIC Supporting | PIC Finishing |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Cabin Maintenance (DJA, WO, DMI, CML, LGT) | ✓ | ✓ | ✓ | – | – | – | – |
| Painting (NSRDI Logs, Daily Report) | – | – | – | ✓ | – | – | – |
| Aircraft Cleaning | ✓ | ✓ | – | – | ✓ | – | – |
| NSRDI Management | ✓ | ✓ | ✓ | ✓ | – | – | – |
| ICT | ✓ | ✓ | ✓ | – | – | – | – |
| Capacity / Rotation / AC Movement | ✓ | ✓ | ✓ | – | ✓ | – | – |
| Inventory (IMS) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ (kelola toko) | ✓ |
| Analitik (Summary, KPI) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

Super Admin: semua (Gate::before). Executive, Audit: Super Admin + Manager. Master data inti (airports, aircraft, categories): Super Admin.
Tes: `tests/Feature/RoleMatrixTest.php` (route × role), `tests/Feature/MasterCrudTest.php` (CRUD master, cleaning, ICT).

**COD (Cabin On Duty)** — role tersendiri (bisa dirangkap dengan PIC, mis. PIC Supporting + COD). Penerima awal permintaan planner / work group / complaint lalu meneruskan ke eksekutor lapangan. Akses: menu Cabin Maintenance (DJA, WO, DMI, CML, LGT draft awal), NSRDI, ICT, AC Movement/Capacity, Inventory (lihat stok, minta barang, terima barang rusak dari lapangan sebelum ke Repair Area), Analitik.

## Alur COD, repair IMS, dan LGT

**Repair IMS** (`ims_repair_waiting` → `ims_repair_in_progress` → `ims_repair_completed`):
COD / Admin COD mengisi *Form Barang Masuk Repair* (`ims.repair.request`) → tim repair (PIC Supporting, `ims.repair.manage`) **ACC** (`accepted_at`, `accepted_by`) → **Rak Repair 1** antrian → **Rak Repair 2** proses → **Rak Repair 3** selesai → dikembalikan ke **rak gudang** (`ims_locations`) bila serviceable. Rak repair berbeda dari rak gudang. Setiap perpindahan dicatat di `ims_repair_logs` dan mengirim notifikasi (`ImsNotifier`).

**Permintaan barang untuk pesawat:** katalog → permintaan (`ims_transactions`, `aircraft_registration`, stok di-*reserve*) → notifikasi ke approver (`ims.approval.act`) → disetujui: stok berkurang, pemohon diberi notifikasi; ditolak: reservasi dilepas, alasan dikirim ke pemohon.

**LGT:** Admin COD / COD membuat **draft** (`lgt.plan`: pesawat, station, STA, STD; durasi ground time dihitung) → PIC Finishing diberi notifikasi → Finishing / CBM / AIEC (`lgt.manage`) mengisi pekerjaan dan status. Baris tanpa pekerjaan = draft. Kolom `drafted_by`, `filled_by`, `filled_at`.

**Dashboard per role** (`App\Support\DashboardScope`, `ModuleTiles`, `AttentionList`): semua role kerja punya periode harian / mingguan / bulanan dan blok KPI. PIC dan COD melihat semua station tetapi hanya tim divisinya; tile dan modul mengikuti menu role. Panel **Perlu perhatian** menghitung pekerjaan yang harus dikejar sesuai hak akses, dan tile produksi menampilkan selisih terhadap periode sebelumnya.

**Pivot operasional** (`App\Services\Kpi\OperationalPivot`, tab Ringkasan KPI): open / closed / rate per station untuk WO, DMI, NSRDI (sesuai DJA), Unplanned, CML, dan Aircraft Cleaning per tipe (Transit, General, DCI, DCE, DBI, GCI, GCE), plus rincian per hari untuk periode minggu / bulan. Periode hari / minggu / bulan mengikuti pemilih di bar atas; hanya blok yang menjadi hak menu role yang dihitung.
