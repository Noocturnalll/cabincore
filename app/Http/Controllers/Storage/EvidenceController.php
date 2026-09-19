<?php

namespace App\Http\Controllers\Storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'evidence' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $file = $request->file('evidence');
        $path = Storage::disk('s3')->put('evidence', $file);

        return response()->json([
            'message' => 'File uploaded successfully',
            'path' => $path
        ], 200);
    }

    public function getUrl(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        $url = Storage::disk('s3')->temporaryUrl(
            $request->path,
            now()->addMinutes(30)
        );

        return response()->json([
            'url' => $url
        ]);
    }
}
