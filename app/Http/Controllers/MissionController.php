<?php

namespace App\Http\Controllers;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\MissionType;
use App\Domain\Missions\Enums\ProposalStatus;
use App\Http\Requests\StoreMissionRequest;
use App\Http\Requests\StoreProposalRequest;
use App\Models\Mission;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MissionController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->isClient()) {
            $missions = Mission::query()
                ->where('user_id', $user->id)
                ->withCount([
                    'proposals',
                    'proposals as pending_proposals_count' => fn ($query) => $query->where('status', ProposalStatus::Pending),
                ])
                ->latest()
                ->get()
                ->map(fn (Mission $mission): array => $this->missionPayload($mission));

            return Inertia::render('Missions/ClientIndex', [
                'missions' => $missions,
            ]);
        }

        $missions = Mission::query()
            ->where('status', MissionStatus::Open)
            ->where('moderation_status', 'approved')
            ->with(['client:id,name'])
            ->withCount('proposals')
            ->latest()
            ->get()
            ->map(fn (Mission $mission): array => $this->missionPayload($mission, includeClient: true));

        return Inertia::render('Missions/FreelancerIndex', [
            'missions' => $missions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Missions/Create', [
            'missionTypes' => collect(MissionType::cases())->map(fn (MissionType $type): array => [
                'value' => $type->value,
                'label' => $type === MissionType::Fixed ? 'Forfait' : 'Horaire',
            ]),
            'currencies' => ['XAF', 'EUR', 'USD', 'GBP'],
        ]);
    }

    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $mission = DB::transaction(function () use ($request, $validated): Mission {
            $mission = Mission::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'type' => $validated['type'],
                'budget_min' => $validated['budget_min'] ?? null,
                'budget_max' => $validated['budget_max'] ?? null,
                'hourly_cap' => $validated['hourly_cap'] ?? null,
                'currency' => $validated['currency'],
                'status' => MissionStatus::Open,
                'start_expected_at' => $validated['start_expected_at'] ?? null,
                'deadline_at' => $validated['deadline_at'] ?? null,
                'moderation_status' => 'approved',
            ]);

            $request->user()->clientProfile?->increment('jobs_posted_count');

            return $mission;
        });

        return redirect()->route('missions.show', $mission);
    }

    public function show(Request $request, Mission $mission): Response
    {
        $user = $request->user();
        $mission->load(['client:id,name,email']);

        $isOwner = $user->id === $mission->user_id;

        if ($user->isFreelancer() && ! $mission->isOpen() && ! $isOwner) {
            abort(404);
        }

        if ($user->isFreelancer() && $mission->user_id === $user->id) {
            abort(403);
        }

        $proposals = [];
        $myProposal = null;

        if ($isOwner) {
            $proposals = $mission->proposals()
                ->with('freelancer:id,name,email')
                ->latest('submitted_at')
                ->get()
                ->map(fn (Proposal $proposal): array => $this->proposalPayload($proposal));
        } elseif ($user->isFreelancer()) {
            $myProposal = $mission->proposals()
                ->where('user_id', $user->id)
                ->first();

            if ($myProposal !== null) {
                $myProposal = $this->proposalPayload($myProposal);
            }
        }

        return Inertia::render('Missions/Show', [
            'mission' => $this->missionPayload($mission, includeClient: true, detailed: true),
            'proposals' => $proposals,
            'myProposal' => $myProposal,
            'canApply' => $user->isFreelancer()
                && $mission->isOpen()
                && $myProposal === null,
            'isOwner' => $isOwner,
            'pricingTypes' => [
                ['value' => 'fixed', 'label' => 'Forfait'],
                ['value' => 'hourly', 'label' => 'Horaire'],
            ],
        ]);
    }

    public function storeProposal(StoreProposalRequest $request, Mission $mission): RedirectResponse
    {
        abort_unless($mission->isOpen(), 403, 'Cette mission n\'accepte plus de candidatures.');
        abort_if($mission->user_id === $request->user()->id, 403);

        $validated = $request->validated();

        $exists = $mission->proposals()
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_if($exists, 422, 'Vous avez déjà postulé à cette mission.');

        Proposal::create([
            'mission_id' => $mission->id,
            'user_id' => $request->user()->id,
            'cover_letter' => $validated['cover_letter'],
            'pricing_type' => $validated['pricing_type'],
            'amount_fixed' => $validated['amount_fixed'] ?? null,
            'hourly_rate' => $validated['hourly_rate'] ?? null,
            'estimated_hours' => $validated['estimated_hours'] ?? null,
            'delivery_days' => $validated['delivery_days'],
            'status' => ProposalStatus::Pending,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('missions.show', $mission)
            ->with('status', 'proposal-sent');
    }

    /**
     * @return array<string, mixed>
     */
    private function missionPayload(
        Mission $mission,
        bool $includeClient = false,
        bool $detailed = false,
    ): array {
        $payload = [
            'id' => $mission->id,
            'title' => $mission->title,
            'description' => $detailed ? $mission->description : \Illuminate\Support\Str::limit($mission->description, 160),
            'type' => $mission->type->value,
            'type_label' => $mission->type === MissionType::Fixed ? 'Forfait' : 'Horaire',
            'budget_min' => $mission->budget_min,
            'budget_max' => $mission->budget_max,
            'hourly_cap' => $mission->hourly_cap,
            'currency' => $mission->currency,
            'status' => $mission->status->value,
            'status_label' => $this->missionStatusLabel($mission->status),
            'start_expected_at' => $mission->start_expected_at?->toDateString(),
            'deadline_at' => $mission->deadline_at?->toDateString(),
            'proposals_count' => $mission->proposals_count ?? null,
            'pending_proposals_count' => $mission->pending_proposals_count ?? null,
            'created_at' => $mission->created_at?->toDateTimeString(),
        ];

        if ($includeClient && $mission->relationLoaded('client')) {
            $payload['client'] = [
                'id' => $mission->client->id,
                'name' => $mission->client->name,
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function proposalPayload(Proposal $proposal): array
    {
        $freelancer = $proposal->relationLoaded('freelancer') ? $proposal->freelancer : null;

        return [
            'id' => $proposal->id,
            'cover_letter' => $proposal->cover_letter,
            'pricing_type' => $proposal->pricing_type->value,
            'pricing_type_label' => $proposal->pricing_type->value === 'fixed' ? 'Forfait' : 'Horaire',
            'amount_fixed' => $proposal->amount_fixed,
            'hourly_rate' => $proposal->hourly_rate,
            'estimated_hours' => $proposal->estimated_hours,
            'delivery_days' => $proposal->delivery_days,
            'status' => $proposal->status->value,
            'status_label' => $this->proposalStatusLabel($proposal->status),
            'submitted_at' => $proposal->submitted_at?->toDateTimeString(),
            'freelancer' => $freelancer ? [
                'id' => $freelancer->id,
                'name' => $freelancer->name,
                'email' => $freelancer->email,
            ] : null,
        ];
    }

    private function missionStatusLabel(MissionStatus $status): string
    {
        return match ($status) {
            MissionStatus::Open => 'Ouverte',
            MissionStatus::InProgress => 'En cours',
            MissionStatus::Closed => 'Clôturée',
            MissionStatus::Cancelled => 'Annulée',
        };
    }

    private function proposalStatusLabel(ProposalStatus $status): string
    {
        return match ($status) {
            ProposalStatus::Pending => 'En attente',
            ProposalStatus::Accepted => 'Acceptée',
            ProposalStatus::Rejected => 'Refusée',
            ProposalStatus::Withdrawn => 'Retirée',
        };
    }
}
