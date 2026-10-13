<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop any leftover sqlite temporary table from failed migration attempts
        DB::statement('DROP TABLE IF EXISTS __temp__nsrdi_logs');

        // First migrate all existing records before any schema alterations
        $logs = DB::table('nsrdi_logs')->get();
        foreach ($logs as $log) {
            $cat = strtoupper(trim((string) $log->category));
            $isPainting = false;

            if (in_array($cat, ['PAINTING', 'AIRCRAFT PAINTING', 'PAINT', 'PPO', 'PENGECATAN', 'CAT PAINTING'], true) || str_contains($cat, 'PAINT') || str_contains($cat, 'PENGECATAN')) {
                $isPainting = true;
            } else {
                $text = strtolower(trim(($log->category ?? '').' '.($log->description ?? '').' '.($log->work_group ?? '')));
                if (
                    str_contains($text, 'paint') ||
                    str_contains($text, 'pengecatan') ||
                    str_contains($text, 'ppo') ||
                    str_contains($text, 'repaint') ||
                    preg_match('/\bcat\s+painting\b/i', $text) ||
                    preg_match('/\baircraft\s+painting\b/i', $text)
                ) {
                    $isPainting = true;
                }
            }

            $targetCategory = $isPainting ? 'PAINTING' : 'CBM';

            if ($log->category !== $targetCategory) {
                DB::table('nsrdi_logs')->where('id', $log->id)->update([
                    'category' => $targetCategory,
                ]);
            }
        }

        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->string('category')->default('CBM')->nullable()->change();
            $table->index('category', 'nsrdi_logs_category_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nsrdi_logs', function (Blueprint $table) {
            $table->dropIndex('nsrdi_logs_category_index');
            $table->string('category')->nullable()->change();
        });
    }
};
