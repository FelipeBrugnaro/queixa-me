<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Accounts\Actions\AnonymiseUser;
use App\Domain\Accounts\Enums\UserStatus;
use App\Domain\Accounts\Enums\UserType;
use App\Domain\Accounts\Models\User;
use App\Domain\Complaints\Models\Complaint;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->seo()->title('Utilizadores');

        $users = User::query()
            ->when($request->query('tipo'), fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($request->query('estado'), fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($request->query('q'), fn (Builder $q, string $term) => $q->where(
                fn (Builder $sub) => $sub->where('email', 'like', '%'.$term.'%')
                    ->orWhere('public_name', 'like', '%'.$term.'%')
                    ->orWhere('name', 'like', '%'.$term.'%')
            ))
            ->withCount('complaints')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'types' => UserType::options(),
            'statuses' => UserStatus::options(),
        ]);
    }

    public function show(User $user): View
    {
        $this->seo()->title($user->publicDisplayName());

        return view('admin.users.show', [
            'user' => $user->load(['companies', 'socialAccounts', 'consents' => fn ($q) => $q->latest()->limit(30)]),
            'complaints' => Complaint::where('user_id', $user->id)
                ->with('company:id,name,slug')
                ->latest()
                ->limit(25)
                ->get(),
            'stats' => [
                'total' => Complaint::where('user_id', $user->id)->count(),
                'rejected' => Complaint::where('user_id', $user->id)->where('moderation_status', 'rejected')->count(),
                'last_30_days' => Complaint::where('user_id', $user->id)->where('created_at', '>=', now()->subDays(30))->count(),
            ],
        ]);
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [], ['reason' => 'motivo']);

        abort_if($user->isAdmin(), 403, 'Não é possível bloquear administradores.');

        $user->forceFill([
            'status' => UserStatus::Blocked,
            'blocked_at' => now(),
            'blocked_reason' => $data['reason'],
        ])->save();

        return back()->with('success', 'Utilizador bloqueado.');
    }

    public function unblock(User $user): RedirectResponse
    {
        $user->forceFill([
            'status' => UserStatus::Active,
            'blocked_at' => null,
            'blocked_reason' => null,
        ])->save();

        return back()->with('success', 'Utilizador desbloqueado.');
    }

    public function anonymise(Request $request, User $user, AnonymiseUser $action): RedirectResponse
    {
        abort_if($user->isAdmin(), 403);

        $action->handle($user, $request->user());

        return back()->with(
            'success',
            'Conta anonimizada. As reclamações públicas mantêm-se, sem qualquer ligação à pessoa.'
        );
    }
}
