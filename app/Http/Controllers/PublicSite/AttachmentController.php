<?php

declare(strict_types=1);

namespace App\Http\Controllers\PublicSite;

use App\Domain\Complaints\Models\ComplaintAttachment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Servidor autorizado de anexos.
 *
 * Os ficheiros vivem num disco privado, fora do document root. Guardar
 * faturas e comprovativos em storage/app/public seria expor documentos com
 * dados pessoais a quem adivinhasse o URL — e os motores de busca acabariam
 * por os indexar.
 */
class AttachmentController extends Controller
{
    public function __invoke(Request $request, string $uuid): StreamedResponse
    {
        $attachment = ComplaintAttachment::where('uuid', $uuid)
            ->with('complaint.company')
            ->firstOrFail();

        $this->authorizeAccess($request, $attachment);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
                // Nunca renderizar HTML/SVG carregado por utilizadores no
                // nosso domínio: forçar transferência elimina XSS via anexo.
                'Content-Disposition' => $attachment->isImage() || $attachment->mime_type === 'application/pdf'
                    ? 'inline; filename="'.addslashes($attachment->original_name).'"'
                    : 'attachment; filename="'.addslashes($attachment->original_name).'"',
                'X-Content-Type-Options' => 'nosniff',
                'X-Robots-Tag' => 'noindex, nofollow',
                'Cache-Control' => 'private, max-age=3600',
            ],
        );
    }

    private function authorizeAccess(Request $request, ComplaintAttachment $attachment): void
    {
        $user = $request->user();
        $complaint = $attachment->complaint;

        abort_if($user === null || $complaint === null, 403);

        // Autor da reclamação.
        if ($user->id === $complaint->user_id) {
            return;
        }

        // Moderação e administração.
        if ($user->isModerator()) {
            return;
        }

        // Gestores da empresa visada, e apenas se o autor consentiu a
        // transmissão dos elementos do processo.
        if ($complaint->company
            && $complaint->share_contact_with_company
            && $user->canForCompany($complaint->company, 'complaints.view')) {
            return;
        }

        abort(403, 'Não tens permissão para aceder a este ficheiro.');
    }
}
