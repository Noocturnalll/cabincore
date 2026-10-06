# IMS TRACKER BARANG — Spesifikasi & Instruksi Implementasi (Laravel)

> Dokumen ini ditujukan untuk **Gemini Antigravity** sebagai satu-satunya sumber kebenaran (source of truth).
> Bahasa UI: **Bahasa Indonesia**. Zona waktu: **Asia/Jakarta**.
> Domain: manajemen inventori **part pesawat** (aviation spare parts) dengan alur permintaan, persetujuan, repair, dan pelacakan stok.

---

## 0. ATURAN EMAS (WAJIB DIPATUHI)

1. **Ini BUKAN project baru.** Modul ini ditambahkan sebagai **menu baru** di dalam project Laravel yang SUDAH ADA. Jangan membuat ulang project, jangan scaffold ulang auth, layout, atau dashboard.
2. **Audit dulu, baru coding** (lihat Bagian 2). Jangan menebak versi Laravel, stack frontend, atau package. Baca project-nya.
3. **Ikuti konvensi project yang sudah ada**: struktur folder, gaya penulisan controller, layout Blade/Livewire/Inertia, komponen UI, sistem role/permission, penamaan, bahasa.
4. **Isolasi modul**: semua tabel berawalan **`ims_`**, semua model di `App\Models\Ims`, route prefix `/ims`, nama route berawalan `ims.`, permission berawalan `ims.`.
5. **Jangan mengubah tabel/kode existing** (users, roles, dll.) kecuali benar-benar perlu dan minimal (misalnya menambah menu di sidebar, mendaftarkan permission). Setiap perubahan pada file existing harus dilaporkan.
6. **Dilarang**: `migrate:fresh`, `migrate:refresh`, `db:wipe`, drop tabel existing, menghapus data. Migration hanya boleh **membuat** tabel `ims_*` baru.
7. **Jangan install package baru tanpa izin.** Jika dibutuhkan (Excel export, PDF, permission), cek dulu apakah sudah ada di `composer.json`; kalau belum, **tanyakan** ke pemilik project dan tawarkan alternatif tanpa package.
8. **Kerjakan per fase** (Bagian 12). Setelah tiap fase: jalankan test, lalu **berhenti dan laporkan** apa yang dibuat, file yang diubah, dan cara mencobanya. Jangan lompat ke fase berikutnya sebelum diminta.
9. **Semua perubahan stok hanya boleh lewat `StockService`** (Bagian 5). Dilarang mengubah `qty_on_hand` / `qty_reserved` langsung dari controller, model event, atau query manual.
10. Jika ada hal ambigu, gunakan **asumsi default** di Bagian 14, tulis asumsi itu di laporan, dan lanjutkan. Hanya berhenti bertanya untuk hal yang bisa merusak data atau butuh package baru.

---

## 1. KONTEKS & TUJUAN

Perusahaan mengelola banyak part pesawat yang tersebar di lokasi berbeda (gudang, rak, bin). Kebutuhan:

- **Katalog barang** yang rapi: tiap barang punya **Part Number (PN)**, deskripsi, kategori, satuan, lokasi, dan status ketersediaan.
- **Barang keluar** (diambil/dipinjam) harus melalui **picklist → pengajuan → approval**, dengan **deskripsi tujuan penggunaan wajib**.
- **Barang masuk** juga harus punya deskripsi/keterangan dan **approval**.
- **Stok berubah hanya setelah approve**, sebesar **jumlah (qty) yang diajukan**, bukan selalu 1. Contoh: keluar 5 → stok −5. Masuk 5 → stok +5.
- **Repair barang** punya 3 "rak" (3 tabel, 3 proses): **Menunggu Repair → Proses Repair → Selesai Repair**.
- **Tracker**: setiap pergerakan tercatat (siapa, kapan, berapa, untuk apa, disetujui siapa), sehingga bisa diaudit.
- **Data master** lengkap (kategori, satuan, lokasi, supplier/vendor, tipe pesawat, departemen, dll.).

---

## 2. LANGKAH 0 — AUDIT PROJECT (LAKUKAN SEBELUM MENULIS KODE)

Periksa dan **laporkan ringkasannya** dalam bentuk daftar pendek:

| Yang dicek | Cara cek | Dampak |
|---|---|---|
| Versi Laravel & PHP | `composer.json`, `php artisan --version` | Struktur route (`bootstrap/app.php` vs `RouteServiceProvider`), sintaks |
| Stack frontend | cek `resources/views`, `package.json` (Blade, Livewire, Inertia+Vue/React, Filament, dll.) | Cara membuat halaman |
| Auth & starter kit | Breeze/Jetstream/Fortify/custom | Middleware `auth`, guard |
| Sistem role/permission | cek `spatie/laravel-permission`, Gate/Policy custom, kolom `role` di users | Cara mendaftarkan permission IMS |
| Tabel/entitas existing | model `User`, `Department`/`Divisi`, `Branch`, dll. | **Reuse**, jangan duplikasi (mis. jika sudah ada tabel departemen, pakai itu) |
| Layout & komponen UI | layout utama, komponen tabel/modal/form/alert | Konsistensi tampilan |
| Lokasi menu sidebar | file layout/sidebar | Tempat menambah menu IMS |
| Database & koneksi | `.env`, driver (MySQL/PostgreSQL/SQLite) | Tipe kolom, locking |
| Queue, mail, storage disk | `config/queue.php`, `config/filesystems.php` | Notifikasi & upload file |
| Test setup | PHPUnit/Pest, factories | Penulisan test |
| Package export | `maatwebsite/excel`, `dompdf`, dll. | Fitur export laporan |

**Output Langkah 0**: ringkasan temuan + keputusan integrasi (mis. "Pakai Spatie permission, layout `layouts.app`, Livewire 3, tabel `departments` dipakai ulang"). Baru lanjut.

---

