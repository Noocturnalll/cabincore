<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive only: carries the extra GSS DJA columns (DJA WO / DJA DMI sheets) so the web tables can follow the sheet.
 * Nothing existing is changed or removed.
 */
return new class extends Migration
{
    private const WO = [
        'ata' => 'string', 'task_card' => 'string', 'task_card_description' => 'text',
        'rii' => 'string', 'etops' => 'string', 'eru' => 'string', 'iera' => 'string',
        'site' => 'string', 'due_date_wo' => 'date', 'class_wo' => 'string',
        'materials' => 'text', 'tools' => 'text', 'plan_date' => 'date', 'planner' => 'string',
        'ppc_onduty' => 'string', 'close_date' => 'date', 'highlight' => 'string',
    ];

    private const DMI = [
        'work_group' => 'string', 'defer_type' => 'string', 'status_wo' => 'string', 'wo_number' => 'string',
        'ata' => 'string', 'due_date_dmi' => 'date', 'picklist_number' => 'string', 'aoc' => 'string',
        'type' => 'string', 'plan_date' => 'date', 'close_date' => 'date', 'highlight' => 'string',
        'operator' => 'string',
    ];

    public function up(): void
    {
        $this->add('wo_logs', self::WO);
        $this->add('dmi_logs', self::DMI);
    }

    public function down(): void
    {
        foreach (['wo_logs' => self::WO, 'dmi_logs' => self::DMI] as $table => $cols) {
            $drop = array_values(array_filter(array_keys($cols), fn ($c) => Schema::hasColumn($table, $c)));
            if ($drop) {
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn($drop));
            }
        }
    }

    private function add(string $table, array $cols): void
    {
        Schema::table($table, function (Blueprint $t) use ($table, $cols) {
            foreach ($cols as $name => $type) {
                if (! Schema::hasColumn($table, $name)) {
                    $type === 'text' ? $t->text($name)->nullable() : ($type === 'date' ? $t->date($name)->nullable() : $t->string($name)->nullable());
                }
            }
        });
    }
};
