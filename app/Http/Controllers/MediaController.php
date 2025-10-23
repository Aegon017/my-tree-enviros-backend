<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

final class MediaController extends Controller
{
    public function show(Request $request, int $id)
    {
        // Use Spatie Media model to locate media record
        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($id);

        // Get path to file on disk
        $path = $media->getPath();

        if (! $path || ! file_exists($path)) {
            abort(404);
        }

        // Stream the file with proper headers
        return response()->file($path);
    }
}
