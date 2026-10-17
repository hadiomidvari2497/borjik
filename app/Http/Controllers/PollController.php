<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PollController extends Controller
{
    public function index(Request $request, CurrentBuilding $current): View
    {
        abort_unless($current->id(), 403);

        $polls = Poll::query()
            ->with(['options' => fn ($query) => $query->withCount('votes'), 'votes' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->withCount('votes')
            ->orderByRaw("CASE WHEN status = 'open' AND ends_at > ? THEN 0 ELSE 1 END", [now()])
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('polls.index', [
            'polls' => $polls,
            'canManage' => $request->user()->hasPermission('poll.manage'),
            'canVote' => $request->user()->hasPermission('poll.vote'),
            'currentRoles' => $current->roles(),
        ]);
    }

    public function store(Request $request, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id(), 403);

        foreach (['starts', 'ends'] as $prefix) {
            if ($request->exists($prefix.'_date') || $request->exists($prefix.'_time')) {
                $date = JalaliDate::parse($request->input($prefix.'_date'));
                $time = (string) $request->input($prefix.'_time', '');
                if (! $date || ! preg_match('/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', $time)) {
                    throw ValidationException::withMessages([
                        $prefix.'_at' => $prefix === 'starts' ? 'تاریخ و ساعت شروع را به‌درستی انتخاب کنید.' : 'تاریخ و ساعت پایان را به‌درستی انتخاب کنید.',
                    ]);
                }

                $request->merge([$prefix.'_at' => $date->setTimeFromTimeString($time)->format('Y-m-d H:i:s')]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'response_type' => ['nullable', 'in:choice,text'],
            'audience' => ['required', 'in:all,residents'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_anonymous' => ['nullable', 'boolean'],
            'options' => ['nullable', 'array', 'min:2', 'max:8'],
            'options.*' => ['nullable', 'string', 'max:200'],
        ]);

        $data['response_type'] = $data['response_type'] ?? 'choice';
        $options = collect($data['options'] ?? [])->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
        if ($data['response_type'] === 'choice' && $options->count() < 2) {
            throw ValidationException::withMessages(['options' => 'حداقل دو گزینه‌ی متفاوت وارد کنید.']);
        }

        if ($data['response_type'] === 'text') {
            $options = collect();
        }

        $start = now()->parse($data['starts_at']);
        $end = now()->parse($data['ends_at']);
        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['ends_at' => 'زمان پایان باید بعد از زمان شروع باشد.']);
        }

        $poll = DB::transaction(function () use ($data, $options, $request, $start, $end): Poll {
            $poll = Poll::create([
                'title' => trim($data['title']),
                'response_type' => $data['response_type'],
                'description' => trim((string) ($data['description'] ?? '')) ?: null,
                'audience' => $data['audience'],
                'starts_at' => $start,
                'ends_at' => $end,
                'is_anonymous' => $request->boolean('is_anonymous'),
                'status' => 'open',
                'created_by' => $request->user()->id,
            ]);

            foreach ($options as $text) {
                $poll->options()->create(['text' => $text]);
            }

            return $poll;
        });

        return redirect()->route('polls.index')->with('status', 'نظرسنجی با موفقیت ایجاد شد.');
    }

    public function vote(Request $request, int $poll, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id(), 403);

        try {
            DB::transaction(function () use ($request, $poll, $current): void {
                $item = Poll::query()->whereKey($poll)->lockForUpdate()->firstOrFail();

                if ($item->status !== 'open' || now()->lt($item->starts_at) || now()->gte($item->ends_at)) {
                    throw ValidationException::withMessages(['general' => 'این نظرسنجی در حال حاضر رأی‌گیری نمی‌کند.']);
                }

                if ($item->audience === 'residents' && ! $current->hasRole('tenant', 'unit_owner')) {
                    abort(403);
                }

                $voteData = $request->validate($item->response_type === 'text'
                    ? ['answer' => ['required', 'string', 'max:5000']]
                    : ['option_id' => ['required', 'integer']]);

                $option = null;
                if ($item->response_type === 'choice') {
                    $option = PollOption::query()
                        ->where('poll_id', $item->id)
                        ->whereKey($voteData['option_id'])
                        ->firstOrFail();
                }

                if (PollVote::where('poll_id', $item->id)->where('user_id', $request->user()->id)->exists()) {
                    throw ValidationException::withMessages(['general' => 'پاسخ شما قبلاً ثبت شده است.']);
                }

                PollVote::create([
                    'poll_id' => $item->id,
                    'option_id' => $option?->id,
                    'answer' => $voteData['answer'] ?? null,
                    'user_id' => $request->user()->id,
                    'unit_id' => null,
                ]);
            });
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'poll_votes_poll_id_user_id_unique')) {
                return back()->withErrors(['general' => 'رأی شما قبلاً ثبت شده است.']);
            }

            throw $exception;
        }

        return redirect()->route('polls.index')->with('status', 'رأی شما با موفقیت ثبت شد.');
    }


    public function show(Request $request, int $poll, CurrentBuilding $current): View
    {
        abort_unless($current->id() && $request->user()->hasPermission('poll.manage'), 403);

        $item = Poll::query()
            ->with(['createdBy', 'options' => fn ($query) => $query->withCount('votes'), 'votes.user', 'votes.option'])
            ->withCount('votes')
            ->findOrFail($poll);

        return view('polls.show', ['poll' => $item]);
    }

    public function close(int $poll): RedirectResponse
    {
        $item = Poll::query()->whereKey($poll)->firstOrFail();
        $item->update(['status' => 'closed']);

        return redirect()->route('polls.index')->with('status', 'رأی‌گیری بسته شد.');
    }
}
