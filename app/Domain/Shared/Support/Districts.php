<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

/**
 * Distritos e regiões autónomas de Portugal.
 *
 * Estava duplicado em três controllers. Centralizado aqui porque é uma lista
 * de referência estável: se algum dia mudar, muda num sítio só.
 */
class Districts
{
    /** @return array<int,string> */
    public static function all(): array
    {
        return [
            'Aveiro', 'Beja', 'Braga', 'Bragança', 'Castelo Branco', 'Coimbra', 'Évora',
            'Faro', 'Guarda', 'Leiria', 'Lisboa', 'Portalegre', 'Porto', 'Santarém',
            'Setúbal', 'Viana do Castelo', 'Vila Real', 'Viseu',
            'Região Autónoma dos Açores', 'Região Autónoma da Madeira',
        ];
    }

    /** @return array<string,string> para usar em campos <select> */
    public static function options(): array
    {
        return array_combine(self::all(), self::all());
    }
}
