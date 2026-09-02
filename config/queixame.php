<?php

declare(strict_types=1);

/**
 * Configuracao de dominio do queixa.me.
 *
 * Todas as regras de negocio ajustaveis vivem aqui (e nao espalhadas por
 * controllers ou views) para que possam ser alteradas, versionadas e
 * documentadas publicamente na pagina de metodologia dos indices.
 */
return [

    'brand' => [
        'name' => 'queixa.me',
        'tagline' => 'Reclamar bem. Responder melhor.',
        'canonical_url' => rtrim((string) env('APP_CANONICAL_URL', env('APP_URL', 'https://queixa.me')), '/'),
        'contact_email' => 'ola@queixa.me',
        'dpo_email' => 'privacidade@queixa.me',
    ],

    'legal' => [
        'terms_version' => env('LEGAL_TERMS_VERSION', '1.0'),
        'privacy_version' => env('LEGAL_PRIVACY_VERSION', '1.0'),
        'data_protection_version' => env('LEGAL_DATA_PROTECTION_VERSION', '1.0'),
    ],

    'accounts' => [
        'minimum_age' => (int) env('QM_MINIMUM_AGE', 16),
        'email_change_ttl_minutes' => 60,
        'phone_code_ttl_minutes' => 10,
        'public_name_min' => 3,
        'public_name_max' => 40,
        // Nomes publicos reservados: evitam personificacao do portal ou de marcas.
        'reserved_public_names' => ['queixa', 'queixame', 'queixa.me', 'admin', 'administrador', 'moderador', 'moderacao', 'suporte', 'staff', 'equipa', 'oficial'],
    ],

    'complaints' => [
        'title_min' => 10,
        'title_max' => 120,
        'description_min' => 100,
        'description_max' => 6000,
        'desired_resolution_max' => 1500,
        'extra_info_max' => 1500,
        'reply_min' => 20,
        'reply_max' => 4000,

        // Janela em que o autor pode editar sem nova moderacao (minutos).
        'grace_edit_minutes' => 0,

        // Dias sem resposta da empresa antes de a reclamacao contar como
        // "sem resposta" nos indicadores.
        'response_sla_days' => 15,

        // Dias apos a ultima interacao para encerramento automatico.
        'auto_close_days' => 60,

        // Dias que o consumidor tem para confirmar a resolucao proposta
        // pela empresa antes de o sistema encerrar sem confirmacao.
        'resolution_confirmation_days' => 15,

        'attachments' => [
            'max_files' => 8,
            'max_size_kb' => 8192,
            'max_total_size_kb' => 32768,
            'allowed_mimes' => [
                'image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/gif',
                'application/pdf',
            ],
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'heic', 'gif', 'pdf'],
        ],

        // Anti-abuso: limites por utilizador.
        'rate_limits' => [
            'per_day' => 5,
            'per_company_per_month' => 3,
        ],
    ],

    'moderation' => [
        // Padroes que sinalizam dados sensiveis no texto submetido.
        // Nao rejeitam automaticamente: elevam a prioridade na fila.
        'sensitive_patterns' => [
            'iban' => '/\b[A-Z]{2}\d{2}[ ]?(?:[A-Z0-9]{4}[ ]?){3,7}[A-Z0-9]{1,4}\b/i',
            'nif_pt' => '/\b[1235689]\d{8}\b/',
            'cc_pt' => '/\b\d{8}[ ]?\d[ ]?[A-Z]{2}\d\b/i',
            'card' => '/\b(?:\d[ -]*?){13,16}\b/',
            'phone_pt' => '/\b(?:\+351[ ]?)?[92][0-9]{8}\b/',
            'email' => '/[\w.+-]+@[\w-]+\.[\w.]{2,}/',
            'plate_pt' => '/\b[A-Z0-9]{2}-[A-Z0-9]{2}-[A-Z0-9]{2}\b/i',
        ],
        'priority_boost_when_sensitive' => 20,
    ],

    /**
     * METODOLOGIA DOS INDICES
     *
     * Problema: um ranking baseado no numero absoluto de reclamacoes premeia
     * empresas pequenas e pune empresas grandes, e um ranking baseado em
     * medias simples deixa uma empresa com 1 reclamacao resolvida no topo.
     *
     * Solucao: media bayesiana (shrinkage para a media global) sobre tres
     * componentes normalizados, com minimo de reclamacoes para figurar no
     * ranking publico e janela movel de 12 meses.
     */
    'index' => [
        // Peso de cada componente no indice de satisfacao (soma = 1.0).
        'weights' => [
            'response_rate' => 0.25,   // A empresa responde?
            'resolution_rate' => 0.35, // Resolve, confirmado pelo consumidor?
            'satisfaction' => 0.30,    // Avaliacao 1-5 dada pelo consumidor
            'speed' => 0.10,           // Rapidez da primeira resposta
        ],

        // Constante de suavizacao bayesiana: numero de "reclamacoes virtuais"
        // com o valor medio do mercado somadas a cada empresa. Quanto maior,
        // mais dificil e subir ou descer com poucas reclamacoes.
        'bayesian_prior_weight' => 10,

        // Minimo de reclamacoes publicadas na janela para constar do ranking.
        'ranking_minimum_complaints' => 5,

        // Tempo de resposta (horas) que corresponde a pontuacao maxima e minima
        // na componente de velocidade.
        'speed_best_hours' => 24,
        'speed_worst_hours' => 336, // 14 dias

        // Escala final do indice.
        'scale' => 100,
    ],

    'awards' => [
        // Minimo de reclamacoes no mes para uma empresa poder ser distinguida.
        'minimum_complaints' => 3,
        'per_award' => 1,
    ],

    'search' => [
        'per_page' => 15,
        'companies_per_page' => 24,
        'autocomplete_limit' => 8,
    ],

    'seo' => [
        // Paginas de empresa sem conteudo real sao "thin content": ficam
        // noindex ate terem reclamacoes publicadas suficientes.
        'company_min_complaints_to_index' => 1,
        'sitemap_chunk_size' => 5000,
        'default_image' => '/img/og-default.png',
    ],
];
