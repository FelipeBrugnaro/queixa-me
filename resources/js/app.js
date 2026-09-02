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
            if (panel.dataset.togglePersistent !== undefined) return;
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
            counter.textContent = max
                ? `${length} / ${max} caracteres`
                : `${length} caracteres`;

            const tooShort = min > 0 && length > 0 && length < min;
            const tooLong = max > 0 && length > max;
            counter.classList.toggle('text-rose-600', tooShort || tooLong);
            counter.classList.toggle('text-ink-500', !tooShort && !tooLong);

            if (tooShort) {
                counter.textContent = `Faltam ${min - length} caracteres (mínimo ${min})`;
            }
        };

        field.addEventListener('input', update);
        update();
    });
}

/* ------------------------------------------------------------------ */
/* Pesquisa de empresas com sugestões                                  */
/* ------------------------------------------------------------------ */

function initCompanyAutocomplete() {
    document.querySelectorAll('[data-company-search]').forEach((input) => {
        const listId = input.getAttribute('aria-controls');
        const list = listId ? document.getElementById(listId) : null;
        if (!list) return;

        const hidden = document.getElementById(input.dataset.companySearch);
        let controller = null;
        let timer = null;
        let activeIndex = -1;

        const close = () => {
            list.setAttribute('hidden', '');
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
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
                    'flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm hover:bg-ink-50 focus:bg-ink-50 focus:outline-none';
                button.innerHTML = `
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-xs font-bold text-brand-700">${company.initials}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium text-ink-900">${company.name}</span>
                        <span class="block truncate text-xs text-ink-500">${company.meta}</span>
                    </span>`;
                button.addEventListener('click', () => choose(button));
                list.appendChild(button);
            });

            const createNew = document.createElement('button');
            createNew.type = 'button';
            createNew.className =
                'flex w-full items-center gap-3 border-t border-ink-100 px-4 py-2.5 text-left text-sm hover:bg-ink-50';
            createNew.innerHTML = `
                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-ink-100 text-ink-600">+</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-medium text-ink-900">Adicionar &ldquo;${term}&rdquo;</span>
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
                    const response = await fetch(
                        `${input.dataset.endpoint}?q=${encodeURIComponent(term)}`,
                        { signal: controller.signal, headers: { Accept: 'application/json' } }
                    );
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

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex =
                    event.key === 'ArrowDown'
                        ? (activeIndex + 1) % options.length
                        : (activeIndex - 1 + options.length) % options.length;
                options[activeIndex].focus();
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
                row.className = `flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm ${
                    tooBig ? 'bg-rose-50 text-rose-700' : 'bg-ink-50 text-ink-700'
                }`;
                row.innerHTML = `<span class="truncate">${file.name}</span>
                    <span class="shrink-0 text-xs">${Math.max(1, Math.round(file.size / 1024))} KB</span>`;
                preview.appendChild(row);
            });

            const errorBox = document.getElementById(`${input.id}_errors`);
            if (errorBox) {
                errorBox.innerHTML = errors.map((e) => `<p>${e}</p>`).join('');
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

    // A rota de resultado espera ?empresas=a,b — um URL limpo e partilhável
    // em vez de empresas[]=a&empresas[]=b.
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
                button.innerHTML = 'A processar…';
            });
        });
    });
}

ready(() => {
    initToggles();
    initCharacterCounters();
    initCompanyAutocomplete();
    initFileInputs();
    initCompareLimit();
    initSubmitGuards();
});
