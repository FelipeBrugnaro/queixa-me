/**
 * queixa.me — comportamento do cliente.
 *
 * Sem framework de front-end por opção: todas as páginas públicas são
 * renderizadas no servidor (Blade) e o JavaScript limita-se a melhorias
 * progressivas. Nada aqui é necessário para ler o conteúdo, submeter um
 * formulário ou navegar — o que protege o SEO e os Core Web Vitals.
 */

const ready = (fn) =>
    document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);

const escapeHtml = (value) =>
    String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));

/* ------------------------------------------------------------------ */
/* Menus e painéis                                                     */
/* ------------------------------------------------------------------ */

function initToggles() {
    document.querySelectorAll('[data-toggle-target]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.stopPropagation();
            const target = document.getElementById(trigger.dataset.toggleTarget);
            if (!target) return;

            const isHidden = target.hasAttribute('hidden');
            document.querySelectorAll('[data-toggle-panel]').forEach((panel) => {
                if (panel !== target) panel.setAttribute('hidden', '');
            });

            if (isHidden) {
                target.removeAttribute('hidden');
                trigger.setAttribute('aria-expanded', 'true');
            } else {
                target.setAttribute('hidden', '');
                trigger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-toggle-panel]:not([hidden])').forEach((panel) => {
            if (!panel.contains(event.target)) {
                panel.setAttribute('hidden', '');
                document
                    .querySelectorAll(`[data-toggle-target="${panel.id}"]`)
                    .forEach((t) => t.setAttribute('aria-expanded', 'false'));
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('[data-toggle-panel]:not([hidden])').forEach((panel) => {
            panel.setAttribute('hidden', '');
        });
    });
}

/* ------------------------------------------------------------------ */
/* Pesquisa em sobreposição                                            */
/* ------------------------------------------------------------------ */