## 3. ARSITEKTUR & STRUKTUR FILE

Sesuaikan dengan konvensi project. Struktur target:

```
app/
  Enums/Ims/            -> TransactionType, TransactionStatus, ItemCondition, TrackingType,
                           RepairStage, RepairResult, RepairPriority, ApprovalStatus,
                           UsageType, MovementType, LocationType
  Models/Ims/           -> semua model ims_*
  Services/Ims/         -> StockService, ApprovalService, RepairService,
                           DocumentNumberService, ImsNotificationService
  Http/Controllers/Ims/ -> satu controller per modul (atau resource controller)
  Http/Requests/Ims/    -> FormRequest untuk setiap aksi tulis
  Policies/Ims/         -> ItemPolicy, TransactionPolicy, RepairPolicy, dst.
  Notifications/Ims/    -> ApprovalRequested, ApprovalDecided, LowStock, LoanOverdue
config/ims.php          -> setting modul (approval, prefix kode, ambang batas, dll.)
database/migrations/    -> semua migration ims_* (timestamp berurutan, 1 migration per tabel)
database/seeders/Ims/   -> ImsPermissionSeeder, ImsMasterSeeder, ImsDemoSeeder
database/factories/Ims/ -> factory tiap model
routes/ims.php          -> semua route IMS (di-include dari file route utama sesuai versi Laravel)
resources/views/ims/    -> (atau komponen Livewire/Inertia sesuai stack)
tests/Feature/Ims/      -> test per modul + test StockService
```

**Prinsip**: controller tipis → logika bisnis di Service → validasi di FormRequest → otorisasi di Policy/Gate.

**Menu sidebar** (grup baru "IMS Tracker Barang", tampil sesuai permission):

1. Katalog Barang
2. Peminjaman Barang
3. Persetujuan (badge jumlah pending)
4. Perbaikan Barang
5. Pencatatan Stok
6. Riwayat & Laporan
7. Data Master
8. Pengguna & Hak Akses (hanya admin; bisa memakai menu user existing jika sudah ada, lihat Modul 7)

---

## 4. MODEL DATA (SEMUA TABEL BERAWALAN `ims_`)

Aturan umum semua tabel: `id` (bigint), `created_at`, `updated_at`; tabel master & dokumen memakai **SoftDeletes**; foreign key dengan index; kolom status memakai Enum PHP (disimpan sebagai string); `created_by`/`updated_by` (FK ke users, nullable) untuk tabel penting.

### 4.1 Data Master

**`ims_categories`** — kategori barang (hierarkis)
`parent_id` (nullable, self FK), `code` (unique), `name`, `ata_chapter` (nullable, mis. "32" untuk Landing Gear), `description`, `is_active`.

**`ims_units`** — satuan
`code` (unique, mis. PCS, EA, SET, LTR, KG), `name`, `is_active`.

**`ims_locations`** — lokasi penyimpanan (hierarkis: Gudang → Rak → Shelf → Bin)
`parent_id` (nullable), `code` (unique, mis. `WH-A-R01-S02`), `name`, `type` (warehouse|rack|shelf|bin|repair_area|quarantine), `description`, `is_active`.

**`ims_suppliers`** — pemasok & vendor repair
`code` (unique), `name`, `type` (supplier|repair_vendor|both), `contact_person`, `phone`, `email`, `address`, `is_active`.

**`ims_aircraft_types`** — tipe pesawat
`code` (unique), `manufacturer`, `model`, `description`, `is_active`.

**`ims_departments`** — **hanya buat jika project belum punya tabel departemen.** Jika sudah ada, pakai ulang dan jangan buat tabel ini.
`code`, `name`, `is_active`.

**Enum bukan tabel** (cukup PHP Enum, label Indonesia): `ItemCondition` (new=Baru, serviceable=Layak Pakai, repaired=Hasil Repair, overhauled=Overhaul, unserviceable=Tidak Layak, scrap=Scrap), `TrackingType` (quantity=Per Jumlah, serial=Per Serial Number, batch=Per Batch), `UsageType` (consume=Habis Pakai, loan=Dipinjam/Harus Kembali), `RepairPriority` (normal, urgent, aog=Aircraft On Ground).

### 4.2 Barang

**`ims_items`** — master barang
| Kolom | Keterangan |
|---|---|
| `part_number` | **unique**, disimpan UPPERCASE + trim, index |
| `name` | nama barang |
| `description` | text — **wajib diisi** (spesifikasi/deskripsi barang) |
| `category_id`, `unit_id` | FK |
| `manufacturer` | nullable |
| `ata_chapter` | nullable |
| `tracking_type` | quantity \| serial \| batch (default quantity) |
| `is_rotable` | boolean (part yang bisa di-repair dan dipakai ulang) |
| `min_stock` | integer, default 0 (ambang "Menipis") |
| `max_stock` | integer nullable |
| `default_location_id` | nullable FK |
| `shelf_life_days` | nullable (untuk barang berumur simpan) |
| `is_hazmat` | boolean |
| `image_path` | nullable |
| `notes` | nullable |
| `is_active` | boolean |

**`ims_item_alternates`** — part number alternatif/pengganti
`item_id`, `part_number`, `relation` (alternate|interchangeable|superseded_by|supersedes), `notes`. Unique (`item_id`,`part_number`).

**`ims_item_aircraft_type`** (pivot) — kompatibilitas
`item_id`, `aircraft_type_id`.

**`ims_item_serials`** — hanya dipakai untuk item `tracking_type` = serial/batch
`item_id`, `serial_number`, `batch_no` (nullable), `condition`, `status` (in_stock|reserved|issued|in_repair|scrapped), `location_id` (nullable), `tsn_hours` (nullable), `csn_cycles` (nullable), `tso_hours` (nullable), `cso_cycles` (nullable), `expiry_date` (nullable), `certificate_no` (nullable, mis. nomor release certificate), `received_at`. Unique (`item_id`,`serial_number`).

