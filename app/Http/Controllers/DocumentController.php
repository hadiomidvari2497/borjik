<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\File as StoredFile;
use App\Models\Role;
use App\Support\CurrentBuilding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DocumentController extends Controller
{
    private const CATEGORIES = [
        'regulations' => 'آیین‌نامه و مقررات',
        'minutes' => 'صورت‌جلسه‌ها',
        'financial' => 'اسناد مالی',
        'contracts' => 'قراردادها',
        'technical' => 'فنی و تأسیسات',
        'other' => 'سایر اسناد',
    ];

    public function index(Request $request, CurrentBuilding $current): View
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $documents = Document::query()
            ->with(['uploadedBy', 'file', 'roles'])
            ->where(function ($query) use ($current) {
                $query->whereDoesntHave('roles')
                    ->orWhereHas('roles', fn ($roles) => $roles->whereIn('roles.key', $current->roles()));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('documents.index', [
            'documents' => $documents,
            'categories' => self::CATEGORIES,
            'roles' => Role::forBuilding($buildingId)->orderBy('name_fa')->get(),
            'canManage' => $request->user()->hasPermission('document.manage'),
            'totalDocuments' => Document::count(),
            'categoryCounts' => Document::query()->select('category', DB::raw('count(*) as aggregate'))->groupBy('category')->pluck('aggregate', 'category'),
        ]);
    }

    public function store(Request $request, CurrentBuilding $current): RedirectResponse
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);
        abort_unless($request->user()->hasPermission('document.manage'), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,jpg,jpeg,png'],
            'role_ids' => ['nullable', 'array', 'max:30'],
            'role_ids.*' => ['required', 'integer', 'distinct'],
        ], [
            'file.max' => 'حجم فایل نباید بیشتر از ۲۰ مگابایت باشد.',
            'file.mimes' => 'فرمت فایل انتخاب‌شده مجاز نیست.',
        ]);

        $roleIds = collect($data['role_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($roleIds->isNotEmpty()) {
            $validIds = Role::forBuilding($buildingId)->whereIn('id', $roleIds)->pluck('id');
            if ($validIds->count() !== $roleIds->count()) {
                throw ValidationException::withMessages(['role_ids' => 'یکی از نقش‌های انتخاب‌شده معتبر نیست.']);
            }
        }

        $upload = $request->file('file');
        $path = $upload->store('private-documents/'.$buildingId, 'local');
        if (! $path) {
            throw ValidationException::withMessages(['file' => 'ذخیره فایل ناموفق بود؛ دوباره تلاش کن.']);
        }

        try {
            DB::transaction(function () use ($data, $roleIds, $upload, $path, $buildingId, $request) {
                $file = StoredFile::create([
                    'building_id' => $buildingId,
                    'uploader_id' => $request->user()->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => basename($upload->getClientOriginalName()),
                    'mime' => $upload->getMimeType(),
                    'size' => $upload->getSize(),
                    'sha256' => hash_file('sha256', $upload->getRealPath()),
                    'visibility' => 'private',
                ]);

                $document = Document::create([
                    'building_id' => $buildingId,
                    'title' => trim($data['title']),
                    'category' => $data['category'],
                    'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                    'uploaded_by' => $request->user()->id,
                    'file_id' => $file->id,
                ]);

                if ($roleIds->isNotEmpty()) {
                    DB::table('document_roles')->insert($roleIds->map(fn ($roleId) => [
                        'document_id' => $document->id,
                        'role_id' => $roleId,
                    ])->all());
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return redirect()->route('documents.index')->with('status', 'سند با موفقیت به بایگانی ساختمان اضافه شد.');
    }

    public function download(int $id, CurrentBuilding $current)
    {
        $buildingId = $current->id();
        abort_unless($buildingId, 403);

        $document = Document::query()->with('file')->findOrFail($id);
        abort_unless($document->file && (int) $document->file->building_id === $buildingId, 404);

        $allowedRoles = $document->roles()->pluck('roles.key');
        if ($allowedRoles->isNotEmpty()) {
            abort_unless($allowedRoles->intersect($current->roles())->isNotEmpty(), 403);
        }

        $file = $document->file;
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    public function destroy(int $id, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id(), 403);
        abort_unless(request()->user()->hasPermission('document.manage'), 403);

        Document::query()->findOrFail($id)->delete();

        return redirect()->route('documents.index')->with('status', 'سند از فهرست فعال بایگانی خارج شد.');
    }
}
