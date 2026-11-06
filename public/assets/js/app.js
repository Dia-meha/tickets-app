/* Interactions côté navigateur. Ces contrôles ne remplacent pas la validation serveur. */
(function () {
    'use strict';

    // Longueur comptée comme le serveur : retours à la ligne normalisés, espaces de début/fin ignorés.
    function textLength(value) {
        return Array.from(value.replace(/\r\n/g, '\n').trim()).length;
    }

    /* ---------- 1. Validation des formulaires ---------- */
    function fieldError(input) {
        const label = input.dataset.label || 'Ce champ';
        const value = input.value;
        const len = textLength(value);

        if (input.required && len === 0) {
            return input.tagName === 'SELECT' ? label + ' : veuillez faire un choix.' : label + ' est obligatoire.';
        }
        if (input.type === 'email' && len > 0 && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) {
            return 'Saisissez une adresse e-mail valide.';
        }
        const min = input.dataset.min ? parseInt(input.dataset.min, 10) : null;
        const max = input.dataset.max ? parseInt(input.dataset.max, 10) : null;
        if (min !== null && len < min) {
            return label + ' doit contenir au moins ' + min + ' caractères (actuellement ' + len + ').';
        }
        if (max !== null && len > max) {
            return label + ' ne doit pas dépasser ' + max + ' caractères (actuellement ' + len + ').';
        }
        return '';
    }

    function showFieldError(input, message) {
        const feedback = document.getElementById(input.id + '-feedback');
        input.classList.toggle('is-invalid', message !== '');
        input.classList.toggle('is-valid', message === '' && input.value.trim() !== '');
        input.setAttribute('aria-invalid', message !== '' ? 'true' : 'false');
        if (feedback) { feedback.textContent = message; }
    }

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        const inputs = Array.from(form.querySelectorAll('input[required], textarea[required], select[required]'));

        inputs.forEach(function (input) {
            input.addEventListener('blur', function () { showFieldError(input, fieldError(input)); });
            input.addEventListener('input', function () {
                if (input.classList.contains('is-invalid')) { showFieldError(input, fieldError(input)); }
            });
        });

        form.addEventListener('submit', function (event) {
            let firstInvalid = null;
            inputs.forEach(function (input) {
                const message = fieldError(input);
                showFieldError(input, message);
                if (message && !firstInvalid) { firstInvalid = input; }
            });
            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });
    });

    /* ---------- 2. Compteur de caractères ---------- */
    document.querySelectorAll('[data-counter-for]').forEach(function (counter) {
        const input = document.getElementById(counter.dataset.counterFor);
        const max = parseInt(counter.dataset.max, 10);
        if (!input) { return; }
        counter.classList.add('char-counter');

        function update() {
            const len = textLength(input.value);
            const over = len > max;
            counter.textContent = len + ' / ' + max + ' caractères' + (over ? ' – limite dépassée de ' + (len - max) : '');
            counter.classList.toggle('over', over);
        }
        input.addEventListener('input', update);
        update();
    });

    /* ---------- 3. Modale de confirmation de suppression ---------- */
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            if (!trigger) { return; }
            document.getElementById('deleteTicketId').value = trigger.dataset.ticketId;
            // textContent : le titre n'est jamais interprété comme du HTML
            document.getElementById('deleteTicketTitle').textContent = trigger.dataset.ticketTitle;
        });
    }

    /* ---------- 4. Liste dynamique : recherche et filtres avec fetch() ---------- */
    const filters = document.getElementById('ticket-filters');
    if (!filters) { return; }

    const endpoint = filters.dataset.endpoint;
    const showAuthor = filters.dataset.showAuthor === '1';
    const els = {
        loading: document.getElementById('ticket-loading'),
        empty: document.getElementById('ticket-empty'),
        error: document.getElementById('ticket-error'),
        table: document.getElementById('ticket-table'),
        rows: document.getElementById('ticket-rows'),
        count: document.getElementById('ticket-count'),
    };
    let controller = null;
    let timer = null;

    function setState(state) {
        els.loading.hidden = state !== 'loading';
        els.empty.hidden = state !== 'empty';
        els.error.hidden = state !== 'error';
        els.table.hidden = state !== 'list';
    }

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) { node.className = className; }
        if (text !== undefined) { node.textContent = text; }   // textContent : jamais de HTML interprété
        return node;
    }

    function badge(label, cls) { return el('span', 'badge ' + cls, label); }

    function renderRow(t) {
        const tr = document.createElement('tr');
        tr.appendChild(el('td', 'text-muted', '#' + t.id));

        const titleCell = el('td', 'ticket-title');
        const link = el('a', 'fw-semibold text-decoration-none', t.titre);
        link.href = '/ticket_view.php?id=' + encodeURIComponent(t.id);
        titleCell.appendChild(link);
        tr.appendChild(titleCell);

        tr.appendChild(el('td', '', t.categorie_label));
        const p = el('td'); p.appendChild(badge(t.priorite_label, t.priorite_badge)); tr.appendChild(p);
        const s = el('td'); s.appendChild(badge(t.statut_label, t.statut_badge)); tr.appendChild(s);
        if (showAuthor) { tr.appendChild(el('td', '', t.auteur)); }
        tr.appendChild(el('td', 'text-nowrap small text-muted', t.updated_at));

        const actions = el('td', 'text-end text-nowrap');
        const view = el('a', 'btn btn-sm btn-outline-primary', showAuthor ? 'Traiter' : 'Voir');
        view.href = link.href;
        actions.appendChild(view);
        if (t.can_edit) {
            const edit = el('a', 'btn btn-sm btn-outline-secondary ms-1', 'Modifier');
            edit.href = '/ticket_edit.php?id=' + encodeURIComponent(t.id);
            actions.appendChild(edit);

            const del = el('button', 'btn btn-sm btn-outline-danger ms-1', 'Supprimer');
            del.type = 'button';
            del.dataset.bsToggle = 'modal';
            del.dataset.bsTarget = '#deleteModal';
            del.dataset.ticketId = String(t.id);
            del.dataset.ticketTitle = t.titre;
            actions.appendChild(del);
        }
        tr.appendChild(actions);
        return tr;
    }

    async function load() {
        if (controller) { controller.abort(); }   // ignore la réponse d'une recherche déjà dépassée
        controller = new AbortController();
        const myController = controller;

        const params = new URLSearchParams(new FormData(filters));
        for (const [key, value] of Array.from(params.entries())) {
            if (value.trim() === '') { params.delete(key); }
        }

        setState('loading');
        els.count.textContent = '';
        try {
            const response = await fetch(endpoint + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: myController.signal,
            });
            if (response.status === 401) { window.location.href = '/login.php'; return; }
            if (!response.ok) { throw new Error('HTTP ' + response.status); }
            const data = await response.json();
            if (!data || !Array.isArray(data.tickets)) { throw new Error('Réponse invalide'); }

            els.rows.replaceChildren(...data.tickets.map(renderRow));
            if (data.tickets.length === 0) {
                setState('empty');
            } else {
                setState('list');
                els.count.textContent = data.tickets.length + ' ticket' + (data.tickets.length > 1 ? 's' : '');
            }
        } catch (err) {
            if (err.name === 'AbortError') { return; }
            setState('error');
        }
    }

    filters.addEventListener('input', function (event) {
        clearTimeout(timer);
        // petite temporisation pour la saisie, immédiat pour les listes déroulantes
        timer = setTimeout(load, event.target.type === 'search' ? 250 : 0);
    });
    filters.addEventListener('submit', function (event) { event.preventDefault(); load(); });
    filters.addEventListener('reset', function () { setTimeout(load, 0); });
    document.getElementById('ticket-retry').addEventListener('click', load);

    load();
})();
