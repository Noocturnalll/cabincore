<?php

namespace App\Services\Ims;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function generate($type)
    {
        return DB::transaction(function () use ($type) {
            $prefix = config("ims.prefix.{$type}", strtoupper(substr($type, 0, 3)));
            $period = date('Ym');
            
            $sequence = DB::table('ims_document_sequences')
                ->where('prefix', $prefix)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();
                
            if (!$sequence) {
                DB::table('ims_document_sequences')->insert([
                    'prefix' => $prefix,
                    'period' => $period,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $number = 1;
            } else {
                $number = $sequence->last_number + 1;
                DB::table('ims_document_sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'last_number' => $number,
                        'updated_at' => now()
                    ]);
            }
            
            return sprintf("%s-%s-%04d", $prefix, $period, $number);
        });
    }
}
