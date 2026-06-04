<?php

namespace App\Http\Controllers;

use App\Domain\Missions\Enums\MissionStatus;
use App\Domain\Missions\Enums\ProposalStatus;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProposalController extends Controller
{
    public function accept(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeClientForProposal($request, $proposal);

        abort_unless($proposal->isPending(), 403, 'Cette proposition ne peut plus être acceptée.');
        abort_unless($proposal->mission->isOpen(), 403, 'La mission n\'est plus ouverte.');

        DB::transaction(function () use ($proposal): void {
            $proposal->update(['status' => ProposalStatus::Accepted]);

            $proposal->mission->update([
                'status' => MissionStatus::InProgress,
                'accepted_proposal_id' => $proposal->id,
            ]);

            $proposal->mission->proposals()
                ->where('id', '!=', $proposal->id)
                ->where('status', ProposalStatus::Pending)
                ->update(['status' => ProposalStatus::Rejected]);
        });

        return redirect()
            ->route('missions.show', $proposal->mission_id)
            ->with('status', 'proposal-accepted');
    }

    public function reject(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeClientForProposal($request, $proposal);

        abort_unless($proposal->isPending(), 403, 'Cette proposition ne peut plus être refusée.');

        $proposal->update(['status' => ProposalStatus::Rejected]);

        return redirect()
            ->route('missions.show', $proposal->mission_id)
            ->with('status', 'proposal-rejected');
    }

    private function authorizeClientForProposal(Request $request, Proposal $proposal): void
    {
        abort_unless($request->user()?->isClient(), 403);

        $proposal->loadMissing('mission');

        abort_unless($proposal->mission->user_id === $request->user()->id, 403);
    }
}
