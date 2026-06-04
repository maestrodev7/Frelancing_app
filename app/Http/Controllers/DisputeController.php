<?php

namespace App\Http\Controllers;

use App\Domain\Missions\Enums\DisputeStatus;
use App\Domain\Missions\Enums\MissionStatus;
use App\Http\Requests\ResolveDisputeRequest;
use App\Http\Requests\StoreDisputeRequest;
use App\Models\Dispute;
use App\Models\Mission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DisputeController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isStaff(), 403);

        $disputes = Dispute::query()
            ->with([
                'mission:id,title,status,user_id',
                'mission.client:id,name',
                'openedBy:id,name',
            ])
            ->latest()
            ->get()
            ->map(fn (Dispute $dispute): array => $this->disputeListPayload($dispute));

        return Inertia::render('Admin/Disputes/Index', [
            'disputes' => $disputes,
        ]);
    }

    public function show(Request $request, Dispute $dispute): Response
    {
        abort_unless($request->user()?->isStaff(), 403);

        $dispute->load([
            'mission.client',
            'mission.acceptedProposal.freelancer',
            'openedBy',
            'resolvedBy',
        ]);

        return Inertia::render('Admin/Disputes/Show', [
            'dispute' => $this->disputeDetailPayload($dispute),
        ]);
    }

    public function store(StoreDisputeRequest $request, Mission $mission): RedirectResponse
    {
        abort_unless($mission->user_id === $request->user()->id, 403);
        abort_unless($mission->isInProgress(), 403, 'Un litige ne peut être ouvert que sur une mission en cours.');
        abort_if($mission->dispute()->exists(), 422, 'Un litige est déjà ouvert pour cette mission.');

        DB::transaction(function () use ($request, $mission): void {
            Dispute::create([
                'mission_id' => $mission->id,
                'opened_by_user_id' => $request->user()->id,
                'reason' => $request->validated('reason'),
                'status' => DisputeStatus::Open,
            ]);

            $mission->update(['status' => MissionStatus::Disputed]);
        });

        return redirect()
            ->route('missions.show', $mission)
            ->with('status', 'dispute-opened');
    }

    public function resolve(ResolveDisputeRequest $request, Dispute $dispute): RedirectResponse
    {
        abort_unless($dispute->isOpen(), 403);

        $validated = $request->validated();

        DB::transaction(function () use ($request, $dispute, $validated): void {
            $dispute->update([
                'status' => DisputeStatus::Resolved,
                'resolution_notes' => $validated['resolution_notes'],
                'resolution_outcome' => $validated['resolution_outcome'],
                'resolved_by_user_id' => $request->user()->id,
                'resolved_at' => now(),
            ]);

            $dispute->mission->update([
                'status' => $validated['resolution_outcome'] === 'close_mission'
                    ? MissionStatus::Closed
                    : MissionStatus::InProgress,
            ]);
        });

        return redirect()
            ->route('admin.disputes.show', $dispute)
            ->with('status', 'dispute-resolved');
    }

    /**
     * @return array<string, mixed>
     */
    private function disputeListPayload(Dispute $dispute): array
    {
        return [
            'id' => $dispute->id,
            'status' => $dispute->status->value,
            'status_label' => $dispute->status === DisputeStatus::Open ? 'Ouvert' : 'Résolu',
            'reason' => \Illuminate\Support\Str::limit($dispute->reason, 120),
            'mission' => [
                'id' => $dispute->mission->id,
                'title' => $dispute->mission->title,
                'status' => $dispute->mission->status->value,
                'client_name' => $dispute->mission->client->name,
            ],
            'opened_by' => $dispute->openedBy->name,
            'created_at' => $dispute->created_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function disputeDetailPayload(Dispute $dispute): array
    {
        $mission = $dispute->mission;
        $freelancer = $mission->acceptedProposal?->freelancer;

        return [
            'id' => $dispute->id,
            'status' => $dispute->status->value,
            'status_label' => $dispute->status === DisputeStatus::Open ? 'Ouvert' : 'Résolu',
            'reason' => $dispute->reason,
            'resolution_notes' => $dispute->resolution_notes,
            'resolution_outcome' => $dispute->resolution_outcome,
            'resolved_at' => $dispute->resolved_at?->toDateTimeString(),
            'opened_by' => $dispute->openedBy->only(['id', 'name', 'email']),
            'resolved_by' => $dispute->resolvedBy?->only(['id', 'name']),
            'mission' => [
                'id' => $mission->id,
                'title' => $mission->title,
                'status' => $mission->status->value,
                'status_label' => $this->missionStatusLabel($mission->status),
                'client' => $mission->client->only(['id', 'name', 'email']),
                'freelancer' => $freelancer?->only(['id', 'name', 'email']),
            ],
        ];
    }

    private function missionStatusLabel(MissionStatus $status): string
    {
        return match ($status) {
            MissionStatus::Open => 'Ouverte',
            MissionStatus::InProgress => 'En cours',
            MissionStatus::Disputed => 'En litige',
            MissionStatus::Closed => 'Clôturée',
            MissionStatus::Cancelled => 'Annulée',
        };
    }
}
