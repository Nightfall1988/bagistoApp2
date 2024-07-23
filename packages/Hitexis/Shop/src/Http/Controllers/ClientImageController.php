<?php

namespace Hitexis\Shop\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ClientImageController {
    public function show($filename)
    {
        $path = storage_path('app/public/client_logos/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        $file = Storage::get('public/client_logos/' . $filename);
        $type = Storage::mimeType('public/client_logos/' . $filename);

        return response($file, 200)->header('Content-Type', $type);
    }
}