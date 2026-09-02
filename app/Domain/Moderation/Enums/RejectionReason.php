<?php

declare(strict_types=1);

namespace App\Domain\Moderation\Enums;

use App\Domain\Shared\Concerns\HasLabel;

/**
 * Motivos normalizados de rejeicao / pedido de alteracao.
 * Sao codigos fechados para que a decisao seja auditavel, comparavel entre
 * moderadores e explicavel ao autor sem texto livre ambiguo.
 */
enum RejectionReason: string
{
    use HasLabel;

    case PersonalData = 'personal_data';
    case OffensiveLanguage = 'offensive_language';
    case Defamatory = 'defamatory';
    case NotAComplaint = 'not_a_complaint';
    case Duplicate = 'duplicate';
    case WrongCompany = 'wrong_company';
    case InsufficientDetail = 'insufficient_detail';
    case ThirdPartyData = 'third_party_data';
    case Spam = 'spam';
    case OutOfScope = 'out_of_scope';
    case LegalProceedings = 'legal_proceedings';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PersonalData => 'Contém dados pessoais que não devem ser públicos',
            self::OffensiveLanguage => 'Linguagem ofensiva ou desadequada',
            self::Defamatory => 'Conteúdo potencialmente difamatório ou não sustentado',
            self::NotAComplaint => 'O conteúdo não constitui uma reclamação',
            self::Duplicate => 'Reclamação duplicada',
            self::WrongCompany => 'A entidade indicada não corresponde',
            self::InsufficientDetail => 'Faltam elementos essenciais para a empresa responder',
            self::ThirdPartyData => 'Contém dados de terceiros sem consentimento',
            self::Spam => 'Spam ou conteúdo promocional',
            self::OutOfScope => 'Fora do âmbito do portal',
            self::LegalProceedings => 'Assunto em processo judicial em curso',
            self::Other => 'Outro motivo',
        };
    }

    public function guidanceForAuthor(): string
    {
        return match ($this) {
            self::PersonalData => 'Remove nomes de funcionários, moradas, NIF, IBAN, matrículas ou números de documento do texto e dos anexos.',
            self::OffensiveLanguage => 'Reescreve a reclamação descrevendo os factos sem insultos nem acusações pessoais.',
            self::Defamatory => 'Descreve apenas o que aconteceu e o que consegues comprovar, evitando acusações de crime.',
            self::NotAComplaint => 'Explica concretamente qual foi o problema, quando ocorreu e o que correu mal.',
            self::Duplicate => 'Já existe uma reclamação tua sobre este assunto. Atualiza a reclamação existente.',
            self::WrongCompany => 'Confirma qual é a entidade responsável e seleciona-a corretamente.',
            self::InsufficientDetail => 'Acrescenta datas, números de encomenda/contrato e o que já tentaste resolver com a empresa.',
            self::ThirdPartyData => 'Retira dados de outras pessoas que não sejam tu.',
            self::Spam => 'O portal destina-se a reclamações de consumo, não a divulgação comercial.',
            self::OutOfScope => 'Este assunto não é tratado pelo queixa.me. Consulta a nossa FAQ.',
            self::LegalProceedings => 'Assuntos com processo judicial em curso não são publicados.',
            self::Other => 'Consulta a mensagem da equipa de moderação.',
        };
    }
}
