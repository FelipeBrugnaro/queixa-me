<?php

declare(strict_types=1);

/**
 * Países disponíveis nos formulários.
 *
 * A ordem não é alfabética por acaso: Portugal e os países com maior
 * comunidade portuguesa aparecem primeiro, porque são a origem da quase
 * totalidade dos utilizadores. Obrigar alguém a percorrer a lista até ao
 * "P" para escolher o caso mais comum é fricção gratuita.
 *
 * A bandeira é o emoji regional correspondente ao código ISO 3166-1
 * alpha-2 — sem imagens, sem pedidos extra, e acompanha o tipo de letra.
 */
return [

    'default' => 'PT',

    /** Códigos destacados no topo da lista. */
    'priority' => ['PT', 'ES', 'FR', 'GB', 'BR', 'CH', 'LU', 'DE', 'AO', 'MZ'],

    /** @var array<string,string> código ISO => nome em português */
    'list' => [
        'PT' => 'Portugal',
        'ES' => 'Espanha',
        'FR' => 'França',
        'GB' => 'Reino Unido',
        'BR' => 'Brasil',
        'CH' => 'Suíça',
        'LU' => 'Luxemburgo',
        'DE' => 'Alemanha',
        'AO' => 'Angola',
        'MZ' => 'Moçambique',
        'AD' => 'Andorra',
        'AT' => 'Áustria',
        'BE' => 'Bélgica',
        'BG' => 'Bulgária',
        'CA' => 'Canadá',
        'CV' => 'Cabo Verde',
        'CN' => 'China',
        'CY' => 'Chipre',
        'CZ' => 'Chéquia',
        'DK' => 'Dinamarca',
        'EE' => 'Estónia',
        'FI' => 'Finlândia',
        'GR' => 'Grécia',
        'GW' => 'Guiné-Bissau',
        'HR' => 'Croácia',
        'HU' => 'Hungria',
        'IE' => 'Irlanda',
        'IN' => 'Índia',
        'IS' => 'Islândia',
        'IT' => 'Itália',
        'JP' => 'Japão',
        'LT' => 'Lituânia',
        'LV' => 'Letónia',
        'MA' => 'Marrocos',
        'MT' => 'Malta',
        'MX' => 'México',
        'NL' => 'Países Baixos',
        'NO' => 'Noruega',
        'PL' => 'Polónia',
        'RO' => 'Roménia',
        'SE' => 'Suécia',
        'SI' => 'Eslovénia',
        'SK' => 'Eslováquia',
        'ST' => 'São Tomé e Príncipe',
        'TL' => 'Timor-Leste',
        'TR' => 'Turquia',
        'UA' => 'Ucrânia',
        'US' => 'Estados Unidos',
        'ZA' => 'África do Sul',
    ],
];
