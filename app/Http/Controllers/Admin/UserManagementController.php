<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Users\Enums\AccountStatus;
use App\Domain\Users\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isStaff(), 403);

        $users = User::query()
            ->with('country:id,name,code')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'role_label' => $this->roleLabel($user->role),
                'status' => $user->status->value,
                'status_label' => $this->statusLabel($user->status),
                'country' => $user->country?->name,
                'created_at' => $user->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 403, 'Vous ne pouvez pas modifier votre propre compte ici.');
        abort_if($user->isStaff() && $request->user()->isSecretary() && $user->isAdmin(), 403);

        $user->update(['status' => $request->validated('status')]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'user-status-updated');
    }

    private function roleLabel(UserRole $role): string
    {
        return match ($role) {
            UserRole::Client => 'Porteur de projet',
            UserRole::Freelancer => 'Freelance',
            UserRole::Admin => 'Administrateur',
            UserRole::Secretary => 'Secrétaire',
        };
    }

    private function statusLabel(AccountStatus $status): string
    {
        return match ($status) {
            AccountStatus::Active => 'Actif',
            AccountStatus::Suspended => 'Désactivé',
            AccountStatus::Pending => 'En attente',
        };
    }
}
