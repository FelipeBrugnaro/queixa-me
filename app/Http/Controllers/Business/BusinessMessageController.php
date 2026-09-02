<?php

declare(strict_types=1);

namespace App\Http\Controllers\Business;

use App\Domain\Companies\Models\Company;
use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Services\ConversationService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BusinessMessageController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    public function index(Request $request): View
    {
        $company = $this->company($request);

        $this->seo()->title('Mensagens');

        return view('business.messages.index', [
            'company' => $company,
            'conversations' => Conversation::where('company_id', $company->id)
                ->with(['user:id,uuid,public_name,status', 'complaint:id,uuid,title,reference'])
                ->orderByDesc('last_message_at')
                ->paginate(20),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $company = $this->company($request);
        $this->authorizeCompany($conversation, $company);

        $conversation->load(['messages', 'user:id,uuid,public_name,status', 'complaint:id,uuid,title,reference,slug']);
        $this->conversations->markRead($conversation, ActorType::Company);

        $this->seo()->title($conversation->title());

        return view('business.messages.show', [
            'company' => $company,
            'conversation' => $conversation,
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $company = $this->company($request);
        $this->authorizeCompany($conversation, $company);
        abort_unless($request->user()->canForCompany($company, 'messages.send'), 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ], [], ['body' => 'mensagem']);

        try {
            $this->conversations->send(
                $conversation,
                ActorType::Company,
                $data['body'],
                user: $request->user(),
                company: $company,
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Mensagem enviada.');
    }

    private function company(Request $request): Company
    {
        return $request->attributes->get('company');
    }

    private function authorizeCompany(Conversation $conversation, Company $company): void
    {
        abort_unless($conversation->company_id === $company->id, 404);
    }
}
