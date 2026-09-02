<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Cada consentimento é registado com o tipo, a versão do documento aceite,
 * a data/hora, o IP e o user agent. Isto permite provar consentimento
 * informado perante a CNPD e reconsentir quando um documento muda de versão.
 */
enum ConsentType: string
{
    use HasLabel;

    case Terms = 'terms';
    case Privacy = 'privacy';
    case DataProtection = 'data_protection';
    case Marketing = 'marketing';
    /** Consentimento específico para transmitir dados pessoais à entidade visada. */
    case DataTransferToCompany = 'data_transfer_to_company';

    public function label(): string
    {
        return match ($this) {
            self::Terms => 'Termos e Condições',
            self::Privacy => 'Política de Privacidade',
            self::DataProtection => 'Política de Proteção de Dados',
            self::Marketing => 'Comunicações de marketing',
            self::DataTransferToCompany => 'Transmissão de dados à entidade visada',
        };
    }

    public function isRevocable(): bool
    {
        return in_array($this, [self::Marketing], true);
    }

    public function currentVersion(): string
    {
        return match ($this) {
            self::Terms => (string) config('queixame.legal.terms_version'),
            self::Privacy => (string) config('queixame.legal.privacy_version'),
            self::DataProtection => (string) config('queixame.legal.data_protection_version'),
            self::Marketing, self::DataTransferToCompany => (string) config('queixame.legal.privacy_version'),
        };
    }
}