### 4.3 Stok

**`ims_stocks`** — saldo stok per barang per lokasi (hanya barang **layak pakai / serviceable**)
`item_id`, `location_id`, `qty_on_hand` (unsigned int), `qty_reserved` (unsigned int, default 0). **Unique (`item_id`,`location_id`)**.
`qty_available` = `qty_on_hand − qty_reserved` (accessor, bukan kolom).
Tambahkan **CHECK constraint** (jika DB mendukung): `qty_on_hand >= 0`, `qty_reserved >= 0`, `qty_reserved <= qty_on_hand`.

**`ims_stock_movements`** — **ledger/kartu stok, IMMUTABLE** (tidak boleh update/delete; tidak pakai SoftDeletes)
`item_id`, `location_id`, `serial_id` (nullable), `movement_type` (in|out|adjust_plus|adjust_minus|transfer_in|transfer_out|repair_out|repair_in|loan_return|scrap), `qty_change` (signed int), `balance_before`, `balance_after`, `transaction_id` (nullable FK), `repair_code` (nullable), `notes`, `created_by`, `created_at`. Index (`item_id`,`created_at`), (`location_id`,`created_at`).

### 4.4 Transaksi (Picklist, Masuk, Keluar, Penyesuaian, Transfer)

**`ims_transactions`** — header dokumen
| Kolom | Keterangan |
|---|---|
| `code` | unique, mis. `OUT-202610-0001` |
| `type` | out \| in \| adjustment \| transfer \| repair_in |
| `status` | draft \| pending_approval \| approved \| rejected \| cancelled \| completed |
| `usage_type` | consume \| loan (khusus type out) |
| `expected_return_date` | nullable, wajib jika usage_type = loan |
| `purpose_description` | text, **WAJIB** (min 10 karakter): untuk apa barang digunakan / asal & alasan barang masuk |
| `reference_no` | nullable (No. Work Order / No. PO / No. Dokumen) |
| `aircraft_registration` | nullable (mis. PK-XXX) |
| `department_id` | nullable |
| `supplier_id` | nullable (untuk type in) |
| `source` | nullable (purchase \| return_loan \| repair_result \| from_aircraft \| other) |
| `requested_by`, `requested_at` | pemohon |
| `submitted_at` | |
| `approved_by`, `approved_at`, `approval_note` | |
| `rejected_by`, `rejected_at`, `rejected_reason` | |
| `picked_up_by_name`, `picked_up_at`, `handover_note`, `handed_over_by` | catatan pengambilan fisik (type out) |
| `parent_transaction_id` | nullable (mis. pengembalian merujuk ke peminjaman asal) |

**`ims_transaction_items`** — baris barang
`transaction_id`, `item_id`, `serial_id` (nullable), `location_id` (lokasi asal/tujuan stok), `to_location_id` (nullable, khusus transfer), `qty` (unsigned int ≥ 1), `condition` (nullable), `batch_no`, `system_qty` & `actual_qty` (khusus adjustment), `note`, `returned_qty` (default 0, untuk loan).

### 4.5 Approval

**`ims_approvals`**
`approvable_type`, `approvable_id` (morph: transaksi), `level` (int, mulai 1), `required_role` (nullable, nama role/permission), `approver_id` (nullable, diisi saat bertindak), `status` (pending|approved|rejected|skipped), `note`, `acted_at`.
Default **1 level**. Struktur sudah siap multi-level (config `ims.approval.levels`).

### 4.6 Repair — 3 Rak (3 Tabel)

Ketiganya punya **kolom umum**: `repair_code` (mis. `RPR-202610-0001`, **identifier tetap yang sama di ketiga rak**), `item_id`, `serial_id` (nullable), `qty` (default 1), `fault_description` (text, wajib: kerusakan/keluhan), `priority`, `origin_transaction_id` (FK transaksi type repair_in), `location_id` (lokasi fisik rak repair), `created_by`, SoftDeletes.

**`ims_repair_waiting`** — RAK 1: Menunggu Repair
kolom umum + `received_at`, `received_by`, `source` (from_stock|from_aircraft|external), `aircraft_registration`, `vendor_id` (nullable, jika repair di vendor luar), `notes`.

**`ims_repair_in_progress`** — RAK 2: Proses Repair
kolom umum + `started_at`, `technician_id` (nullable FK users), `vendor_id` (nullable), `work_order_no`, `estimated_completion_date`, `progress_notes`.

**`ims_repair_completed`** — RAK 3: Selesai Repair
kolom umum + `completed_at`, `result` (repaired|beyond_repair|returned_unrepaired), `findings` (temuan), `action_taken` (tindakan perbaikan), `certificate_no`, `repaired_by_name`, `returned_to_stock_at` (nullable), `return_transaction_id` (nullable FK).

**`ims_repair_logs`** — jejak perpindahan rak
`repair_code`, `from_stage` (nullable), `to_stage` (waiting|in_progress|completed|returned_to_stock|scrapped), `actor_id`, `note`, `created_at`. Immutable.

**Cara kerja perpindahan rak**: dilakukan oleh `RepairService` dalam **satu DB transaction**: (1) buat record di rak tujuan membawa data dari rak asal, (2) soft-delete record di rak asal, (3) tulis `ims_repair_logs`. Satu `repair_code` hanya boleh aktif (tidak ter-soft-delete) di **satu rak** pada satu waktu — validasi di service.

### 4.7 Pendukung

**`ims_attachments`** (morph) — `attachable_type/id`, `path`, `original_name`, `mime`, `size`, `uploaded_by`. Disimpan di disk **private**; validasi tipe (jpg, png, pdf) & ukuran (maks 5 MB).
**`ims_audit_logs`** — `user_id`, `action`, `auditable_type/id`, `old_values` (json), `new_values` (json), `ip_address`, `created_at`. (Jika project sudah memakai `spatie/laravel-activitylog`, pakai itu.)
**`ims_document_sequences`** — `prefix`, `period` (YYYYMM), `last_number`; dipakai `DocumentNumberService` dengan row locking.

