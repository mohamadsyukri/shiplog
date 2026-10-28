/**
 * The board's figures (modules/board/_partials/figures): four tiles and two charts for the chosen period
 * (Last 14 / 30 days, remembered under 'board-period') and the lanes the Lanes filter shows ('board:lanes'
 * from board.js). The data is window.boardFigures (BoardService::figures): each of the last 60 days with the
 * sources of each lane's card, so a lane has at most one card a day. The charts are plain SVG; every number
 * in them is also in the table view, so none lives only in a tooltip.
 */
const SVG = 'http://www.w3.org/2000/svg';
const GAP = 2;
const PLOT_HEIGHT = 150;
const AXIS_HEIGHT = 22;
const GUTTER = 24;

export function initBoardFigures() {
    const section = document.querySelector('[data-board-figures]');
    const data = window.boardFigures;
    if (!section || !data) {
        return;
    }

    const text = section.dataset;
    const periodButtons = document.querySelectorAll('[data-period]');
    const chart = section.querySelector('[data-chart]');
    // board.js ran first and has put the saved filter on the boxes.
    let shown = [...document.querySelectorAll('[data-filter-lane]')].filter((lane) => lane.checked).map((lane) => lane.value);
    let period = savedPeriod(periodButtons);
    let view = null;

    const render = () => {
        view = summarise(data, period, shown);
        periodButtons.forEach((button) => button.setAttribute('aria-pressed', String(Number(button.dataset.period) === period)));
        renderTiles(section, view, text, period);
        renderLaneBars(section, view, text);
        renderTable(section, view, text);
        renderLegend(section, view);
        section.querySelector('[data-range]').textContent = fill(text.rangeLabel, { from: dmy(view.days[0].date), to: dmy(view.days.at(-1).date) });
        section.querySelector('[data-period-label]').textContent = [...periodButtons].find((button) => Number(button.dataset.period) === period)?.textContent ?? '';
        renderChart(chart, view, text);
    };

    periodButtons.forEach((button) => button.addEventListener('click', () => {
        period = Number(button.dataset.period);
        try {
            localStorage.setItem('board-period', String(period));
        } catch {
            // Storage is blocked: the period holds until the page reloads.
        }
        render();
    }));

    document.addEventListener('board:lanes', (event) => {
        shown = event.detail.shown;
        render();
    });

    // The chart is drawn at its real width, so a 4px corner stays 4px: redraw when the card changes size.
    let width = chart.clientWidth;
    new ResizeObserver(() => {
        if (chart.clientWidth !== width) {
            width = chart.clientWidth;
            renderChart(chart, view, text);
        }
    }).observe(chart);

    render();
}

function savedPeriod(buttons) {
    const periods = [...buttons].map((button) => Number(button.dataset.period));
    try {
        const saved = Number(localStorage.getItem('board-period'));
        if (periods.includes(saved)) {
            return saved;
        }
    } catch {
        // Storage is blocked: fall back to the longest period.
    }

    return Math.max(...periods);
}

/** The period's days (oldest first) and the one before it, counted over the shown lanes only. */
function summarise(data, period, shown) {
    const lanes = data.lanes.filter((lane) => shown.includes(lane.key));
    const count = (days) => {
        const totals = { worked: 0, cards: 0, sources: 0, byLane: {}, sourcesByLane: {} };
        days.forEach((day) => {
            const cards = lanes.filter((lane) => day.sources[lane.key] !== undefined);
            totals.worked += cards.length > 0 ? 1 : 0;
            totals.cards += cards.length;
            cards.forEach((lane) => {
                totals.sources += day.sources[lane.key];
                totals.byLane[lane.key] = (totals.byLane[lane.key] ?? 0) + 1;
                totals.sourcesByLane[lane.key] = (totals.sourcesByLane[lane.key] ?? 0) + day.sources[lane.key];
            });
        });

        return totals;
    };
    const days = data.days.slice(-period);

    return { lanes, days, current: count(days), previous: count(data.days.slice(-2 * period, -period)) };
}

