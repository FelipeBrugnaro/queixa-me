<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Domain\Complaints\Enums\ActorType;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Services\ConversationService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MessageController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    public function index(Request $request): View
    {
        $this->seo()->title('Mensagens');

        $conversations = Conversation::where('user_id', $request->user()->id)
            ->with(['company:id,name,slug,logo_path,status', 'complaint:id,uuid,title,reference'])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('consumer.messages.index', [
            'conversations' => $conversations,
            'notifications' => $request->user()->notifications()->latest()->limit(20)->get(),
            'unreadNotifications' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorizeParticipant($request, $conversation);

        $conversation->load(['messages', 'company:id,name,slug,logo_path,status', 'complaint:id,uuid,title,reference,slug,moderation_status']);
        $this->conversations->markRead($conversation, ActorType::Consumer);

        $this->seo()->title($conversation->title());

        return view('consumer.messages.show', ['conversation' => $conversation]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ], [], ['body' => 'mensagem']);

        try {
            $this->conversations->send(
                $conversation,
                ActorType::Consumer,
                $data['body'],
                user: $request->user(),
            );
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Mensagem enviada.');
    }

    public function toggleRead(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($request, $conversation);

        if ($request->boolean('unread')) {
            $this->conversations->markUnread($conversation, ActorType::Consumer);
        } else {
            $this->conversations->markRead($conversation, ActorType::Consumer);
        }

        return back();
    }

    /**
     * O consumidor pode encerrar o canal privado.
     * A empresa continua a poder responder publicamente na reclamação — o que
     * se fecha é a possibilidade de contacto direto e insistente.
     */
    public function close(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorizeParticipant($request, $conversation);

        $this->conversations->closeByUser($conversation);

        return back()->with('success', 'Conversa encerrada. A empresa deixa de te poder enviar mensagens privadas.');
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->user_id === $request->user()?->id, 403);
    }
}