---

## 5. ATURAN BISNIS INTI (INVARIAN — TIDAK BOLEH DILANGGAR)

### 5.1 StockService (satu-satunya pintu perubahan stok)

Semua method berjalan dalam `DB::transaction` + `lockForUpdate()` pada baris `ims_stocks` (buat baris via `firstOrCreate` dulu, lalu lock). Untuk transaksi multi-baris, **urutkan lock berdasarkan (item_id, location_id)** untuk mencegah deadlock.

| Method | Efek pada `ims_stocks` | Ledger |
|---|---|---|
| `reserve(item, location, qty)` | `qty_reserved += qty` (validasi `available >= qty`) | — |
| `releaseReservation(item, location, qty)` | `qty_reserved -= qty` | — |
| `commitOut(item, location, qty)` | `qty_on_hand -= qty` **dan** `qty_reserved -= qty` | `out`, qty_change negatif |
| `receive(item, location, qty)` | `qty_on_hand += qty` | `in` / `repair_in` / `loan_return` |
| `adjust(item, location, newQty, reason)` | `qty_on_hand = newQty` (validasi `newQty >= qty_reserved`) | `adjust_plus` / `adjust_minus` |
| `transfer(item, from, to, qty)` | from −qty, to +qty (atomik) | `transfer_out` + `transfer_in` |

Setiap method **wajib** menulis baris `ims_stock_movements` dengan `balance_before` & `balance_after`.

### 5.2 Alur Barang KELUAR (Picklist → Approval → Stok berkurang)

```
Katalog -> [Tambah ke Picklist] -> Picklist (transaksi OUT, status draft)
   -> [Ajukan] isi deskripsi tujuan (WAJIB) + usage_type + referensi
   -> status pending_approval  + KUNCI BARANG: qty_reserved += qty tiap baris
   -> Approver:
        SETUJU  -> qty_on_hand -= qty, qty_reserved -= qty (stok berkurang SEKARANG), status approved
                -> Admin Gudang serahkan fisik -> isi Catatan Pengambilan -> status completed
        TOLAK   -> qty_reserved -= qty (kunci dilepas), status rejected (alasan wajib)
   -> Pemohon boleh BATAL selama draft/pending_approval -> kunci dilepas, status cancelled
```

Poin penting:
- **Stok on_hand baru berkurang saat APPROVE**, sebesar qty tiap baris (5 → −5). Ini sesuai kebutuhan bisnis.
- **Kunci (reserve) saat pengajuan** adalah tambahan agar barang yang sama tidak diajukan berlebih oleh banyak orang. Katalog menampilkan `available` (= on_hand − reserved).
- Saat approve, sistem **validasi ulang** ketersediaan (data bisa berubah sejak pengajuan). Jika gagal, approve ditolak dengan pesan jelas.
- Item `tracking_type = serial`: pemohon wajib memilih serial number; qty = jumlah serial; serial berubah status `in_stock → reserved → issued`.
- `usage_type = loan`: barang dianggap **dipinjam**, wajib `expected_return_date`; tercatat sebagai pinjaman outstanding sampai dikembalikan (lihat 5.4).

### 5.3 Alur Barang MASUK (Deskripsi + Approval → Stok bertambah)

```
Admin Gudang buat transaksi IN (draft) -> isi: sumber (pembelian/pengembalian/hasil repair/lainnya),
   supplier, referensi (PO/DO), deskripsi & keterangan (WAJIB), baris barang: item, lokasi tujuan, qty, kondisi, serial/batch, sertifikat
   -> [Ajukan] pending_approval
   -> Approver SETUJU -> qty_on_hand += qty (stok bertambah SEKARANG, sesuai qty), status approved
   -> TOLAK -> status rejected, stok tidak berubah
```

### 5.4 Pengembalian Pinjaman
Transaksi IN dengan `source = return_loan` dan `parent_transaction_id` = transaksi OUT asal. Sistem memvalidasi `returned_qty + qty <= qty` pinjaman asal. Setelah disetujui, stok bertambah dan `returned_qty` diperbarui. Pinjaman yang lewat `expected_return_date` ditandai **Terlambat** (notifikasi + laporan).

### 5.5 Penyesuaian Stok (Stock Opname)
Transaksi `adjustment`: per baris isi `system_qty` (otomatis dari sistem), `actual_qty` (hasil hitung fisik), **alasan wajib**. Perlu approval. Saat disetujui, `StockService::adjust` dijalankan dan selisih dicatat di ledger.

### 5.6 Transfer Antar Lokasi
Transaksi `transfer`: lokasi asal, lokasi tujuan, qty. Perlu approval (bisa dikonfigurasi tanpa approval untuk perpindahan antar bin dalam gudang yang sama). Total stok tidak berubah, hanya lokasi.

### 5.7 Alur Perbaikan (Repair) — 3 Rak

```
[Ajukan Perbaikan] = transaksi type repair_in (draft -> pending_approval), isi: barang, serial,
     qty, kerusakan/keluhan (WAJIB), sumber (dari stok / dari pesawat / vendor luar), prioritas, lampiran foto
   -> Approver SETUJU:
        - jika sumber = from_stock : stok layak pakai berkurang (ledger repair_out)
        - jika from_aircraft/external : TIDAK ada efek stok (barang memang belum pernah masuk stok)
        - RepairService membuat record di RAK 1 (Menunggu Repair)
   -> RAK 1 -> [Mulai Repair] (isi teknisi/vendor, WO, estimasi selesai) -> RAK 2 (Proses Repair)
   -> RAK 2 -> [Selesaikan Repair] (hasil, temuan, tindakan, no. sertifikat) -> RAK 3 (Selesai Repair)
   -> RAK 3 -> hasil:
        repaired            -> [Kembalikan ke Stok] membuat transaksi IN (source=repair_result, kondisi=repaired),
                               perlu approval (default), setelah approve stok +qty di lokasi pilihan
        beyond_repair       -> dicatat scrap (ledger scrap bila sebelumnya tercatat stok), tidak masuk stok
        returned_unrepaired -> dikembalikan apa adanya; tidak masuk stok layak pakai
```

