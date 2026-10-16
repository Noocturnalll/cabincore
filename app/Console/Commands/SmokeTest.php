<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/**
 * Opens every parameter-less page as a Super Admin and reports the ones that fail, with the real exception. Meant to be
 * run on the server after a deploy: `php artisan app:smoke-test`. Read only (GET requests); it does not log anyone in
 * for real, it only sets the user for the duration of the check.
 */
class SmokeTest extends Command
{
    protected $signature = 'app:smoke-test {--role=Super Admin : role whose user is used}';

    protected $description = 'Open every page as a Super Admin and report errors (also lists migrations that have not run)';

    private const SKIP = ['horizon', 'api/', 'livewire', 'storage', 'sanctum', 'logout', 'pdf', 'export', 'download', 'telescope', '_ignition', 'up'];

    public function handle(): int
    {
        @ini_set('memory_limit', '1G');   // every page is rendered inside this one process

        Artisan::call('migrate:status');
        $pending = collect(explode("\n", Artisan::output()))->filter(fn ($l) => str_contains($l, 'Pending'))->map(fn ($l) => trim(preg_replace('/\s+/', ' ', $l)));
        if ($pending->isNotEmpty()) {
            $this->error('Migrasi BELUM dijalankan ('.$pending->count().'): jalankan `php artisan migrate --force`');
            $pending->each(fn ($l) => $this->line("  {$l}"));
        } else {
            $this->info('Semua migrasi sudah dijalankan.');
        }

        $user = User::role((string) $this->option('role'))->where('status', 'active')->first();
        if (! $user) {
            $this->error('Tidak ada user aktif dengan peran itu.');

            return self::FAILURE;
        }

        $kernel = app(Kernel::class);
        $failed = 0;
        $checked = 0;

        foreach (app('router')->getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || str_contains($uri, '{') || collect(self::SKIP)->contains(fn ($s) => str_starts_with($uri, $s) || str_contains($uri, $s))) {
                continue;
            }
            $checked++;
            $request = Request::create($uri === '/' ? '/' : '/'.ltrim($uri, '/'), 'GET');
            app()->instance('request', $request);
            Auth::guard('web')->setUser($user);

            try {
                $response = $kernel->handle($request);
                gc_collect_cycles();
                if ($response->getStatusCode() >= 500) {
                    $failed++;
                    $e = $response->exception ?? null;
                    $this->line('<fg=red>500</> /'.ltrim($uri, '/').'  '.($e ? class_basename($e).': '.mb_substr($e->getMessage(), 0, 240) : ''));
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->line('<fg=red>EXC</> /'.ltrim($uri, '/').'  '.class_basename($e).': '.mb_substr($e->getMessage(), 0, 240));
            }
        }

        $this->line("Diperiksa {$checked} halaman, {$failed} error.");

        return $failed === 0 && $pending->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
