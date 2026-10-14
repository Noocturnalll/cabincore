<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Who created, changed or deleted the data that decides who may do what and what the numbers are measured against:
 * accounts, the Bandara / aircraft / category / division / position masters and the capacity stations.
 * Passwords and tokens are never written to the log, only that they changed. Jobs and imports (no signed-in user)
 * are not logged here: their own run history is the record.
 */
class AuditObserver
{
    private const SKIP_FIELDS = ['updated_at', 'created_at', 'remember_token', 'deleted_at'];

    private const SECRET_FIELDS = ['password'];

    public function created(Model $model): void
    {
        $this->log($model, 'Tambah', $this->label($model));
    }

    public function updated(Model $model): void
    {
        $fields = array_diff(array_keys($model->getChanges()), self::SKIP_FIELDS);
        if (! $fields) {
            return;
        }

        $parts = [];
        foreach ($fields as $field) {
            if (in_array($field, self::SECRET_FIELDS, true)) {
                $parts[] = "$field diubah";

                continue;
            }
            $parts[] = sprintf('%s: %s -> %s', $field, $this->short($model->getOriginal($field)), $this->short($model->getAttribute($field)));
        }

        $this->log($model, 'Ubah', $this->label($model).' ('.implode('; ', $parts).')');
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'Hapus', $this->label($model));
    }

    private function log(Model $model, string $verb, string $description): void
    {
        // no signed-in person (scheduler, seeder, import command): nothing to attribute
        if (! auth()->check()) {
            return;
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $verb.' '.class_basename($model),
            'description' => mb_substr($description, 0, 1000),
            'ip_address' => request()?->ip(),
        ]);
    }

    private function label(Model $model): string
    {
        foreach (['nik', 'kode', 'registration', 'station_code', 'name', 'nama'] as $key) {
            $value = $model->getAttribute($key);
            if ($value !== null && $value !== '') {
                return class_basename($model).' '.$value.' (#'.$model->getKey().')';
            }
        }

        return class_basename($model).' #'.$model->getKey();
    }

    private function short(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '∅';
        }

        return mb_substr(is_scalar($value) ? (string) $value : json_encode($value), 0, 60);
    }
}
