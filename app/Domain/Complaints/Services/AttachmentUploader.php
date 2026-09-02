<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Services;

use App\Domain\Accounts\Models\User;
use App\Domain\Complaints\Models\Complaint;
use App\Domain\Complaints\Models\ComplaintAttachment;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Receção de anexos.
 *
 * O upload de ficheiros é o vetor de ataque mais comum num portal onde
 * qualquer pessoa pode publicar. As defesas aqui são cumulativas:
 *
 *  1. Tipo verificado pelo conteúdo real do ficheiro, não pela extensão nem
 *     pelo Content-Type enviado pelo cliente (ambos são forjáveis).
 *  2. Nome de ficheiro gerado por nós; o nome original é apenas um rótulo
 *     guardado na base de dados.
 *  3. Disco privado, servido por rota autorizada com Content-Disposition
 *     controlado.
 *  4. Imagens são reprocessadas, o que remove metadados EXIF — incluindo
 *     coordenadas GPS que revelariam a morada de quem reclama.
 */
class AttachmentUploader
{
    public function store(Complaint $complaint, UploadedFile $file, User $uploader): ComplaintAttachment
    {
        $this->assertLimits($complaint, $file);

        $mime = $this->detectMime($file);
        $this->assertAllowedMime($mime);

        $extension = $this->extensionFor($mime);
        $path = sprintf('attachments/%s/%s.%s', $complaint->uuid, bin2hex(random_bytes(16)), $extension);

        $stream = fopen($file->getRealPath(), 'rb');

        try {
            \Illuminate\Support\Facades\Storage::disk('private')->put($path, $stream, ['visibility' => 'private']);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        $attachment = $complaint->attachments()->create([
            'uploaded_by_user_id' => $uploader->id,
            'disk' => 'private',
            'path' => $path,
            'original_name' => $this->sanitiseName($file->getClientOriginalName()),
            'mime_type' => $mime,
            'size_bytes' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()),
            'is_public' => false,
        ]);

        if (str_starts_with($mime, 'image/')) {
            $this->stripImageMetadata($path, $mime);
        }

        return $attachment;
    }

    public function delete(ComplaintAttachment $attachment): void
    {
        \Illuminate\Support\Facades\Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();
    }

    private function assertLimits(Complaint $complaint, UploadedFile $file): void
    {
        $config = (array) config('queixame.complaints.attachments');

        $current = $complaint->attachments()->count();

        if ($current >= (int) $config['max_files']) {
            throw new RuntimeException('Atingiste o número máximo de '.$config['max_files'].' anexos.');
        }

        if ($file->getSize() > (int) $config['max_size_kb'] * 1024) {
            throw new RuntimeException('O ficheiro "'.$file->getClientOriginalName().'" excede o tamanho máximo permitido.');
        }

        $totalBytes = (int) $complaint->attachments()->sum('size_bytes') + $file->getSize();

        if ($totalBytes > (int) $config['max_total_size_kb'] * 1024) {
            throw new RuntimeException('O conjunto de anexos excede o limite total permitido.');
        }
    }

    /** Deteta o tipo real lendo os primeiros bytes do ficheiro. */
    private function detectMime(UploadedFile $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = $finfo->file($file->getRealPath());

        if ($detected === false) {
            throw new RuntimeException('Não foi possível validar o tipo do ficheiro.');
        }

        return $detected;
    }

    private function assertAllowedMime(string $mime): void
    {
        $allowed = (array) config('queixame.complaints.attachments.allowed_mimes');

        if (! in_array($mime, $allowed, true)) {
            throw new RuntimeException('Só aceitamos imagens (JPG, PNG, WEBP, GIF, HEIC) e ficheiros PDF.');
        }
    }

    private function extensionFor(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/heic' => 'heic',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    /**
     * Remove metadados de imagens (EXIF, GPS) reescrevendo o ficheiro.
     * Silencioso quando a extensão GD não suporta o formato: o anexo continua
     * válido, apenas sem esta limpeza adicional.
     */
    private function stripImageMetadata(string $path, string $mime): void
    {
        if (! function_exists('imagecreatefromstring')) {
            return;
        }

        rescue(function () use ($path, $mime): void {
            $disk = \Illuminate\Support\Facades\Storage::disk('private');
            $contents = $disk->get($path);
            $image = @imagecreatefromstring((string) $contents);

            if ($image === false) {
                return;
            }

            ob_start();

            match ($mime) {
                'image/png' => imagepng($image, null, 6),
                'image/webp' => imagewebp($image, null, 85),
                'image/gif' => imagegif($image),
                default => imagejpeg($image, null, 88),
            };

            $clean = (string) ob_get_clean();
            imagedestroy($image);

            if ($clean !== '') {
                $disk->put($path, $clean, ['visibility' => 'private']);
            }
        }, report: false);
    }

    private function sanitiseName(string $name): string
    {
        $name = preg_replace('/[^\p{L}\p{N}._\- ]+/u', '', $name) ?? 'ficheiro';

        return mb_substr(trim($name) ?: 'ficheiro', 0, 120);
    }
}