function renderTiles(section, view, text, period) {
    const { current, previous, days, lanes } = view;
    const tile = (name) => section.querySelector(`[data-kpi="${name}"]`);

    setValue(tile('days'), String(current.worked), fill(text.of, { days: period }));
    setValue(tile('cards'), String(current.cards));
    setValue(tile('sources'), current.sources.toLocaleString());
    setDelta(tile('days'), current.worked - previous.worked, text, period);
    setDelta(tile('cards'), current.cards - previous.cards, text, period);
    setDelta(tile('sources'), current.sources - previous.sources, text, period);

    const perDay = days.map((day) => lanes.filter((lane) => day.sources[lane.key] !== undefined));
    spark(tile('days').querySelector('[data-spark]'), perDay.map((cards) => (cards.length > 0 ? 1 : 0)), 'strip');
    spark(tile('cards').querySelector('[data-spark]'), perDay.map((cards) => cards.length), 'line');
    spark(tile('sources').querySelector('[data-spark]'), days.map((day) => lanes.reduce((sum, lane) => sum + (day.sources[lane.key] ?? 0), 0)), 'line');

    // Most cards; a tie goes to more sources, then to lane order.
    const busiest = lanes
        .filter((lane) => current.byLane[lane.key])
        .sort((a, b) => (current.byLane[b.key] - current.byLane[a.key]) || (current.sourcesByLane[b.key] - current.sourcesByLane[a.key]))[0];
    const value = tile('busiest').querySelector('[data-value]');
    const detail = tile('busiest').querySelector('[data-delta]');
    value.replaceChildren();
    if (busiest) {
        const name = document.createElement('span');
        name.className = 'text-truncate';
        name.textContent = busiest.name;
        value.append(swatch(busiest.color), name);
        detail.textContent = fill(text.share, { percent: Math.round((100 * current.byLane[busiest.key]) / current.cards) });
    } else {
        value.textContent = '–';
        detail.textContent = lanes.length > 0 ? text.noCards : text.noLanes;
    }
}

function setValue(tile, value, suffix = null) {
    const element = tile.querySelector('[data-value]');
    element.textContent = value;
    if (suffix) {
        const small = document.createElement('small');
        small.textContent = suffix;
        element.append(' ', small);
    }
}

// The change against the period before, in plain ink: more work is not better or worse, only more.
function setDelta(tile, change, text, period) {
    const element = tile.querySelector('[data-delta]');
    element.replaceChildren();
    if (change !== 0) {
        const icon = document.createElement('i');
        icon.className = `bi ${change > 0 ? 'bi-arrow-up-short' : 'bi-arrow-down-short'}`;
        icon.setAttribute('aria-hidden', 'true');
        element.append(icon);
    }
    element.append(document.createTextNode(fill(change > 0 ? text.more : change < 0 ? text.fewer : text.same, { count: Math.abs(change).toLocaleString(), days: period })));
}

/** A tile's trend: a line, or for days with work a strip of short bars. Stretched to the tile's width. */
function spark(svg, values, kind) {
    const height = 28;
    const max = Math.max(1, ...values);
    svg.setAttribute('viewBox', `0 0 ${values.length} ${height}`);
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.replaceChildren();

    if (kind === 'strip') {
        values.forEach((value, index) => svg.append(node('rect', {
            x: index + 0.15, y: value ? 4 : height - 4, width: 0.7, height: value ? height - 4 : 4,
            class: value ? 'board-spark-on' : 'board-spark-off',
        })));
        return;
    }

    const points = values.map((value, index) => `${index + 0.5},${height - 2 - ((height - 4) * value) / max}`).join(' ');
    svg.append(node('polyline', { points, class: 'board-spark-line' }));
}

function renderLegend(section, view) {
    const legend = section.querySelector('[data-legend]');
    legend.replaceChildren();
    // One lane needs no key: the chart's title already says what it plots.
    if (view.lanes.length < 2) {
        return;
    }
    view.lanes.forEach((lane) => {
        const item = document.createElement('span');
        item.append(swatch(lane.color), document.createTextNode(lane.name));
        legend.append(item);
    });
}

