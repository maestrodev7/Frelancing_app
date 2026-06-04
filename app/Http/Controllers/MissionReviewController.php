<?php

namespace App\Http\Controllers;

use App\Domain\Missions\Enums\MissionStatus;
use App\Http\Requests\StoreMissionReviewRequest;
use App\Models\Mission;
use App\Models\MissionReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MissionReviewController extends Controller
{
    public function store(StoreMissionReviewRequest $request, Mission $mission): RedirectResponse
    {
        abort_unless($mission->isClosed(), 403, 'Les avis sont possibles uniquement sur une mission clôturée.');

        $user = $request->user();
        $freelancerId = $mission->freelancerUserId();

        abort_if($freelancerId === null, 403);

        $revieweeId = null;

        if ($user->id === $mission->user_id) {
            $revieweeId = $freelancerId;
        } elseif ($user->id === $freelancerId) {
            $revieweeId = $mission->user_id;
        } else {
            abort(403);
        }

        $exists = MissionReview::query()
            ->where('mission_id', $mission->id)
            ->where('reviewer_id', $user->id)
            ->exists();

        abort_if($exists, 422, 'Vous avez déjà laissé un avis pour cette mission.');

        MissionReview::create([
            'mission_id' => $mission->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating' => $request->validated('rating'),
            'comment' => $request->validated('comment'),
        ]);

        return redirect()
            ->route('missions.show', $mission)
            ->with('status', 'review-submitted');
    }
}
