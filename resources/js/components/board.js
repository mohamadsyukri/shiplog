/**
 * The board's diary (modules/board): a card opens its details in the side pane, Newer and Older step through
 * its lane on this page, Copy puts a source's ref on the clipboard, and the Lanes filter hides lanes,
 * remembered in this browser under 'board-lanes' (the keys of the hidden lanes).
 */
export function initBoard() {
    const board = document.querySelector('[data-board]');
    if (!board) {
        return;
    }

    const pane = initPane(board);
    initLaneFilter(board, pane);
}

function initPane(board) {
    const element = document.getElementById('board-pane');
    const pane = window.bootstrap.Offcanvas.getOrCreateInstance(element);
    const body = element.querySelector('[data-pane-body]');
    const steps = element.querySelectorAll('[data-step]');
    let current = null;

    const laneCards = (card) => [...board.querySelectorAll(`.board-cell[data-lane="${CSS.escape(card.parentElement.dataset.lane)}"] .board-card`)];

    const open = (card) => {
        const details = document.getElementById(card.dataset.card);
        current?.setAttribute('aria-expanded', 'false');
        current = card;
        card.setAttribute('aria-expanded', 'true');

        element.style.setProperty('--lane-color', card.style.getPropertyValue('--lane-color'));
        element.querySelector('[data-pane-title]').textContent = details.dataset.title;
        element.querySelector('[data-pane-date]').textContent = details.dataset.date;
        body.replaceChildren(details.content.cloneNode(true));
        body.scrollTop = 0;

        const cards = laneCards(card);
        const index = cards.indexOf(card);
        steps.forEach((step) => {
            step.disabled = !cards[step.dataset.step === 'newer' ? index - 1 : index + 1];
        });

        pane.show();
        // Bootstrap marks every offcanvas modal, but this one leaves the page usable: a non-modal dialog.
        element.removeAttribute('aria-modal');
    };

    board.addEventListener('click', (event) => {
        const card = event.target.closest('.board-card');
        if (card) {
            open(card);
            element.focus({ preventScroll: true });
        }
    });

    steps.forEach((step) => step.addEventListener('click', () => {
        const cards = laneCards(current);
        const index = cards.indexOf(current);
        const next = cards[step.dataset.step === 'newer' ? index - 1 : index + 1];
        if (!next) {
            return;
        }

        open(next);
        next.scrollIntoView({ block: 'nearest' });
        if (step.disabled) {
            element.focus({ preventScroll: true });
        }
    }));

    body.addEventListener('click', (event) => {
        const button = event.target.closest('[data-copy]');
        if (button) {
            copy(button, element.dataset);
        }
    });

    // Bootstrap closes on Escape only while focus is inside the pane; this covers a click that left it on the page.
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && current && !element.contains(event.target) && !document.querySelector('.dropdown-menu.show')) {
            pane.hide();
        }
    });

    element.addEventListener('hidden.bs.offcanvas', () => {
        if (!current) {
            return;
        }

        current.setAttribute('aria-expanded', 'false');
        // Bootstrap blurs the pane as it closes: give focus back to the card it came from.
        if (document.activeElement === document.body && !current.closest('[hidden]')) {
            current.focus({ preventScroll: true });
        }
        current = null;
    });

    return {
        closeIfHidden: () => {
            if (current?.closest('[hidden]')) {
                pane.hide();
            }
        },
    };
}

async function copy(button, labels) {
    const label = button.textContent;
    try {
        await writeClipboard(button.dataset.copy);
        button.textContent = labels.copied;
        setTimeout(() => {
            button.textContent = label;
        }, 1500);
    } catch {
        window.toast(labels.copyFailed, 'danger');
    }
    button.focus();
}

function writeClipboard(text) {
    if (window.isSecureContext && navigator.clipboard) {
        return navigator.clipboard.writeText(text);
    }

    // Plain http (http://shiplog.local) has no Clipboard API, so copy through a hidden field.
    const field = document.createElement('textarea');
    field.value = text;
    field.readOnly = true;
    field.className = 'visually-hidden';
    document.body.append(field);
    field.select();
    const copied = document.execCommand('copy');
    field.remove();

    return copied ? Promise.resolve() : Promise.reject(new Error('The browser refused to copy.'));
}

function initLaneFilter(board, pane) {
    const filter = document.querySelector('[data-lane-filter]');
    const lanes = [...filter.querySelectorAll('[data-filter-lane]')];
    const all = filter.querySelector('[data-filter-all]');
    const label = filter.querySelector('[data-filter-label]');
    const none = document.querySelector('[data-board-none]');
    const quiet = document.querySelector('[data-board-quiet]');

    const apply = () => {
        const shown = lanes.filter((lane) => lane.checked);
        const hidden = lanes.filter((lane) => !lane.checked).map((lane) => lane.value);

        board.querySelectorAll('.board-lane, .board-cell').forEach((cell) => {
            cell.hidden = hidden.includes(cell.dataset.lane);
        });
        board.style.setProperty('--board-lanes', String(shown.length));

        let days = 0;
        board.querySelectorAll('[data-day]').forEach((day) => {
            day.hidden = !day.querySelector('.board-cell:not([hidden]) .board-card');
            days += day.hidden ? 0 : 1;
        });
        board.hidden = shown.length === 0 || days === 0;
        none.hidden = shown.length > 0;
        quiet.hidden = shown.length === 0 || days > 0;

        all.checked = shown.length === lanes.length;
        all.indeterminate = shown.length > 0 && shown.length < lanes.length;
        label.textContent = shown.length === lanes.length ? label.dataset.all
            : shown.length === 0 ? label.dataset.none
                : shown.map((lane) => lane.dataset.name).join(', ');

        pane.closeIfHidden();

        return hidden;
    };

    const save = (hidden) => {
        try {
            hidden.length > 0 ? localStorage.setItem('board-lanes', JSON.stringify(hidden)) : localStorage.removeItem('board-lanes');
        } catch {
            // Storage is blocked: the filter holds until the page reloads.
        }
    };

    filter.addEventListener('change', (event) => {
        if (event.target === all) {
            lanes.forEach((lane) => {
                lane.checked = all.checked;
            });
        }
        save(apply());
    });

    const saved = savedHiddenLanes();
    lanes.forEach((lane) => {
        lane.checked = !saved.includes(lane.value);
    });
    apply();
}

function savedHiddenLanes() {
    try {
        const saved = JSON.parse(localStorage.getItem('board-lanes') ?? '[]');
        return Array.isArray(saved) ? saved : [];
    } catch {
        return [];
    }
}
