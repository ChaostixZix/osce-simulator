<?php

namespace App\Http\Controllers;

use App\Models\OsceCase;
use App\Models\OsceSession;
use App\Services\EvidenceReviewService;
use App\Services\LocalPatientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class GuestStationController extends Controller
{
    private function featuredCase(): OsceCase
    {
        abort_unless(config('demo.enabled'), 404);
        return OsceCase::where('title', config('demo.case_title'))->where('is_active', true)->firstOrFail();
    }

    private function authorizeGuest(Request $request, OsceSession $session): void
    {
        abort_unless(config('demo.enabled'), 404);
        $token = $request->session()->get('osce_guest_token');
        abort_unless(
            is_string($token) && $session->guest_token_hash &&
            hash_equals($session->guest_token_hash, hash('sha256', $token)),
            404
        );
    }

    private function briefingData(OsceCase $case): array
    {
        return $case->only(['id', 'title', 'description', 'scenario', 'objectives', 'duration_minutes', 'clinical_setting']);
    }

    public function briefing()
    {
        return Inertia::render('Guest/Briefing', ['station' => $this->briefingData($this->featuredCase())]);
    }

    public function start(Request $request)
    {
        $case = $this->featuredCase();
        if (!$request->session()->has('osce_guest_token')) {
            $request->session()->put('osce_guest_token', Str::random(64));
        }
        $hash = hash('sha256', $request->session()->get('osce_guest_token'));
        $session = OsceSession::where('guest_token_hash', $hash)
            ->where('osce_case_id', $case->id)->whereNull('finalized_at')->latest('id')->first();

        if (!$session) {
            $session = new OsceSession;
            $session->osce_case_id = $case->id;
            $session->guest_token_hash = $hash;
            $session->status = 'in_progress';
            $session->started_at = now();
            $session->guest_actions = [];
            $session->save();
        }

        return to_route('demo.session', $session);
    }

    public function show(Request $request, OsceSession $session)
    {
        $this->authorizeGuest($request, $session);
        if ($session->finalized_at) {
            return to_route('demo.review', $session);
        }
        $session->load('osceCase');
        return Inertia::render('Guest/Encounter', [
            'station' => $this->briefingData($session->osceCase),
            'encounter' => [
                'id' => $session->id,
                'started_at' => $session->started_at->toISOString(),
                'remaining_seconds' => $session->remaining_seconds,
                'expired' => $session->is_expired,
                'actions' => $session->guest_actions ?? [],
            ],
            'messages' => $session->chatMessages()->orderBy('id')->get(['id', 'sender_type', 'message', 'source_topic']),
            'investigations' => collect(config('demo.investigations'))->map(fn ($test, $key) => ['key' => $key, 'name' => $test['name']])->values(),
        ]);
    }

    public function message(Request $request, OsceSession $session, LocalPatientService $patient)
    {
        $this->authorizeGuest($request, $session);
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'client_message_id' => ['required', 'uuid'],
        ]);

        DB::transaction(function () use ($session, $data, $patient) {
            $locked = OsceSession::lockForUpdate()->findOrFail($session->id);
            if ($locked->chatMessages()->where('client_message_id', $data['client_message_id'])->exists()) {
                return;
            }
            if (!$locked->isActive() || $locked->finalized_at) {
                throw ValidationException::withMessages(['message' => 'The conversation has ended. Submit your summary to review this encounter.']);
            }
            $question = trim($data['message']);
            if ($question === '') {
                throw ValidationException::withMessages(['message' => 'Enter a question for the patient.']);
            }
            $reply = $patient->reply($locked->osceCase, $question);
            $locked->chatMessages()->create([
                'sender_type' => 'user', 'message' => $question, 'sent_at' => now(),
                'client_message_id' => $data['client_message_id'],
            ]);
            $locked->chatMessages()->create([
                'sender_type' => 'ai_patient', 'message' => $reply['message'],
                'source_topic' => $reply['topic'], 'sent_at' => now(),
            ]);
        });

        return to_route('demo.session', $session);
    }

    public function action(Request $request, OsceSession $session)
    {
        $this->authorizeGuest($request, $session);
        $data = $request->validate(['action' => ['required', Rule::in(array_merge(['vitals'], array_keys(config('demo.investigations'))))]]);
        DB::transaction(function () use ($session, $data) {
            $locked = OsceSession::lockForUpdate()->findOrFail($session->id);
            if (!$locked->isActive() || $locked->finalized_at) {
                throw ValidationException::withMessages(['action' => 'The encounter has ended. Your recorded findings are still available.']);
            }
            $actions = $locked->guest_actions ?? [];
            if (collect($actions)->contains('key', $data['action'])) {
                return;
            }
            $case = $locked->osceCase;
            if ($data['action'] === 'vitals') {
                $name = 'Vital signs';
                $findings = $case->ai_patient_vitals;
            } else {
                $test = config('demo.investigations.'.$data['action']);
                $name = $test['name'];
                $findings = $case->test_results_templates[$test['template']] ?? null;
            }
            if (!$findings) {
                throw ValidationException::withMessages(['action' => 'No result is recorded for this action in the case.']);
            }
            $actions[] = ['key' => $data['action'], 'name' => $name, 'findings' => $findings, 'recorded_at' => now()->toISOString()];
            $locked->guest_actions = $actions;
            $locked->save();
        });
        return to_route('demo.session', $session);
    }

    public function submit(Request $request, OsceSession $session, EvidenceReviewService $reviewer)
    {
        $this->authorizeGuest($request, $session);
        if ($session->finalized_at) {
            return to_route('demo.review', $session);
        }
        $data = $request->validate([
            'diagnosis' => ['required', 'string', 'min:10', 'max:3000'],
            'differential_diagnosis' => ['required', 'string', 'min:10', 'max:3000'],
            'plan' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        DB::transaction(function () use ($session, $data, $reviewer) {
            $locked = OsceSession::lockForUpdate()->findOrFail($session->id);
            if ($locked->finalized_at) {
                return;
            }
            $locked->fill($data);
            $locked->status = 'completed';
            $locked->completed_at = now();
            $locked->finalized_at = now();
            $locked->rationalization_completed_at = now();
            $locked->guest_review = $reviewer->create($locked);
            $locked->save();
        });

        return to_route('demo.review', $session);
    }

    public function review(Request $request, OsceSession $session)
    {
        $this->authorizeGuest($request, $session);
        if (!$session->finalized_at || !$session->guest_review) {
            return to_route('demo.session', $session);
        }
        return Inertia::render('Guest/Review', [
            'station' => $this->briefingData($session->osceCase),
            'encounterId' => $session->id,
            'review' => $session->guest_review,
            'messages' => $session->chatMessages()->orderBy('id')->get(['id', 'sender_type', 'message']),
        ]);
    }
}
