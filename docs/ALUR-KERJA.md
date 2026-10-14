# Alur kerja harian — Cluster Cabin Maintenance

Satu halaman: siapa mengisi apa, kapan, dan ke mana datanya mengalir.

## 1. Sumber data → bank data → laporan

| Tahap | Apa yang terjadi | Siapa / apa |
|---|---|---|
| Rencana | DJA dikirim planner lewat Google Sheet, ditarik otomatis tiap 15 menit | Sistem (sinkron DJA) |
| Pelaksanaan | Laporan Leader (Excel) diimpor: nomor dokumen cocok dengan DJA → DJA ditutup; tidak cocok → unplanned | Admin CGK / PIC |
| Penutupan hari | Pukul **18:00** hari operasional berganti. Yang sudah Closed, atau Open dengan kode dan remarks lengkap, masuk **Daily Report (bank data)** | Sistem (arsip tiap 15 menit) |
| Tertahan | Open tanpa kode / remarks tidak masuk bank, dihitung dan diingatkan tiap jam | COD / PIC melengkapi alasan |
| Dipakai | Daily Report, Capacity, KPI, dashboard, laporan eksekutif membaca log yang sama | — |

Aturan yang berlaku di semua laporan:

- **Station:** tugas DJA dihitung di **plan station**; tugas unplanned di **act station**.
- **Airline (AOC):** ditentukan oleh **master pesawat** (registrasi → AOC). Awalan registrasi tidak dipakai karena PK-L.. dipakai Lion dan Batik.
- **WO yang terbawa ke hari berikutnya** dihitung **sekali** per periode, dengan status terakhirnya.
- **Station tanpa MP CBM** (mis. BDJ, NBX): pekerjaannya tetap terhitung; kapasitas tampil "–" dan diberi label *tanpa MP*, bukan 0%.

## 2. Siapa mengisi apa

| Peran | Mengisi | Memantau |
|---|---|---|
| **COD** | Form *Barang Masuk Repair*; permintaan barang untuk pesawat; teruskan permintaan planner / work group / complaint ke lapangan (WhatsApp) | DJA, AC Movement, stok, Perlu perhatian |
| **Admin COD** | Draft LGT: pesawat, station, STA, STD | LGT, COD desk |
| **PIC Finishing** (juga CBM / AIEC) | Mengisi pekerjaan dan status LGT dari draft | LGT, KPI |
| **PIC Supporting** (tim repair) | ACC barang rusak → Rak Repair 1 → 2 → 3 → kembali ke rak gudang | Repair, stok |
| **Manager** | Persetujuan permintaan barang | Semua dashboard |
| **Super Admin** | Akun, role, master data (Bandara, Pesawat, Kategori) | Audit, Sumber Data |

## 3. Alur barang rusak (IMS)

```
Lapangan → COD isi Form Barang Masuk → [menunggu ACC]
         → tim repair ACC → Rak Repair 1 (antrian)
         → Rak Repair 2 (proses) → Rak Repair 3 (selesai)
         → kembali ke rak gudang (stok bertambah)
```
Rak Repair berbeda dari rak gudang. Setiap perpindahan memberi notifikasi.

## 4. Permintaan barang untuk pesawat

COD cek katalog → ajukan untuk pesawat (registrasi) → notifikasi ke Manager → disetujui: **stok berkurang 1**, pemohon diberi tahu → barang diambil dan serah terima dicatat. Ditolak: alasan dikirim ke pemohon.

## 5. LGT

Admin COD membuat draft (belum ada pekerjaan) → PIC Finishing mendapat notifikasi → mengisi pekerjaan CBM / AIEC → status Closed / Cancel → masuk LGT Monitoring.

## 6. Akun

- Login memakai **ID karyawan**.
- Password awal `Password123`; saat login pertama wajib diganti.
- Lupa password: hubungi Super Admin (menu Users → reset).