function renderLaneBars(section, view, text) {
    const list = section.querySelector('[data-lanebars]');
    list.replaceChildren();
    if (view.lanes.length === 0) {
        const empty = document.createElement('li');
        empty.className = 'board-chart-empty';
        empty.textContent = text.noLanes;
        list.append(empty);
        return;
    }

    const max = Math.max(1, ...view.lanes.map((lane) => view.current.byLane[lane.key] ?? 0));
    view.lanes.forEach((lane) => {
        const cards = view.current.byLane[lane.key] ?? 0;
        const item = document.createElement('li');
        const name = document.createElement('span');
        name.className = 'board-lanebar-name';
        name.append(swatch(lane.color), document.createTextNode(lane.name));
        const value = document.createElement('span');
        value.className = 'board-lanebar-value';
        value.textContent = String(cards);
        const track = document.createElement('span');
        track.className = 'board-lanebar-track';
        const bar = document.createElement('span');
        bar.style.width = `${(100 * cards) / max}%`;
        bar.style.backgroundColor = lane.color;
        track.append(bar);
        item.append(name, value, track);
        list.append(item);
    });
}

function renderTable(section, view, text) {
    const table = document.createElement('table');
    table.className = 'table table-sm mb-0';
    const head = table.createTHead().insertRow();
    [text.dayLabel, ...view.lanes.map((lane) => lane.name)].forEach((label) => {
        const cell = document.createElement('th');
        cell.scope = 'col';
        cell.textContent = label;
        head.append(cell);
    });

    const body = table.createTBody();
    [...view.days].reverse().forEach((day) => {
        const row = body.insertRow();
        const date = document.createElement('th');
        date.scope = 'row';
        date.textContent = `${weekday(day.date)} ${dmy(day.date)}`;
        row.append(date);
        view.lanes.forEach((lane) => {
            const sources = day.sources[lane.key];
            row.insertCell().textContent = sources === undefined ? text.noCard : sourcesText(sources, text);
        });
    });

    section.querySelector('[data-table]').replaceChildren(table);
}

/** Stacked columns: one 1-high segment per lane with a card, in lane order from the baseline up. */
function renderChart(chart, view, text) {
    if (!view) {
        return;
    }

    chart.replaceChildren();
    const width = chart.clientWidth;
    // No room to draw (the page is being laid out or torn down); the next resize draws it.
    if (width <= GUTTER) {
        return;
    }
    const days = view.days;
    const totals = days.map((day) => view.lanes.filter((lane) => day.sources[lane.key] !== undefined));
    const top = Math.max(1, ...totals.map((cards) => cards.length));
    const unit = PLOT_HEIGHT / top;
    const slot = (width - GUTTER) / days.length;
    const barWidth = Math.max(2, Math.min(24, slot * 0.6));
    const base = PLOT_HEIGHT + 4;

    const svg = node('svg', { width, height: base + AXIS_HEIGHT, class: 'board-chart-svg', 'aria-hidden': 'true' });

    // Hairline gridlines at whole cards; every step up to four, else every other one.
    const step = top <= 4 ? 1 : 2;
    for (let tick = 0; tick <= top; tick += step) {
        const y = base - tick * unit + 0.5;
        svg.append(node('line', { x1: GUTTER, x2: width, y1: y, y2: y, class: 'board-chart-grid' }));
        svg.append(textNode(String(tick), { x: GUTTER - 6, y: y + 4, 'text-anchor': 'end', class: 'board-chart-tick' }));
    }

    const bands = [];
    days.forEach((day, index) => {
        const x = GUTTER + index * slot;
        const band = node('rect', { x, y: 0, width: slot, height: base, class: 'board-chart-band' });
        svg.append(band);
        bands.push(band);

        totals[index].forEach((lane, level) => {
            const isTop = level === totals[index].length - 1;
            const y = base - (level + 1) * unit;
            // A 2px gap in the surface colour under every segment but the bottom one.
            const height = unit - (level > 0 ? GAP : 0);
            const barX = x + (slot - barWidth) / 2;
            svg.append(isTop
                ? node('path', { d: roundedTop(barX, y, barWidth, height, Math.min(4, barWidth / 2, height)), fill: lane.color })
                : node('rect', { x: barX, y, width: barWidth, height, fill: lane.color }));
        });
    });

    // Dates under the first, middle and last columns.
    [0, Math.floor((days.length - 1) / 2), days.length - 1].forEach((index, position) => {
        const x = GUTTER + index * slot + slot / 2;
        svg.append(textNode(dm(days[index].date), {
            x, y: base + 16, 'text-anchor': ['start', 'middle', 'end'][position], class: 'board-chart-tick',
            dx: position === 0 ? -slot / 2 : position === 2 ? slot / 2 : 0,
        }));
    });

    const tip = document.createElement('div');
    tip.className = 'board-tip';
    tip.hidden = true;
    chart.append(svg, tip);
    chart.setAttribute('aria-label', fill(text.chartLabel, { from: dmy(days[0].date), to: dmy(days.at(-1).date) }));

    let active = null;
    const show = (index) => {
        if (active !== null) {
            bands[active].classList.remove('is-active');
        }
        active = index;
        bands[index].classList.add('is-active');
        fillTip(tip, days[index], totals[index], text);
        tip.hidden = false;
        const center = GUTTER + index * slot + slot / 2;
        tip.style.left = `${Math.min(Math.max(center - tip.offsetWidth / 2, 0), width - tip.offsetWidth)}px`;
    };
    const hide = () => {
        if (active !== null) {
            bands[active].classList.remove('is-active');
        }
        active = null;
        tip.hidden = true;
    };

    chart.onpointermove = (event) => {
        const index = Math.floor((event.clientX - chart.getBoundingClientRect().left - GUTTER) / slot);
        if (index >= 0 && index < days.length && index !== active) {
            show(index);
        }
    };
    chart.onpointerleave = hide;
    // The keyboard gets the same readout: arrows walk the days.
    chart.onfocus = () => show(days.length - 1);
    chart.onblur = hide;
    chart.onkeydown = (event) => {
        const moves = { ArrowLeft: -1, ArrowRight: 1, Home: -days.length, End: days.length };
        if (event.key === 'Escape') {
            hide();
        } else if (moves[event.key] !== undefined) {
            event.preventDefault();
            show(Math.min(days.length - 1, Math.max(0, (active ?? days.length - 1) + moves[event.key])));
        }
    };
}

