<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    public function accept(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeClientForProposal($request, $proposal);

        abort_unless($proposal->isPending(), 403, 'Cette proposition ne peut plus être acceptée.');
        abort_unless(
            $proposal->mission->isOpen() || $proposal->mission->isAwaitingPayment(),
            403,
            'La mission n\'accepte plus de paiement.',
        );

        return redirect()->route('proposals.payment.checkout', $proposal);
    }

    public function reject(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorizeClientForProposal($request, $proposal);

        abort_unless($proposal->isPending(), 403, 'Cette proposition ne peut plus être refusée.');

        $proposal->update(['status' => \App\Domain\Missions\Enums\ProposalStatus::Rejected]);

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
