# Menjalankan scheduler (sync otomatis)

Semua pembaruan otomatis CBM berasal dari **satu** perintah yang dijalankan **tiap menit**:

```
php artisan schedule:run
```

Laravel sendiri yang memilih tugas mana yang jatuh tempo (jadwalnya ada di `routes/console.php`):

| Tugas | Jadwal |
|---|---|
| `sync:daily-dja` (WO / DMI / NSRDI dari Google Sheets) | tiap 15 menit |
| `sync:ac-movement` | tiap 5 menit |
| `sources:sync --every=hourly` (Sumber Data: briefing evidence, CBM closing) | tiap 1 jam |
| `sources:sync --every=daily` (Sumber Data: AMM working groups, targets) | 05:30 |
| `dailyreport:auto-submit` (arsip ke Daily Report, cutoff 18:00) | tiap 15 menit |
| `dja:notify-missing-reasons` | tiap jam, 06:00-18:00 |
| `ims:notify-overdue-loans` | 08:00 |
| `report:daily`, `app:snapshot-capacity-data` | 10:00 dan 16:00 |

Tidak perlu membuat cron/task per tugas. Cukup satu pemicu per menit.

---

## 1. Lokal (Windows + Herd)

Sudah terpasang sebagai Windows Task Scheduler bernama **CBM Scheduler** (tiap 1 menit, tanpa jendela, memakai akun Anda).
Skrip pemicunya: `scripts/windows/run-scheduler.vbs`. Hasilnya dicatat di `storage/logs/scheduler.log`.

Syarat: PC menyala dan tidak sleep, dan Anda sudah login. Saat PC mati atau sleep, tidak ada yang tersinkron.

```powershell
Get-ScheduledTaskInfo -TaskName "CBM Scheduler"      # terakhir jalan, hasil (0 = OK), jadwal berikutnya
Start-ScheduledTask   -TaskName "CBM Scheduler"      # jalankan sekarang
Disable-ScheduledTask -TaskName "CBM Scheduler"      # jeda
Unregister-ScheduledTask -TaskName "CBM Scheduler" -Confirm:$false   # hapus
```

Jika PHP berpindah lokasi, set variabel `CBM_PHP` (path `php.exe`) sebagai environment variable user, lalu login ulang.

Alternatif tanpa Task Scheduler: `php artisan schedule:work` di terminal (harus dibiarkan terbuka).

---

## 2. VPS Production (Ubuntu / Debian)

### 2.1 Konfigurasi `.env` Produksi & Hardening

Pastikan konfigurasi production berikut diterapkan di `.env`:

```env
APP_NAME="CBM System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cbm.domain-anda.com
LOG_LEVEL=warning

# Database (Gunakan MySQL atau PostgreSQL di production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cbm_prod
DB_USERNAME=cbm_user
DB_PASSWORD=secret_db_password

# Machine-to-Machine Secret Tokens (Minimal 32 karakter acak)
SHEETS_SYNC_TOKEN=generate_random_32_characters_token_here
TELEGRAM_WEBHOOK_SECRET=generate_random_32_characters_secret_here

# Queue Driver
QUEUE_CONNECTION=database

# Email (SMTP Provider atau log jika menggunakan Reset Password via Admin)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@cbm.domain-anda.com"
MAIL_FROM_NAME="${APP_NAME}"
```

> **Catatan Secret Token:**
> 1. **Google Apps Script:** Setiap request sinkronisasi dari Google Apps Script harus menyertakan header `X-Sync-Token` dengan nilai yang sama persis dengan `SHEETS_SYNC_TOKEN`. Jika kosong atau salah, sinkronisasi ditolak (`403 Forbidden`).
> 2. **Telegram Webhook:** Daftarkan webhook bot menggunakan `secret_token` yang sama dengan `TELEGRAM_WEBHOOK_SECRET`:
>    ```bash
>    curl -F "url=https://cbm.domain-anda.com/api/telegram/webhook" \
>         -F "secret_token=YOUR_TELEGRAM_WEBHOOK_SECRET" \
>         https://api.telegram.org/bot<BOT_TOKEN>/setWebhook
>    ```

### 2.2 Keamanan Akun Contoh (Seeder)

