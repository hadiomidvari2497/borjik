<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BuildingTools;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuildingToolsController extends Controller
{
    public function index()
    {
        return view('admin.building-tools.index', [
            'tools' => BuildingTools::all(),
            'states' => BuildingTools::states(),
        ]);
    }

    public function update(Request $request)
    {
        $validKeys = array_keys(BuildingTools::all());
        $data = $request->validate([
            'tools' => ['sometimes', 'array'],
            'tools.*' => ['string', Rule::in($validKeys)],
        ]);

        $enabled = array_fill_keys($validKeys, false);
        foreach ($data['tools'] ?? [] as $key) {
            $enabled[$key] = true;
        }

        // مسیرهای پایه همیشه فعال می‌مانند و در این صفحه قابل خاموش‌کردن نیستند.
        BuildingTools::save($enabled);

        return redirect()->route('admin.building-tools.index')
            ->with('success', 'وضعیت ابزارهای مدیریت ساختمان ذخیره شد.');
    }
}
