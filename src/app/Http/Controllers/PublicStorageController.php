<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicStorageController extends Controller
{
    public function __invoke(string $path): Response
    {
        $path = ltrim($path, '/');

        abort_if($path === '' || str_contains($path, '..') || str_contains($path, "\0"), 404);
        abort_if(! Storage::disk('public')->exists($path), 404);

        $mimeType = Storage::disk('public')->mimeType($path) ?: 'application/octet-stream';
        $fileName = Str::afterLast($path, '/');

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $fileName).'"',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