- Seeder (`DatabaseSeeder`) secara default mengaktifkan flag `is_default_password = true` untuk semua akun demo bawaan.
- Password awal bawaan adalah default internal (`batam123`) atau dapat diatur via `SEED_DEFAULT_PASSWORD` di `.env`.
- Middleware `EnsurePasswordIsChanged` mewajibkan pengguna mengganti password saat login pertama kali sebelum dapat mengakses menu aplikasi lainnya.
- Untuk produksi baru, buat akun Super Admin mandiri dan hapus/nonaktifkan akun demo yang tidak diperlukan melalui menu **Pengguna**.

### 2.3 Instalasi & Build tanpa Dev Dependencies

Jalankan instalasi dependency tanpa dev tools (`barryvdh/laravel-debugbar`, `laravel/telescope`, `laravel/boost` tidak akan terpasang):

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

Setelah deploy atau update kode:
```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 2.4 Scheduler (Cron)

Salin file cron pemicu scheduler tiap menit:

```bash
sudo cp deploy/ubuntu/cbm-scheduler.cron /etc/cron.d/cbm-scheduler
sudo chmod 644 /etc/cron.d/cbm-scheduler
sudo nano /etc/cron.d/cbm-scheduler        # sesuaikan path /var/www/cbm dan user (www-data)
```

Uji scheduler:
```bash
sudo -u www-data php artisan schedule:list
sudo -u www-data php artisan sync:ac-movement
```

### 2.5 Queue Worker (Background Processing)

Karena `QUEUE_CONNECTION=database`, siapkan daemon worker agar antrean selalu diproses.

**Pilihan A: Menggunakan Systemd (Disarankan)**
```bash
sudo cp deploy/ubuntu/cbm-worker.service /etc/systemd/system/cbm-worker.service
sudo systemctl daemon-reload
sudo systemctl enable --now cbm-worker.service
sudo systemctl status cbm-worker.service
```

**Pilihan B: Menggunakan Supervisor**
```bash
sudo cp deploy/ubuntu/cbm-worker.conf /etc/supervisor/conf.d/cbm-worker.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cbm-worker:*
```

### 2.6 Backup Database Otomatis

Siapkan backup harian untuk database produksi (cron harian):

Contoh skrip backup `/var/www/cbm/deploy/backup-db.sh`:
```bash
#!/usr/bin/env bash
set -e
BACKUP_DIR="/var/backups/cbm"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
mkdir -p "$BACKUP_DIR"

# MySQL/MariaDB:
mysqldump --defaults-extra-file=/etc/mysql/cbm-backup.cnf cbm_prod | gzip > "$BACKUP_DIR/cbm_db_$TIMESTAMP.sql.gz"

# Hapus backup yang lebih tua dari 14 hari:
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +14 -delete
```
Pasang di crontab root:
```bash
0 2 * * * /bin/bash /var/www/cbm/deploy/backup-db.sh >> /var/log/cbm-backup.log 2>&1
```

### 2.7 HTTPS & Web Server (Nginx + Certbot)

Pasang sertifikat SSL gratis dengan Let's Encrypt:
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d cbm.domain-anda.com
```

Konfigurasi Nginx mengarahkan traffic HTTP ke HTTPS secara otomatis dan membatasi akses file `.env`.

### 2.8 Migrasi ke MySQL / PostgreSQL & Verifikasi Test

Di server lokal/pengujian, tes berjalan dengan SQLite. Sebelum rilis di MySQL/PostgreSQL:
1. Pastikan charset database diset ke `utf8mb4` dan collation `utf8mb4_unicode_ci`.
2. Buat database pengujian khusus (mis. `cbm_testing`).
3. Jalankan test suite dengan menunjuk ke database target untuk memastikan kompatibilitas dialect SQL:
   ```bash
   DB_CONNECTION=mysql DB_DATABASE=cbm_testing php -d memory_limit=512M vendor/bin/phpunit
   ```

### 2.9 Penanganan Lupa Password & Email

1. **Jika Menggunakan SMTP:** Isi kredensial SMTP di `.env`. Fitur *Lupa Password?* akan mengirimkan email token reset kepada pengguna.
2. **Jika `MAIL_MAILER=log`:** Email tidak terkirim keluar. Pengguna yang lupa password dapat meminta Super Admin untuk mereset password via menu **Pengguna > Reset Password**. Password akan kembali ke default dan pengguna dipaksa membuat password baru saat login.
