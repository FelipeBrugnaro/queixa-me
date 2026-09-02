<?php

declare(strict_types=1);

namespace App\Http\Controllers\Consumer;

use App\Domain\Accounts\Enums\ConsentType;
use App\Domain\Accounts\Models\Consent;
use App\Domain\Accounts\Services\ConsentRecorder;
use App\Domain\Moderation\Models\DataRequest;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Direitos do titular dos dados.
 *
 * DECISÃO IMPORTANTE — apagamento vs. anonimização.
 *
 * O direito ao apagamento (art. 17) não é absoluto: colide aqui com a
 * liberdade de expressão e informação e com o interesse legítimo das
 * empresas e dos consumidores no arquivo público de reclamações já
 * respondidas. Apagar reclamações a pedido tornaria o portal manipulável —
 * bastaria criar conta, reclamar, e apagar o histórico incómodo.
 *
 * Por isso o pedido de eliminação de conta:
 *  - remove a ligação entre a pessoa e o conteúdo público;
 *  - expurga os dados de contacto cifrados;
 *  - substitui o nome público por "Utilizador removido";
 *  - mantém o texto da reclamação e a resposta da empresa, que passam a ser
 *    conteúdo sem titular identificável.
 *
 * O pedido é registado e tratado por uma pessoa dentro do prazo legal de
 * 30 dias, e não executado imediatamente por um clique, porque exige
 * verificar se há processos em curso.
 */
class PrivacyController extends Controller
{
    public function __construct(private readonly ConsentRecorder $consents) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $this->seo()->title('Privacidade e dados pessoais');

        return view('consumer.privacy', [
            'user' => $user,
            'consents' => Consent::where('user_id', $user->id)
                ->latest('granted_at')
                ->limit(50)
                ->get(),
            'outdated' => $this->consents->outdatedConsents($user),
            'openRequests' => DataRequest::where('user_id', $user->id)
                ->whereNull('completed_at')
                ->get(),
            'pastRequests' => DataRequest::where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    public function updateMarketing(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('marketing_opt_in');
        $user = $request->user();

        $user->forceFill(['marketing_opt_in' => $enabled])->save();

        // Revogar também gera registo: a prova tem de cobrir os dois sentidos.
        $enabled
            ? $this->consents->record(ConsentType::Marketing, $user)
            : $this->consents->revoke(ConsentType::Marketing, $user);

        return back()->with('success', $enabled
            ? 'Passas a receber as nossas comunicações.'
            : 'Deixas de receber comunicações de marketing.');
    }

    public function requestExport(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (DataRequest::where('user_id', $user->id)->where('type', 'export')->whereNull('completed_at')->exists()) {
            return back()->with('info', 'Já tens um pedido de exportação em curso.');
        }

        DataRequest::create([
            'user_id' => $user->id,
            'type' => 'export',
            'status' => 'pending',
            'due_at' => now()->addDays(30),
        ]);

        return back()->with('success', 'Pedido registado. Enviamos-te o ficheiro por email dentro de 30 dias, normalmente muito antes.');
    }

    public function requestDeletion(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm' => ['accepted'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ], [
            'confirm.accepted' => 'Confirma que compreendes as consequências antes de continuares.',
        ]);

        $user = $request->user();

        if (DataRequest::where('user_id', $user->id)->where('type', 'deletion')->whereNull('completed_at')->exists()) {
            return back()->with('info', 'Já tens um pedido de eliminação em curso.');
        }

        DataRequest::create([
            'user_id' => $user->id,
            'type' => 'deletion',
            'status' => 'pending',
            'notes' => $request->input('reason'),
            'due_at' => now()->addDays(30),
        ]);

        return back()->with(
            'success',
            'Pedido registado. Vamos confirmar que não há processos em curso e eliminar a tua conta dentro de 30 dias. As reclamações publicadas passam a aparecer sem qualquer ligação a ti.'
        );
    }
}
