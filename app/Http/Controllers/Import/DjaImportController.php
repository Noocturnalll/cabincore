<?php

namespace App\Http\Controllers\Import;

use App\Http\Controllers\Controller;
use App\Imports\DjaImport;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class DjaImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            DB::transaction(function () use ($request) {
                Excel::import(new DjaImport, $request->file('file'));
            });

            return response()->json([
                'message' => 'DJA imported successfully.',
            ], 200);

        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = 'Row '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $errors,
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