Aturan: tiap perpindahan rak **wajib** menulis `ims_repair_logs`; mundur rak (mis. dari RAK 2 ke RAK 1) hanya untuk role manager dengan alasan wajib.

### 5.8 Matriks Transisi Status Transaksi

| Dari | Ke (diizinkan) |
|---|---|
| draft | pending_approval, cancelled |
| pending_approval | approved, rejected, cancelled |
| approved | completed (hanya type out, setelah serah terima) |
| rejected / cancelled / completed | **final, tidak boleh diubah** |

Transisi di luar tabel ini harus **ditolak** di Service (bukan hanya disembunyikan di UI).

### 5.9 Validasi & Keamanan Umum
- qty selalu integer **≥ 1**; tidak boleh melebihi `available` saat OUT.
- Deskripsi tujuan/keterangan **wajib**, minimal 10 karakter, tidak boleh hanya spasi.
- Item `is_active = false` tidak bisa diajukan.
- Pemohon **tidak boleh menyetujui permintaannya sendiri** (konfigurasi `ims.approval.allow_self_approval = false`).
- Approve/reject harus **idempotent** (klik ganda tidak menggandakan efek): cek status di dalam lock.
- Master data yang sudah dipakai transaksi **tidak boleh dihapus** (hanya dinonaktifkan).
- Part number: normalisasi UPPERCASE + trim; unique.
- Setiap endpoint tulis memakai FormRequest + Policy; **tidak ada** mass-assignment tanpa `$fillable`.
- Upload file: validasi mime & ukuran, simpan di disk private, akses lewat controller dengan otorisasi.

---

## 6. ROLE & PERMISSION

Petakan ke sistem role project yang ada (jangan buat sistem baru). Bila belum ada sistem permission, ikuti konvensi project dan tanyakan sebelum menambah package.

**Permission** (awalan `ims.`):
`catalog.view`, `item.manage`, `master.manage`, `request.create`, `request.view_own`, `request.view_all`, `approval.view`, `approval.act`, `stock.in`, `stock.adjust`, `stock.transfer`, `stock.handover`, `repair.request`, `repair.manage`, `repair.back_stage`, `report.view`, `report.export`, `user.manage`, `audit.view`.

| Role | Hak utama |
|---|---|
| **Pemohon / Teknisi** | lihat katalog, buat picklist & ajukan, ajukan perbaikan, lihat milik sendiri |
| **Admin Gudang** | semua pencatatan stok (masuk, transfer, opname), serah terima, kelola repair rak 1–3, kelola master barang |
| **Approver / Supervisor** | lihat & setujui/tolak semua pengajuan (tidak untuk miliknya sendiri) |
| **Manager** | = Approver + mundur rak repair + lihat semua laporan |
| **Auditor** | read-only: riwayat, laporan, audit log |
| **Admin IMS** | semuanya + data master + hak akses |

---

## 7. SPESIFIKASI MODUL (SESUAI DIAGRAM PERENCANAAN)

> Catatan: pada diagram, setiap modul menampilkan 3 sub fitur dan tombol "Lihat semua (n)". Sub fitur yang **tidak terlihat** di diagram ditandai **[disimpulkan]** — tetap dibangun, boleh disesuaikan pemilik project.

### MODUL 1 — Katalog Barang (FASE 1) — 5 sub fitur

1. **Daftar Semua Barang** — tabel server-side pagination (default 15/halaman). Kolom: foto kecil, Part Number, Nama, Kategori, Total Stok, Tersedia, Status ketersediaan, aksi. Tombol "Tambah ke Picklist" per baris (qty input).
2. **Detail Barang** — halaman: info utama, deskripsi, PN alternatif, kompatibilitas pesawat, **rincian stok per lokasi** (on_hand, reserved, available), jumlah sedang di repair (rak 1/2), daftar serial (jika serial), 10 pergerakan terakhir, tombol ke Kartu Stok.
3. **Cari & Filter Barang** — pencarian di PN, nama, deskripsi, PN alternatif, serial; filter kategori, lokasi, status ketersediaan, tipe pesawat, tipe pelacakan, rotable.
4. **[disimpulkan] Kelola Barang (CRUD)** — tambah/ubah/nonaktifkan barang (permission `item.manage`), upload foto, kelola PN alternatif & kompatibilitas.
5. **[disimpulkan] Indikator & Peringatan Stok** — badge: **Tersedia** (hijau), **Menipis** (kuning: available ≤ min_stock), **Habis** (merah: available = 0), **Nonaktif** (abu); widget "Stok Menipis" untuk admin.

Route: `GET /ims/catalog`, `GET /ims/catalog/{item}`, `GET|POST /ims/items`, `PUT /ims/items/{item}`, `PATCH /ims/items/{item}/toggle-active`.

### MODUL 2 — Peminjaman Barang (FASE 1) — 4 sub fitur

1. **Ajukan Pengambilan** — Picklist (keranjang) → halaman pengajuan: tabel baris (item, lokasi sumber, qty, serial jika perlu), `usage_type`, `expected_return_date` (jika loan), **Deskripsi Tujuan Penggunaan (wajib)**, No. WO, registrasi pesawat, departemen, lampiran. Tombol: Simpan Draft, Ajukan.
2. **Kunci Barang** — otomatis saat Ajukan (`reserve`); ditampilkan di katalog sebagai "Terkunci: n"; dilepas otomatis saat reject/cancel.
3. **Catatan Pengambilan** — setelah approved, Admin Gudang membuka dokumen → isi nama pengambil, waktu, catatan serah terima → status `completed`.
4. **[disimpulkan] Pengembalian Barang** — buat transaksi IN `return_loan` dari daftar pinjaman outstanding; sub-halaman "Pinjaman Saya" & "Pinjaman Terlambat".

