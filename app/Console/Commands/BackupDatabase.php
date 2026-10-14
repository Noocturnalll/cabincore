<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Daily copy of the database to storage/app/backups, keeping the last N days.
 * SQLite: a consistent copy with VACUUM INTO. MySQL / MariaDB: mysqldump (must be on the PATH of the server).
 * A backup that was never restored is not a backup: restore one into a scratch database once a month.
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database {--keep=14 : Days of backups to keep}';

    protected $description = 'Back up the database to storage/app/backups and prune old copies';

    public function handle(): int
    {
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $stamp = now()->format('Ymd-His');
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $target = "{$dir}/db-{$stamp}.sqlite";
            DB::statement('VACUUM INTO '.DB::connection()->getPdo()->quote($target));
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $c = config('database.connections.'.config('database.default'));
            $target = "{$dir}/db-{$stamp}.sql";
            $process = new Process(
                ['mysqldump', '--single-transaction', '--routines', '-h', $c['host'], '-P', (string) $c['port'], '-u', $c['username'], $c['database'], '--result-file='.$target],
                null,
                ['MYSQL_PWD' => (string) $c['password']],
            );
            $process->setTimeout(600)->run();
            if (! $process->isSuccessful()) {
                $this->error('mysqldump gagal: '.trim($process->getErrorOutput()));

                return self::FAILURE;
            }
        } else {
            $this->error("Driver {$driver} belum didukung oleh perintah ini.");

            return self::FAILURE;
        }

        $size = round(filesize($target) / 1048576, 1);
        $this->info("Backup: {$target} ({$size} MB)");

        $cutoff = now()->subDays((int) $this->option('keep'))->getTimestamp();
        foreach (File::files($dir) as $file) {
            if (str_starts_with($file->getFilename(), 'db-') && $file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
                $this->line('Dihapus: '.$file->getFilename());
            }
        }

        return self::SUCCESS;
    }
}