function initSearchModal() {
    const modal = document.querySelector('[data-search-modal]');
    if (!modal) return;

    const panel = modal.querySelector('[data-search-panel]');
    const input = modal.querySelector('[data-search-input]');
    const results = modal.querySelector('[data-search-results]');
    const emptyState = modal.querySelector('[data-search-empty]');

    let controller = null;
    let timer = null;
    let lastFocused = null;

    const open = () => {
        lastFocused = document.activeElement;
        modal.removeAttribute('hidden');
        document.body.style.overflow = 'hidden';
        // Focar só depois de o painel entrar, para a animação não saltar.
        requestAnimationFrame(() => input?.focus());
    };

    const close = () => {
        modal.setAttribute('hidden', '');
        document.body.style.overflow = '';
        controller?.abort();
        lastFocused?.focus?.();
    };

    const render = (companies, term) => {
        if (!companies.length) {
            results.innerHTML = `
                <div class="px-5 py-8 text-center">
                    <p class="text-sm font-semibold text-ink-700">Sem resultados para &ldquo;${escapeHtml(term)}&rdquo;</p>
                    <p class="mt-1 text-xs text-ink-500">A empresa pode ainda não estar no portal — podes indicá-la ao reclamar.</p>
                </div>`;
            return;
        }

        results.innerHTML = companies
            .map(
                (company) => `
                <a href="${escapeHtml(company.url)}"
                   class="flex items-center gap-3 px-5 py-3 transition hover:bg-ink-50 focus:bg-ink-50 focus:outline-none">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-extrabold text-brand-700">
                        ${escapeHtml(company.initials)}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-bold text-ink-900">${escapeHtml(company.name)}</span>
                        <span class="block truncate text-xs text-ink-500">${escapeHtml(company.meta)}</span>
                    </span>
                    ${
                        company.index !== null && company.index !== undefined
                            ? `<span class="shrink-0 rounded-full bg-ink-100 px-2.5 py-1 text-xs font-extrabold tabular-nums text-ink-700">${escapeHtml(company.index)}</span>`
                            : ''
                    }
                </a>`
            )
            .join('');
    };

    input?.addEventListener('input', () => {
        const term = input.value.trim();
        clearTimeout(timer);

        if (term.length < 2) {
            results.innerHTML = '';
            emptyState?.removeAttribute('hidden');
            return;
        }

        emptyState?.setAttribute('hidden', '');

        timer = setTimeout(async () => {
            controller?.abort();
            controller = new AbortController();

            try {
                const response = await fetch(`${input.dataset.endpoint}?q=${encodeURIComponent(term)}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) return;
                const data = await response.json();
                render(data.companies || [], term);
            } catch (error) {
                if (error.name !== 'AbortError') results.innerHTML = '';
            }
        }, 160);
    });

    // Setas percorrem os resultados sem tirar as mãos do teclado.
    input?.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowDown') return;
        event.preventDefault();
        results.querySelector('a')?.focus();
    });

    results?.addEventListener('keydown', (event) => {
        const links = [...results.querySelectorAll('a')];
        const index = links.indexOf(document.activeElement);
        if (index === -1) return;

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            (links[index + 1] || links[0]).focus();
        }
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            index === 0 ? input.focus() : links[index - 1].focus();
        }
    });

    document.querySelectorAll('[data-search-open]').forEach((b) => b.addEventListener('click', open));
    modal.querySelectorAll('[data-search-close]').forEach((b) => b.addEventListener('click', close));

    modal.addEventListener('mousedown', (event) => {
        if (!panel.contains(event.target)) close();
    });

    document.addEventListener('keydown', (event) => {
        const typingElsewhere = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName || '');

        if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
            close();
        }

        // "/" abre a pesquisa, à maneira dos produtos que se usam a sério.
        if (event.key === '/' && !typingElsewhere && modal.hasAttribute('hidden')) {
            event.preventDefault();
            open();
        }
    });
}

/* ------------------------------------------------------------------ */
/* Contadores de caracteres                                            */
/* ------------------------------------------------------------------ */

function initCharacterCounters() {
    document.querySelectorAll('[data-counter-for]').forEach((counter) => {
        const field = document.getElementById(counter.dataset.counterFor);
        if (!field) return;

        const min = parseInt(counter.dataset.counterMin || '0', 10);
        const max = parseInt(counter.dataset.counterMax || '0', 10);

        const update = () => {
            const length = field.value.length;
            const tooShort = min > 0 && length < min;
            const tooLong = max > 0 && length > max;

            counter.textContent = tooShort && length > 0
                ? `Faltam ${min - length} caracteres`
                : max
                    ? `${length} / ${max}`
                    : `${length} caracteres`;

            counter.classList.toggle('text-rose-600', tooShort && length > 0);
            counter.classList.toggle('text-rose-600', tooLong);
            counter.classList.toggle('text-ink-400', !tooShort && !tooLong);
        };

        field.addEventListener('input', update);
        update();
    });
}

/* ------------------------------------------------------------------ */
/* Pesquisa de empresas em campos de formulário                        */
/* ------------------------------------------------------------------ */

function initCompanyAutocomplete() {
    document.querySelectorAll('[data-company-search]').forEach((input) => {
        const listId = input.getAttribute('aria-controls');
        const list = listId ? document.getElementById(listId) : null;
        if (!list) return;

        const hidden = document.getElementById(input.dataset.companySearch);
        let controller = null;
        let timer = null;

        const close = () => {
            list.setAttribute('hidden', '');
            input.setAttribute('aria-expanded', 'false');
        };

        const choose = (button) => {
            input.value = button.dataset.name;
            if (hidden) hidden.value = button.dataset.id || '';
            const newFlag = document.getElementById('company_is_new');
            if (newFlag) newFlag.value = '0';
            close();
        };

        const render = (companies, term) => {
            list.innerHTML = '';

            companies.forEach((company) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.id = company.id;
                button.dataset.name = company.name;
                button.className =
                    'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition hover:bg-ink-50 focus:bg-ink-50 focus:outline-none';
                button.innerHTML = `
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-[0.6875rem] font-extrabold text-brand-700">${escapeHtml(company.initials)}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-bold text-ink-900">${escapeHtml(company.name)}</span>
                        <span class="block truncate text-xs text-ink-500">${escapeHtml(company.meta)}</span>
                    </span>`;
                button.addEventListener('click', () => choose(button));
                list.appendChild(button);
            });

            const createNew = document.createElement('button');
            createNew.type = 'button';
            createNew.className =
                'flex w-full items-center gap-3 border-t border-ink-100 px-4 py-2.5 text-left text-sm transition hover:bg-ink-50';
            createNew.innerHTML = `
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-500">+</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-bold text-ink-900">Adicionar &ldquo;${escapeHtml(term)}&rdquo;</span>
                    <span class="block text-xs text-ink-500">A empresa será validada pela nossa equipa</span>
                </span>`;
            createNew.addEventListener('click', () => {
                if (hidden) hidden.value = '';
                const newFlag = document.getElementById('company_is_new');
                if (newFlag) newFlag.value = '1';
                close();
            });
            list.appendChild(createNew);

            list.removeAttribute('hidden');
            input.setAttribute('aria-expanded', 'true');
        };

        input.addEventListener('input', () => {
            const term = input.value.trim();
            if (hidden) hidden.value = '';
            clearTimeout(timer);

            if (term.length < 2) {
                close();
                return;
            }

            timer = setTimeout(async () => {
                controller?.abort();
                controller = new AbortController();

                try {
                    const response = await fetch(`${input.dataset.endpoint}?q=${encodeURIComponent(term)}`, {
                        signal: controller.signal,
                        headers: { Accept: 'application/json' },
                    });
                    if (!response.ok) return;
                    const data = await response.json();
                    render(data.companies || [], term);
                } catch (error) {
                    if (error.name !== 'AbortError') close();
                }
            }, 180);
        });

        input.addEventListener('keydown', (event) => {
            const options = [...list.querySelectorAll('button')];
            if (!options.length || list.hasAttribute('hidden')) return;

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                options[0].focus();
            }
            if (event.key === 'Escape') close();
        });

        document.addEventListener('click', (event) => {
            if (!list.contains(event.target) && event.target !== input) close();
        });
    });
}

/* ------------------------------------------------------------------ */
/* Anexos: pré-validação antes de gastar largura de banda              */
/* ------------------------------------------------------------------ */

function initFileInputs() {
    document.querySelectorAll('[data-file-input]').forEach((input) => {
        const preview = document.getElementById(input.dataset.fileInput);
        if (!preview) return;

        const maxSize = parseInt(input.dataset.maxSize || '0', 10) * 1024;
        const maxFiles = parseInt(input.dataset.maxFiles || '0', 10);

        input.addEventListener('change', () => {
            preview.innerHTML = '';
            const files = [...input.files];
            const errors = [];

            if (maxFiles && files.length > maxFiles) {
                errors.push(`Podes anexar no máximo ${maxFiles} ficheiros.`);
            }

            files.forEach((file) => {
                const tooBig = maxSize && file.size > maxSize;
                if (tooBig) errors.push(`"${file.name}" excede o tamanho máximo permitido.`);

                const row = document.createElement('li');
                row.className = `flex items-center justify-between gap-3 rounded-xl px-3 py-2.5 text-sm ${
                    tooBig ? 'bg-rose-50 text-rose-700' : 'bg-ink-50 text-ink-700'
                }`;
                row.innerHTML = `<span class="truncate font-medium">${escapeHtml(file.name)}</span>
                    <span class="shrink-0 text-xs text-ink-400">${Math.max(1, Math.round(file.size / 1024))} KB</span>`;
                preview.appendChild(row);
            });

            const errorBox = document.getElementById(`${input.id}_errors`);
            if (errorBox) {
                errorBox.innerHTML = errors.map((e) => `<p>${escapeHtml(e)}</p>`).join('');
                errorBox.classList.toggle('hidden', errors.length === 0);
            }
        });
    });
}

/* ------------------------------------------------------------------ */
/* Comparador de marcas                                                */
/* ------------------------------------------------------------------ */

function initCompareLimit() {
    const form = document.querySelector('[data-compare-form]');
    if (!form) return;

    const max = parseInt(form.dataset.compareMax || '4', 10);
    const boxes = [...form.querySelectorAll('input[type="checkbox"][name="empresas[]"]')];
    const counter = form.querySelector('[data-compare-counter]');

    const sync = () => {
        const checked = boxes.filter((b) => b.checked);
        boxes.forEach((b) => {
            b.disabled = !b.checked && checked.length >= max;
        });
        if (counter) counter.textContent = `${checked.length} de ${max} selecionadas`;
    };

    boxes.forEach((b) => b.addEventListener('change', sync));
    sync();

    form.addEventListener('submit', (event) => {
        const selected = boxes.filter((b) => b.checked).map((b) => b.value);
        event.preventDefault();

        if (selected.length < 2) {
            const notice = form.querySelector('[data-compare-counter]');
            if (notice) {
                notice.textContent = 'Seleciona pelo menos duas empresas';
                notice.classList.add('text-rose-600');
            }
            return;
        }

        const target = form.dataset.compareTarget || form.action;
        window.location = `${target}?empresas=${encodeURIComponent(selected.join(','))}`;
    });
}

/* ------------------------------------------------------------------ */
/* Submissões: evitar duplo clique                                     */
/* ------------------------------------------------------------------ */

function initSubmitGuards() {
    document.querySelectorAll('form[data-guard-submit]').forEach((form) => {
        form.addEventListener('submit', () => {
            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.dataset.originalText = button.innerHTML;
                button.innerHTML =
                    '<span class="inline-block size-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> A processar…';
            });
        });
    });
}

/* ------------------------------------------------------------------ */
/* Confirmação de sucesso                                              */
/* ------------------------------------------------------------------ */

function initSuccessOverlay() {
    const overlay = document.querySelector('[data-success-overlay]');
    if (!overlay) return;

    // Devolve o foco ao conteúdo e deixa a sobreposição sair sozinha:
    // é uma confirmação, não uma decisão a tomar.
    const dismiss = () => {
        overlay.style.transition = 'opacity 0.4s ease';
        overlay.style.opacity = '0';
        setTimeout(() => overlay.remove(), 400);
    };

    overlay.addEventListener('click', dismiss);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && dismiss());
    setTimeout(dismiss, 3600);
}

ready(() => {
    initToggles();
    initSearchModal();
    initCharacterCounters();
    initCompanyAutocomplete();
    initFileInputs();
    initCompareLimit();
    initSubmitGuards();
    initSuccessOverlay();
});
