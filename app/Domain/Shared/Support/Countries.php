<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

/**
 * Acesso à lista de países.
 *
 * A bandeira é derivada do código ISO em vez de guardada: cada letra é
 * convertida no seu "regional indicator symbol", e o par forma o emoji da
 * bandeira. Evita uma segunda coluna de dados que teria de ser mantida em
 * sincronia com a primeira.
 */
class Countries
{
    /** @return array<string,string> código => "🇵🇹 Portugal" */
    public static function options(): array
    {
        $list = (array) config('countries.list');
        $priority = (array) config('countries.priority');

        $top = [];
        $rest = [];

        foreach ($list as $code => $name) {
            $label = self::flag((string) $code).'  '.$name;

            if (in_array($code, $priority, true)) {
                $top[$code] = $label;
            } else {
                $rest[$code] = $label;
            }
        }

        // Os prioritários seguem a ordem definida na configuração;
        // os restantes vão por ordem alfabética portuguesa.
        $ordered = [];

        foreach ($priority as $code) {
            if (isset($top[$code])) {
                $ordered[$code] = $top[$code];
            }
        }

        uasort($rest, static fn (string $a, string $b) => strcoll($a, $b));

        return $ordered + $rest;
    }

    public static function name(?string $code): ?string
    {
        return $code ? (config('countries.list')[strtoupper($code)] ?? null) : null;
    }

    /** Emoji da bandeira a partir do código ISO 3166-1 alpha-2. */
    public static function flag(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return '🏳️';
        }

        $base = 0x1F1E6 - ord('A');

        return mb_chr($base + ord($code[0]), 'UTF-8').mb_chr($base + ord($code[1]), 'UTF-8');
    }

    /** Nome com bandeira, para leitura direta. */
    public static function label(?string $code): string
    {
        $name = self::name($code);

        return $name ? self::flag($code).' '.$name : '—';
    }

    /** @return array<int,string> códigos válidos, para validação */
    public static function codes(): array
    {
        return array_keys((array) config('countries.list'));
    }
}
