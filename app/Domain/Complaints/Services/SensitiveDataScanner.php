<?php

declare(strict_types=1);

namespace App\Domain\Complaints\Services;

/**
 * Deteta dados sensiveis em texto submetido pelo utilizador.
 *
 * PROBLEMA: a maior fonte de risco RGPD num portal de reclamacoes nao e a
 * base de dados - e o proprio texto. As pessoas colam IBAN, NIF, numero de
 * cartao, moradas e nomes de funcionarios na descricao, e isso passa a ser
 * conteudo publico e indexado pelo Google.
 *
 * SOLUCAO: analisar o texto antes da publicacao, avisar o autor no momento
 * da escrita e elevar a prioridade na fila de moderacao. NAO rejeitamos
 * automaticamente: um falso positivo que bloqueie uma reclamacao legitima
 * e pior do que uma revisao humana de trinta segundos.
 */
class SensitiveDataScanner
{
    /**
     * @return array<string,array{count:int,samples:array<int,string>}>
     */
    public function scan(string ...$texts): array
    {
        $text = implode("\n", array_filter($texts));
        $findings = [];

        foreach ((array) config('queixame.moderation.sensitive_patterns') as $key => $pattern) {
            if (preg_match_all($pattern, $text, $matches) > 0) {
                $samples = array_slice(array_unique($matches[0]), 0, 3);

                $findings[$key] = [
                    'count' => count($matches[0]),
                    'samples' => array_map($this->mask(...), $samples),
                ];
            }
        }

        return $findings;
    }

    public function hasFindings(string ...$texts): bool
    {
        return $this->scan(...$texts) !== [];
    }

    /** Peso adicional na fila de moderacao. */
    public function priorityBoost(array $findings): int
    {
        if ($findings === []) {
            return 0;
        }

        $boost = (int) config('queixame.moderation.priority_boost_when_sensitive');

        // IBAN e cartao sao os casos com dano imediato mais grave.
        if (isset($findings['iban']) || isset($findings['card'])) {
            $boost *= 2;
        }

        return $boost;
    }

    /** @return array<int,string> Avisos legiveis para mostrar ao autor. */
    public function warningsFor(array $findings): array
    {
        $labels = [
            'iban' => 'Parece haver um IBAN no texto. Nunca publiques dados bancários.',
            'nif_pt' => 'Parece haver um NIF no texto. Remove-o antes de submeter.',
            'cc_pt' => 'Parece haver um número de cartão de cidadão no texto.',
            'card' => 'Parece haver um número de cartão de pagamento no texto.',
            'phone_pt' => 'Parece haver um número de telefone no texto. Usa antes o campo de contacto privado.',
            'email' => 'Parece haver um endereço de email no texto. Usa antes o campo de contacto privado.',
            'plate_pt' => 'Parece haver uma matrícula no texto.',
        ];

        return array_values(array_intersect_key($labels, $findings));
    }

    /** Guarda apenas uma amostra mascarada: nunca o valor completo. */
    private function mask(string $value): string
    {
        $value = trim($value);
        $length = mb_strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, 2).str_repeat('*', max(3, $length - 4)).mb_substr($value, -2);
    }
}