Halaman tambahan: "Permintaan Saya" (daftar + filter status), detail permintaan (timeline status + approval).
Route prefix: `/ims/requests` (index, create, store, show, submit, cancel, handover), `/ims/picklist` (add, update qty, remove).

### MODUL 3 — Persetujuan Pinjam (FASE 2) — 4 sub fitur

1. **Daftar Menunggu Persetujuan** — semua transaksi `pending_approval` (OUT, IN, adjustment, transfer, repair_in) dengan filter tipe/pemohon/tanggal/prioritas; badge jumlah di sidebar; urut prioritas AOG dulu.
2. **Setuju atau Tolak** — halaman detail menampilkan seluruh baris, deskripsi, stok saat ini vs qty diminta, dampak stok setelah approve. Tombol Setuju (catatan opsional) / Tolak (alasan **wajib**). Dijalankan oleh `ApprovalService` → memanggil `StockService`/`RepairService` dalam **satu transaksi DB**.
3. **Catatan Persetujuan** — tiap keputusan menyimpan approver, waktu, catatan; tampil sebagai timeline di detail dokumen.
4. **[disimpulkan] Riwayat Persetujuan** — daftar keputusan yang sudah diambil, filter tanggal/approver/status.

Notifikasi: pemohon menerima notifikasi (database; mail jika dikonfigurasi) saat disetujui/ditolak; approver menerima saat ada pengajuan baru.

### MODUL 4 — Perbaikan Barang (FASE 2) — 4 sub fitur

1. **Ajukan Perbaikan** — form: barang/serial, qty, kerusakan (wajib), sumber, prioritas, foto, registrasi pesawat → transaksi `repair_in` → approval.
2. **Menunggu Repair (RAK 1)** — tabel `ims_repair_waiting`; aksi: Mulai Repair.
3. **Proses Repair (RAK 2)** — tabel `ims_repair_in_progress`; aksi: Update Progres, Selesaikan Repair.
4. **[disimpulkan] Selesai Repair (RAK 3)** — tabel `ims_repair_completed`; aksi: Kembalikan ke Stok / Tandai Scrap / Kembalikan Apa Adanya.

Tampilan: halaman utama **3 tab/kolom bergaya papan** (Menunggu | Proses | Selesai) dengan hitungan di tiap tab, filter prioritas & pencarian, indikator **lama berada di rak** (hari). Detail repair menampilkan `repair_logs` sebagai timeline.

### MODUL 5 — Pencatatan Stok (FASE 2) — 4 sub fitur

1. **Catat Barang Masuk** — transaksi IN (Bagian 5.3) dengan deskripsi & keterangan wajib, supplier, lokasi tujuan, serial/batch, sertifikat, lampiran.
2. **Catat Barang Keluar** — transaksi OUT langsung oleh Admin Gudang (untuk kebutuhan non-picklist); **tetap melewati approval**; aturan sama seperti 5.2.
3. **Penyesuaian Stok** — stock opname (Bagian 5.5).
4. **[disimpulkan] Transfer Lokasi** — Bagian 5.6.

Halaman "Saldo Stok": stok per barang per lokasi, filter lokasi/kategori, tombol ke Kartu Stok.

### MODUL 6 — Riwayat & Laporan (FASE 3) — 4 sub fitur

1. **Riwayat Keluar-Masuk** — dari `ims_stock_movements` + dokumen terkait: tanggal, dokumen, barang, lokasi, qty (+/−), saldo, pelaku, deskripsi.
2. **Riwayat per Barang (Kartu Stok)** — pilih barang → ledger kronologis dengan saldo berjalan, filter lokasi; ringkasan: total masuk, total keluar, saldo.
3. **Filter Tanggal** — rentang tanggal + filter tipe pergerakan, lokasi, kategori, pemohon, departemen, registrasi pesawat.
4. **[disimpulkan] Ekspor & Laporan Ringkas** — ekspor Excel/CSV/PDF (jika package tersedia; kalau tidak CSV native). Laporan: Ringkasan Stok per Lokasi, Stok Menipis, Pinjaman Outstanding & Terlambat, Repair (jumlah per rak, rata-rata durasi/turnaround), Aktivitas per Pengguna.

Semua laporan: query dengan index, pagination/chunk, **tidak boleh N+1**.

### MODUL 7 — Pengguna & Hak Akses (FASE 4) — 3 sub fitur

1. **Daftar Pengguna** — **gunakan tabel/halaman user existing**; tambahkan hanya kolom/relasi IMS yang perlu (mis. departemen) lewat migration terpisah yang aman. Jangan buat tabel user baru.
2. **Peran & Hak Akses** — UI mengatur role ↔ permission `ims.*` (Bagian 6) mengikuti sistem existing.
3. **Departemen** — kelola departemen (reuse bila sudah ada).

### MODUL 8 — Data Master (digambarkan di bagian bawah diagram yang terpotong; dibuat sebagai pondasi Fase 0)

CRUD dengan pencarian, aktif/nonaktif, validasi unique untuk: **Kategori, Satuan, Lokasi (pohon), Supplier/Vendor Repair, Tipe Pesawat, Departemen** (jika belum ada). Impor CSV untuk **Barang** dan **Lokasi** (opsional, dengan pratinjau & laporan error per baris).

---

## 8. PANDUAN UI/UX