function fillTip(tip, day, cards, text) {
    const heading = document.createElement('strong');
    heading.textContent = `${weekday(day.date)} ${dmy(day.date)}`;
    const rows = cards.map((lane) => {
        const row = document.createElement('span');
        const key = document.createElement('i');
        key.style.backgroundColor = lane.color;
        const value = document.createElement('b');
        value.textContent = sourcesText(day.sources[lane.key], text);
        row.append(key, value, document.createTextNode(lane.name));
        return row;
    });
    if (rows.length === 0) {
        const none = document.createElement('span');
        none.textContent = text.noCard;
        rows.push(none);
    }
    tip.replaceChildren(heading, ...rows);
}

function roundedTop(x, y, width, height, radius) {
    return `M${x},${y + height}V${y + radius}Q${x},${y} ${x + radius},${y}H${x + width - radius}Q${x + width},${y} ${x + width},${y + radius}V${y + height}Z`;
}

function node(name, attributes) {
    const element = document.createElementNS(SVG, name);
    Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, String(value)));
    return element;
}

function textNode(content, attributes) {
    const element = node('text', attributes);
    element.textContent = content;
    return element;
}

function swatch(color) {
    const element = document.createElement('span');
    element.className = 'board-swatch';
    element.style.setProperty('--lane-color', color);
    element.setAttribute('aria-hidden', 'true');
    return element;
}

const fill = (template, values) => Object.entries(values).reduce((result, [key, value]) => result.replaceAll(`:${key}`, String(value)), template);
const sourcesText = (count, text) => fill(count === 1 ? text.sourceOne : text.sourceMany, { count });

// Dates stay Y-m-d strings until shown, so no time zone can move them a day.
const dmy = (date) => date.split('-').reverse().join('/');
const dm = (date) => date.split('-').reverse().slice(0, 2).join('/');
const weekday = (date) => new Intl.DateTimeFormat(document.documentElement.lang || 'en', { weekday: 'short', timeZone: 'UTC' })
    .format(new Date(`${date}T00:00:00Z`));
