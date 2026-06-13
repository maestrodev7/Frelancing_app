<?php

namespace App\Http\Controllers;

use App\Domain\Contact\Enums\ContactMessageStatus;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Landing/Contact');
    }

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        ContactMessage::create([
            ...$request->validated(),
            'status' => ContactMessageStatus::New,
        ]);

        return redirect()
            ->route('contact')
            ->with('status', 'contact-sent');
    }
}