- Gunakan **layout & komponen existing**; tampilan baru harus terlihat seperti bagian dari aplikasi yang sama.
- Label dan pesan error **Bahasa Indonesia**. Format tanggal `dd MMM yyyy HH:mm`, zona Asia/Jakarta.
- Badge status: Draft (abu), Menunggu Persetujuan (kuning), Disetujui (biru), Selesai (hijau), Ditolak (merah), Dibatalkan (abu gelap).
- Tabel: pagination server-side, pencarian debounce, filter tersimpan di query string, empty state yang informatif.
- Picklist: ikon keranjang dengan counter di header halaman katalog; panel samping/drawer berisi ringkasan.
- Aksi destruktif/penting (Setuju, Tolak, Batal, Pindah Rak) memakai **dialog konfirmasi**.
- Tombol submit dinonaktifkan saat proses (cegah klik ganda); tampilkan loading & notifikasi sukses/gagal.
- Responsif (dipakai juga di tablet gudang). Aksesibel: label form, kontras, fokus keyboard.

---

## 9. KONFIGURASI (`config/ims.php`)

```php
return [
  'prefix' => ['out'=>'OUT','in'=>'IN','adjustment'=>'ADJ','transfer'=>'TRF','repair'=>'RPR'],
  'approval' => [
      'allow_self_approval' => false,
      'levels' => [ // per tipe transaksi; default 1 level
          'out' => ['ims_approver'], 'in' => ['ims_approver'], 'adjustment' => ['ims_approver'],
          'transfer' => ['ims_approver'], 'repair_in' => ['ims_approver'],
      ],
      'repair_result_requires_approval' => true,
      'transfer_same_warehouse_requires_approval' => true,
  ],
  'min_description_length' => 10,
  'loan_overdue_notify_days' => [0, 3],
  'attachments' => ['disk' => 'local', 'max_kb' => 5120, 'mimes' => ['jpg','jpeg','png','pdf']],
  'pagination' => 15,
];
```

---

## 10. DATA AWAL (SEEDER)

**`ImsPermissionSeeder`** — semua permission & role di Bagian 6 (idempotent, pakai `updateOrCreate`/`firstOrCreate`).
**`ImsMasterSeeder`** — kategori (Avionics, Hydraulic, Landing Gear, Engine, Electrical, Pneumatic, Consumable, Tools; sertakan ATA chapter), satuan (PCS, EA, SET, LTR, KG, MTR), lokasi contoh (`WH-A` → `WH-A-R01` → `WH-A-R01-S01`, `WH-B`, `REPAIR-AREA`, `QUARANTINE`), tipe pesawat contoh.
**`ImsDemoSeeder`** (hanya environment local/staging) — ±30 barang dengan PN **fiktif** (mis. `PN-100234-A`), stok tersebar di beberapa lokasi, beberapa transaksi contoh di berbagai status, beberapa record di tiap rak repair.
Semua seeder **aman dijalankan ulang** dan tidak menyentuh data existing.

---

## 11. TESTING (WAJIB)

Gunakan framework test project (PHPUnit/Pest). Minimal:

**Unit/Feature `StockService`**
- reserve → available berkurang, on_hand tetap.
- commitOut → on_hand & reserved berkurang sebesar qty; ledger benar (balance_before/after).
- reserve melebihi available → gagal, data tidak berubah.
- receive qty 5 → on_hand +5.
- adjust ke angka < reserved → gagal.
- transfer atomik (gagal di tengah → rollback total).
- **Konkurensi**: dua permintaan bersamaan untuk stok terakhir → hanya satu berhasil.

**Feature alur bisnis**
- **Skenario OUT**: stok 10; ajukan 5 → pending, reserved 5, available 5, on_hand 10; approve → on_hand 5, reserved 0; reject (kasus lain) → reserved kembali 0, on_hand tetap 10.
- **Skenario IN**: ajukan masuk 5 → belum ada perubahan stok; approve → on_hand +5; reject → tetap.
- Pemohon tidak bisa approve miliknya sendiri; user tanpa permission mendapat 403.
- Deskripsi kosong/pendek → validasi gagal.
- Approve dua kali (double submit) → efek hanya sekali.
- Transisi status ilegal ditolak.
- **Skenario Repair**: repair_in approve → muncul di rak 1 → pindah rak 2 → rak 3 → kembalikan ke stok (setelah approve) → stok bertambah; `repair_logs` lengkap; `repair_code` aktif hanya di satu rak.
- Pengembalian pinjaman melebihi qty pinjaman → gagal.
- Barang nonaktif tidak bisa diajukan; master yang dipakai tidak bisa dihapus.

---

## 12. FASE PENGERJAAN

Urutan mengikuti diagram (Fase 1–4), ditambah **Fase 0 (Fondasi)** karena fase berikutnya bergantung padanya.

### FASE 0 — Fondasi (audit, master, inti stok)
1. Audit project (Bagian 2) dan laporkan.
2. Migration semua tabel master + item + stok + movements + transaksi + approval + repair 3 rak + pendukung (Bagian 4). Enum, Model (relasi, casts, fillable), Factory.
3. `config/ims.php`, `DocumentNumberService`, `StockService` (lengkap + test), `ApprovalService` (kerangka), `RepairService` (kerangka).
4. `routes/ims.php` + menu sidebar (grup IMS, item tampil sesuai permission) + halaman placeholder.
5. Permission & role seeder (Bagian 6/10), Data Master CRUD (Modul 8) + seeder master.
**DoD**: migrate sukses tanpa menyentuh tabel existing; test StockService hijau; menu IMS muncul; CRUD master berjalan.

### FASE 1 — Katalog Barang & Peminjaman Barang
Modul 1 + Modul 2 (picklist, ajukan, kunci barang, permintaan saya, pembatalan). Approval **belum** aktif: dokumen berhenti di `pending_approval` dengan reserve aktif.
**DoD**: pemohon bisa mencari barang, menyusun picklist, mengajukan dengan deskripsi wajib; reserved berubah benar; cancel melepas kunci.

