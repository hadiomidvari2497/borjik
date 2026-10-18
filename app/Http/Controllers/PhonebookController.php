<?php

namespace App\Http\Controllers;

use App\Models\PhonebookEntry;
use App\Support\CurrentBuilding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PhonebookController extends Controller
{
    private const CATEGORIES = [
        'emergency' => 'اورژانس و امداد',
        'fire' => 'آتش‌نشانی',
        'management' => 'مدیریت ساختمان',
        'security' => 'حراست و نگهبانی',
        'resident' => 'اعضای ساختمان',
        'service' => 'خدمات و تعمیرات',
        'other' => 'سایر شماره‌ها',
    ];

    public function index(Request $request, CurrentBuilding $current): View
    {
        abort_unless($current->id(), 403);
        $user = $request->user();
        $canBuilding = $user->hasPermission('phonebook.building');
        $canPersonal = $user->hasPermission('phonebook.personal');
        abort_unless($canBuilding || $canPersonal, 403);
        $query = trim((string) $request->query('q', ''));
        $buildingEntries = collect();
        $buildingPeople = collect();

        if ($canBuilding) {
            $buildingEntries = PhonebookEntry::query()->whereNull('user_id')
                ->when($query !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%'.$query.'%')->orWhere('phone', 'like', '%'.$query.'%')->orWhere('category', 'like', '%'.$query.'%')))
                ->orderBy('category')->orderBy('name')->get();

            $buildingPeople = DB::table('building_user_roles as bur')
                ->join('roles as r', 'r.id', '=', 'bur.role_id')
                ->join('users as u', 'u.id', '=', 'bur.user_id')
                ->where('bur.building_id', $current->id())->where('bur.status', 'active')
                ->whereNull('u.deleted_at')->where('u.mobile', '!=', '')
                ->when($query !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('u.first_name', 'like', '%'.$query.'%')->orWhere('u.last_name', 'like', '%'.$query.'%')->orWhere('u.company_name', 'like', '%'.$query.'%')->orWhere('u.mobile', 'like', '%'.$query.'%')))
                ->orderBy('u.first_name')->orderBy('u.last_name')
                ->get(['bur.user_id', 'r.key as role_key', 'r.name_fa as role_name', 'u.first_name', 'u.last_name', 'u.company_name', 'u.person_type', 'u.mobile'])
                ->groupBy('user_id')->map(function ($rows) {
                    $person = $rows->first();
                    return [
                        'id' => (int) $person->user_id,
                        'name' => $person->person_type === 'legal' ? (string) $person->company_name : trim($person->first_name.' '.$person->last_name),
                        'phone' => $person->mobile,
                        'roles' => $rows->pluck('role_name')->unique()->values()->all(),
                    ];
                })->values();
        }

        $personalEntries = $canPersonal
            ? PhonebookEntry::query()->where('user_id', $user->id)
                ->when($query !== '', fn ($q) => $q->where(fn ($sub) => $sub->where('name', 'like', '%'.$query.'%')->orWhere('phone', 'like', '%'.$query.'%')->orWhere('category', 'like', '%'.$query.'%')))
                ->orderBy('name')->paginate(12)->withQueryString()
            : PhonebookEntry::query()->whereRaw('1 = 0')->paginate(12);

        return view('phonebook.index', [
            'canBuilding' => $canBuilding,
            'canPersonal' => $canPersonal,
            'buildingEntries' => $buildingEntries,
            'buildingPeople' => $buildingPeople,
            'personalEntries' => $personalEntries,
            'categories' => self::CATEGORIES,
            'activeTab' => $canBuilding && $request->query('tab') !== 'personal' ? 'building' : 'personal',
            'search' => $query,
            'buildingCount' => $canBuilding ? $buildingEntries->count() + $buildingPeople->count() : 0,
        ]);
    }

    public function store(Request $request, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id(), 403);
        $scope = (string) $request->input('scope', 'personal');
        $this->authorizeScope($request, $scope);
        $data = $request->validate([
            'scope' => ['required', 'in:building,personal'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9۰-۹٠-٩+()\-\s.]{5,20}$/u'],
            'category' => ['required', 'in:emergency,fire,management,security,resident,service,other'],
        ], [
            'name.required' => 'نام مخاطب را وارد کنید.',
            'phone.required' => 'شماره تماس را وارد کنید.',
            'phone.regex' => 'شماره تماس معتبر نیست.',
            'category.required' => 'دسته‌بندی را انتخاب کنید.',
            'category.in' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
        ]);
        PhonebookEntry::create([
            'user_id' => $scope === 'personal' ? $request->user()->id : null,
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'category' => $data['category'],
        ]);
        return redirect()->route('phonebook.index', ['tab' => $scope])->with('status', 'مخاطب با موفقیت ثبت شد.');
    }

    public function update(Request $request, int $entry, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id(), 403);
        $contact = PhonebookEntry::query()->findOrFail($entry);
        $scope = $contact->user_id === null ? 'building' : 'personal';
        $this->authorizeEntry($request, $contact, $scope);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9۰-۹٠-٩+()\-\s.]{5,20}$/u'],
            'category' => ['required', 'in:emergency,fire,management,security,resident,service,other'],
        ], [
            'name.required' => 'نام مخاطب را وارد کنید.',
            'phone.required' => 'شماره تماس را وارد کنید.',
            'phone.regex' => 'شماره تماس معتبر نیست.',
            'category.required' => 'دسته‌بندی را انتخاب کنید.',
            'category.in' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
        ]);
        $contact->update(['name' => trim($data['name']), 'phone' => trim($data['phone']), 'category' => $data['category']]);
        return redirect()->route('phonebook.index', ['tab' => $scope])->with('status', 'اطلاعات مخاطب ویرایش شد.');
    }

    public function destroy(Request $request, int $entry, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id(), 403);
        $contact = PhonebookEntry::query()->findOrFail($entry);
        $scope = $contact->user_id === null ? 'building' : 'personal';
        $this->authorizeEntry($request, $contact, $scope);
        $contact->delete();
        return redirect()->route('phonebook.index', ['tab' => $scope])->with('status', 'مخاطب حذف شد.');
    }

    private function authorizeScope(Request $request, string $scope): void
    {
        abort_unless(in_array($scope, ['building', 'personal'], true), 422);
        abort_unless($request->user()->hasPermission($scope === 'building' ? 'phonebook.building' : 'phonebook.personal'), 403);
    }

    private function authorizeEntry(Request $request, PhonebookEntry $contact, string $scope): void
    {
        $this->authorizeScope($request, $scope);
        if ($scope === 'personal') {
            abort_unless((int) $contact->user_id === (int) $request->user()->id, 403);
        } else {
            abort_unless($contact->user_id === null, 403);
        }
    }
}
