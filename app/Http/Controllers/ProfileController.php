<?php

namespace App\Http\Controllers;

use App\Domain\Users\Enums\UserRole;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateFreelancerProfileRequest;
use App\Models\Country;
use App\Models\FreelancerProfile;
use App\Models\Skill;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user()->load(['freelancerProfile.skills']);

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'countries' => Country::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'freelancerProfile' => $user->freelancerProfile
                ? [
                    'title' => $user->freelancerProfile->title,
                    'bio' => $user->freelancerProfile->bio,
                    'hourly_rate_default' => $user->freelancerProfile->hourly_rate_default,
                    'currency' => $user->freelancerProfile->currency,
                    'experience_years' => $user->freelancerProfile->experience_years,
                    'availability_status' => $user->freelancerProfile->availability_status,
                    'timezone' => $user->freelancerProfile->timezone,
                    'portfolio_url' => $user->freelancerProfile->portfolio_url,
                    'linkedin_url' => $user->freelancerProfile->linkedin_url,
                    'skills' => $user->freelancerProfile->skills
                        ->map(fn ($skill): array => [
                            'name' => $skill->name,
                            'level' => $skill->pivot->level,
                        ])
                        ->values()
                        ->all(),
                ]
                : null,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the freelancer profile information.
     */
    public function updateFreelancer(UpdateFreelancerProfileRequest $request): RedirectResponse
    {
        abort_unless($request->user()->role === UserRole::Freelancer, 403);

        $validated = $request->validated();

        $profile = $request->user()->freelancerProfile()->firstOrCreate([], [
            'title' => '',
            'bio' => '',
            'currency' => 'XAF',
            'experience_years' => 0,
            'availability_status' => 'available',
            'timezone' => 'UTC',
        ]);

        $profile->fill([
            'title' => $validated['title'],
            'bio' => $validated['bio'],
            'hourly_rate_default' => $validated['hourly_rate_default'] ?? null,
            'currency' => $validated['currency'],
            'experience_years' => $validated['experience_years'],
            'availability_status' => $validated['availability_status'],
            'timezone' => $validated['timezone'],
            'portfolio_url' => $validated['portfolio_url'] ?? null,
            'linkedin_url' => $validated['linkedin_url'] ?? null,
        ])->save();

        $this->syncFreelancerSkills($profile, $validated['skills']);

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @param  array<int, array{name: string, level: int|string}>  $skills
     */
    private function syncFreelancerSkills(FreelancerProfile $profile, array $skills): void
    {
        $profile->skills()->sync(
            collect($skills)
                ->mapWithKeys(function (array $skill): array {
                    $skillModel = Skill::query()->firstOrCreate([
                        'name' => trim($skill['name']),
                    ]);

                    return [$skillModel->id => ['level' => (int) $skill['level']]];
                })
                ->all()
        );
    }
}
