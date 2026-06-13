<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Contact\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isStaff(), 403);

        $messages = ContactMessage::query()
            ->with('readBy:id,name')
            ->latest()
            ->get()
            ->map(fn (ContactMessage $message): array => [
                'id' => $message->id,
                'name' => $message->name,
                'email' => $message->email,
                'phone' => $message->phone,
                'subject' => $message->subject,
                'message' => $message->message,
                'status' => $message->status->value,
                'status_label' => $message->isNew() ? 'Nouveau' : 'Lu',
                'read_at' => $message->read_at?->toDateTimeString(),
                'read_by' => $message->readBy?->name,
                'created_at' => $message->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Contacts/Index', [
            'messages' => $messages,
        ]);
    }

    public function markAsRead(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        abort_unless($request->user()?->isStaff(), 403);

        if ($contactMessage->isNew()) {
            $contactMessage->update([
                'status' => ContactMessageStatus::Read,
                'read_at' => now(),
                'read_by_user_id' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('admin.contacts.index')
            ->with('status', 'contact-read');
    }
}
