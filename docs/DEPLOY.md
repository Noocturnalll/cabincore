# Deploy — daftar periksa

## 1. `.env` production

```
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
APP_URL=https://<domain>
APP_KEY=<php artisan key:generate --show>
SESSION_SECURE_COOKIE=true
TRUSTED_PROXIES=*            # di belakang nginx / load balancer HTTPS
SHEETS_SYNC_TOKEN=<acak 32+ karakter>      # header X-Sync-Token di Apps Script
TELEGRAM_WEBHOOK_SECRET=<acak 32+ karakter>
DB_CONNECTION=mysql          # atau tetap sqlite; lihat catatan di bawah
```

## 2. Urutan

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --class=InitialUsersSeeder      # role, permission, 8 akun awal
php artisan stations:clean --dry-run                 # lihat dulu, lalu tanpa --dry-run
php artisan master:missing-aircraft                  # CSV registrasi & WG yang perlu diisi
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Jangan menjalankan `DatabaseSeeder` di production (akun contoh bawaan).

## 3. Scheduler

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Menjalankan: sinkron DJA (15 mnt), AC Movement (5 mnt), Sumber Data, arsip Daily Report (15 mnt),
`ops:watch` (30 mnt, memberi peringatan bila sinkron atau arsip berhenti), `app:backup-database` (02:00).

## 4. Setelah naik

1. Login `212223`, ganti password.
2. Minta tujuh pengguna lain login dan ganti password hari itu juga (password awal dikenal semua orang sampai diganti).
3. Cek Sumber Data: semua sinkron sehat.
4. Cek `php artisan ops:watch --dry-run` → "Semua normal".

## 5. Backup

`php artisan app:backup-database` menyimpan ke `storage/app/backups` (14 hari terakhir).
Salin juga ke luar server. **Uji restore sebulan sekali** ke database kosong.

## 6. Catatan

- Seluruh test berjalan di SQLite. Jika production memakai MySQL, jalankan test suite di MySQL sebelum go-live.
- Daftar station dan pesawat yang perlu dilengkapi dibuat oleh `stations:clean` dan `master:missing-aircraft`.
