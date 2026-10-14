<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /** تصویر فیش را فقط بارگذارنده یا کاربری با مجوز بررسی پرداخت (در همان ساختمان) می‌بیند. */
    public function show(Request $request, int $id)
    {
        $file = File::findOrFail($id);
        $user = $request->user();

        abort_unless((int) $file->building_id === app(CurrentBuilding::class)->id(), 404);
        abort_unless((int) $file->uploader_id === (int) $user->id || $user->can('payment.review'), 403);
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->response($file->path, $file->original_name, [
            'Content-Type' => $file->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }
}