### FASE 2 — Persetujuan, Perbaikan, Pencatatan Stok
Modul 3 + Modul 4 + Modul 5: approval end-to-end (OUT, IN, adjustment, transfer, repair_in), serah terima, pengembalian pinjaman, 3 rak repair, notifikasi.
**DoD**: seluruh skenario Bagian 11 hijau; stok berubah **hanya** setelah approve sesuai qty; ledger lengkap.

### FASE 3 — Riwayat & Laporan
Modul 6: riwayat, kartu stok, filter, ekspor, laporan ringkas, widget dashboard IMS (opsional).
**DoD**: saldo di kartu stok **cocok** dengan `ims_stocks`; laporan cepat pada ±10.000 baris movement (cek index, tanpa N+1).

### FASE 4 — Pengguna & Hak Akses
Modul 7: UI role/permission IMS, departemen, audit log viewer; pengamanan akhir (cek policy di semua route).
**DoD**: tiap role hanya melihat/melakukan hak-nya; uji akses tiap route.

**Penutup (setelah Fase 4)**: tinjauan keamanan & performa, dokumentasi singkat `docs/ims.md` (cara pakai, role, alur, konfigurasi), daftar perubahan pada file existing.

---

## 13. PROMPT SIAP-TEMPEL UNTUK ANTIGRAVITY

Tempel dokumen ini ke konteks/workspace Antigravity, lalu kirim prompt per fase:

**Prompt awal (Fase 0)**
```
Baca seluruh dokumen IMS_Tracker_Barang_Spesifikasi_Antigravity.md. Ini modul BARU yang ditambahkan
ke project Laravel yang sudah ada, bukan project baru. Kerjakan HANYA Fase 0: lakukan audit
project (Bagian 2) dan tampilkan ringkasannya dulu, lalu bangun fondasi sesuai Bagian 12 Fase 0.
Patuhi Aturan Emas (Bagian 0): prefix ims_, jangan ubah tabel existing, jangan migrate:fresh,
jangan install package tanpa izin, semua perubahan stok lewat StockService. Setelah selesai,
jalankan test, lalu berhenti dan laporkan file yang dibuat/diubah dan cara mencobanya.
```

**Fase 1**
```
Lanjut Fase 1 sesuai dokumen: Modul 1 (Katalog Barang) dan Modul 2 (Peminjaman Barang).
Gunakan layout dan komponen UI yang sudah ada. Deskripsi tujuan wajib, kunci barang (reserve)
saat pengajuan, pembatalan melepas kunci. Tulis test. Berhenti dan laporkan setelah selesai.
```

**Fase 2**
```
Lanjut Fase 2 sesuai dokumen: Modul 3 (Persetujuan), Modul 4 (Perbaikan dengan 3 rak/3 tabel),
dan Modul 5 (Pencatatan Stok). Stok on_hand hanya berubah saat APPROVE sebesar qty tiap baris.
Implementasikan semua skenario test di Bagian 11. Berhenti dan laporkan setelah selesai.
```

**Fase 3**
```
Lanjut Fase 3: Modul 6 (Riwayat & Laporan) termasuk kartu stok, filter tanggal, dan ekspor.
Pastikan tidak ada N+1 dan saldo kartu stok cocok dengan ims_stocks. Berhenti dan laporkan.
```

**Fase 4**
```
Lanjut Fase 4: Modul 7 (Pengguna & Hak Akses) memakai sistem user/role yang sudah ada.
Lakukan pengecekan policy pada semua route IMS, tulis test akses per role, dan buat docs/ims.md.
Berhenti dan laporkan.
```

---

## 14. ASUMSI DEFAULT (boleh diubah pemilik project)

1. Approval **1 level** (struktur siap multi-level).
2. Pemohon **tidak boleh** menyetujui miliknya sendiri.
3. Qty bilangan bulat; satuan hanya label (tanpa konversi satuan).
4. Barang masuk, keluar, penyesuaian, transfer, dan hasil repair **semuanya perlu approval**.
5. Stok di `ims_stocks` hanya barang **layak pakai**; barang rusak dilacak lewat 3 rak repair.
6. Untuk item serial/batch, `ims_stocks` tetap menyimpan total agregat per lokasi, dan detail per unit ada di `ims_item_serials`.
7. Repair dari pesawat/vendor luar tidak mengubah stok saat masuk rak 1 (barang belum pernah tercatat di stok).
8. Sub fitur bertanda **[disimpulkan]** dan **Modul 8 (Data Master)** dibuat walau belum terlihat jelas di diagram.
9. Ekspor: Excel/PDF jika package sudah ada, jika tidak CSV native.
10. File lampiran disimpan di disk private, bukan publik.

---

## 15. CHECKLIST AKHIR (Definition of Done Keseluruhan)

- [ ] Tidak ada tabel/kode existing yang rusak; semua tabel baru berawalan `ims_`.
- [ ] Semua perubahan stok melalui `StockService`; ledger immutable dan konsisten dengan saldo.
- [ ] Barang keluar: picklist → deskripsi wajib → approval → stok −qty setelah approve.
- [ ] Barang masuk: deskripsi/keterangan wajib → approval → stok +qty setelah approve.
- [ ] 3 rak repair (3 tabel) dengan perpindahan atomik, log lengkap, dan pengembalian ke stok.
- [ ] Katalog rapi: PN, deskripsi, kategori, lokasi, status ketersediaan, stok per lokasi.
- [ ] Data master lengkap dengan seeder.
- [ ] Hak akses per role teruji; tidak ada route tanpa otorisasi.
- [ ] Test hijau, tidak ada N+1 pada daftar/laporan, UI konsisten dengan aplikasi existing.
- [ ] Dokumentasi `docs/ims.md` dan daftar perubahan file existing tersedia.
