<?php

namespace App\Http\Controllers\Auth;

use App\Application\Auth\UseCases\RegisterClientUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreClientRegistrationRequest;
use App\Models\Country;
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
        StoreClientRegistrationRequest $request,
        RegisterClientUseCase $registerClient,
    ): RedirectResponse|JsonResponse {
        $user = $registerClient->execute($request->toData());
        $user->load(['clientProfile', 'country']);

        event(new Registered($user));

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Client account created successfully.',
                'user' => [
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
                    'client_profile' => [
                        'billing_address' => $user->clientProfile->billing_address,
                        'timezone' => $user->clientProfile->timezone,
                        'total_spent' => $user->clientProfile->total_spent,
                        'jobs_posted_count' => $user->clientProfile->jobs_posted_count,
                    ],
                ],
            ], 201);
        }

        return redirect(route('dashboard', absolute: false));
    }
}
