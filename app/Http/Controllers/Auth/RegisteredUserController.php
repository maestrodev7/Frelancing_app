<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\UseCases\RegisterClientUseCase;
use App\Application\Auth\UseCases\RegisterFreelancerUseCase;
use App\Domain\Users\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegistrationRequest;
use App\Models\Country;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'countries' => Country::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(
        StoreRegistrationRequest $request,
        RegisterClientUseCase $registerClient,
        RegisterFreelancerUseCase $registerFreelancer,
    ): RedirectResponse|JsonResponse {
        $user = match ($request->accountType()) {
            UserRole::Freelancer => $registerFreelancer->execute($request->toFreelancerData()),
            default => $registerClient->execute($request->toClientData()),
        };

        $user->load(['clientProfile', 'freelancerProfile.skills', 'country']);

        event(new Registered($user));

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Account created successfully.',
                'user' => $this->userPayload($user),
            ], 201);
        }

        return redirect(route('dashboard', absolute: false));
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'country' => [
                'id' => $user->country->id,
                'name' => $user->country->name,
                'code' => $user->country->code,
            ],
            'client_profile' => $user->clientProfile
                ? [
                    'billing_address' => $user->clientProfile->billing_address,
                    'timezone' => $user->clientProfile->timezone,
                    'total_spent' => $user->clientProfile->total_spent,
                    'jobs_posted_count' => $user->clientProfile->jobs_posted_count,
                ]
                : null,
            'freelancer_profile' => $user->freelancerProfile
                ? [
                    'title' => $user->freelancerProfile->title,
                    'bio' => $user->freelancerProfile->bio,
                    'hourly_rate_default' => $user->freelancerProfile->hourly_rate_default,
                    'currency' => $user->freelancerProfile->currency,
                    'experience_years' => $user->freelancerProfile->experience_years,
                    'average_rating' => $user->freelancerProfile->average_rating,
                    'completed_jobs_count' => $user->freelancerProfile->completed_jobs_count,
                    'availability_status' => $user->freelancerProfile->availability_status,
                    'timezone' => $user->freelancerProfile->timezone,
                    'portfolio_url' => $user->freelancerProfile->portfolio_url,
                    'linkedin_url' => $user->freelancerProfile->linkedin_url,
                    'skills' => $user->freelancerProfile->skills
                        ->map(fn ($skill): array => [
                            'id' => $skill->id,
                            'name' => $skill->name,
                            'level' => $skill->pivot->level,
                        ])
                        ->values(),
                ]
                : null,
        ];
    }
}
