/**
 * Bookingistic — admin scripts.
 * Calendar (month/week/day/list), booking detail modal, manual booking helpers.
 */
(function () {
	'use strict';

	if (typeof window.BookingisticAdmin === 'undefined') return;
	const cfg = window.BookingisticAdmin;
	const i18n = cfg.i18n || {};

	function pad(n) { return String(n).padStart(2, '0'); }
	function ymd(d) { return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }
	function startOfWeek(d) {
		const x = new Date(d);
		const dow = (x.getDay() + 6) % 7; // Mon = 0
		x.setDate(x.getDate() - dow);
		x.setHours(0,0,0,0);
		return x;
	}
	function addDays(d, n) { const x = new Date(d); x.setDate(x.getDate() + n); return x; }
	function addMonths(d, n) { const x = new Date(d); x.setMonth(x.getMonth() + n); return x; }
	function fmtTime(date) { return date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }); }

	function escapeHtml(s) {
		return String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
	}

	/* ===== Manual booking: customer email auto-fill ===== */
	function bindManualBookingForm() {
		const form = document.querySelector('[data-bookingistic-manual]');
		if (!form) return;

		const serviceSelect = form.querySelector('select[name="service_id"]');
		const dateInput = form.querySelector('input[name="booking_date"]');
		const slotSelect = form.querySelector('select[name="start_utc"]');
		const tzInput = form.querySelector('input[name="timezone"]');

		try { tzInput.value = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) {}

		async function loadSlots() {
			if (!serviceSelect.value || !dateInput.value) return;
			slotSelect.disabled = true;
			slotSelect.innerHTML = `<option>${escapeHtml(i18n.loading || 'Loading…')}</option>`;
			const url = `${cfg.restUrl}availability?service_id=${encodeURIComponent(serviceSelect.value)}&date=${encodeURIComponent(dateInput.value)}&tz=${encodeURIComponent(tzInput.value)}`;
			try {
				const res = await fetch(url, { headers: { 'X-WP-Nonce': cfg.nonce } });
				const data = await res.json();
				slotSelect.innerHTML = '';
				if (!data.slots || !data.slots.length) {
					slotSelect.innerHTML = `<option value="">${escapeHtml('No times available')}</option>`;
				} else {
					for (const slot of data.slots) {
						const local = new Date(slot.start_utc.replace(' ', 'T') + 'Z');
						const opt = document.createElement('option');
						opt.value = slot.start_utc;
						opt.textContent = fmtTime(local);
						slotSelect.appendChild(opt);
					}
				}
			} catch (e) {
				slotSelect.innerHTML = `<option value="">${escapeHtml(i18n.errorGeneric || 'Error')}</option>`;
			}
			slotSelect.disabled = false;
		}

		serviceSelect.addEventListener('change', loadSlots);
		dateInput.addEventListener('change', loadSlots);
	}

	/* ===== Calendar ===== */
	const STATUS_COLORS = {
		pending:     { bg: '#fef3c7', fg: '#92400e' },
		confirmed:   { bg: '#d1fae5', fg: '#065f46' },
		rescheduled: { bg: '#dbeafe', fg: '#1e3a8a' },
		completed:   { bg: '#e5e7eb', fg: '#374151' },
		cancelled:   { bg: '#fee2e2', fg: '#991b1b' },
		no_show:     { bg: '#fde68a', fg: '#78350f' },
	};

	function bindCalendar() {
		const root = document.querySelector('[data-bookingistic-calendar]');
		if (!root) return;

		const titleEl = document.querySelector('[data-bookingistic-calendar-title]');
		const filters = {};
		document.querySelectorAll('[data-bookingistic-calendar-filter]').forEach(sel => {
			filters[sel.dataset.bookingisticCalendarFilter] = '';
			sel.addEventListener('change', () => {
				filters[sel.dataset.bookingisticCalendarFilter] = sel.value;
				render();
			});
		});

		const state = {
			view: 'month',
			cursor: new Date(),
			events: [],
		};

		document.querySelectorAll('[data-bookingistic-calendar-view]').forEach(btn => {
			btn.addEventListener('click', () => {
				state.view = btn.dataset.bookingisticCalendarView;
				document.querySelectorAll('[data-bookingistic-calendar-view]').forEach(b => {
					b.classList.toggle('button-primary', b === btn);
				});
				render();
			});
		});
		document.querySelector('[data-bookingistic-calendar-prev]').addEventListener('click', () => { shift(-1); });
		document.querySelector('[data-bookingistic-calendar-next]').addEventListener('click', () => { shift(1); });
		document.querySelector('[data-bookingistic-calendar-today]').addEventListener('click', () => {
			state.cursor = new Date(); render();
		});

		function shift(dir) {
			if (state.view === 'month') state.cursor = addMonths(state.cursor, dir);
			else if (state.view === 'week') state.cursor = addDays(state.cursor, 7 * dir);
			else if (state.view === 'day' || state.view === 'list') state.cursor = addDays(state.cursor, dir);
			render();
		}

		function rangeForCurrentView() {
			if (state.view === 'month') {
				const first = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), 1);
				const last = new Date(state.cursor.getFullYear(), state.cursor.getMonth()+1, 0);
				return [first, last];
			}
			if (state.view === 'week') {
				const start = startOfWeek(state.cursor);
				return [start, addDays(start, 6)];
			}
			if (state.view === 'day') return [state.cursor, state.cursor];
			// list: 30-day window starting today
			return [state.cursor, addDays(state.cursor, 29)];
		}

		async function fetchEvents() {
			const [from, to] = rangeForCurrentView();
			const params = new URLSearchParams({ from: ymd(from), to: ymd(to) });
			for (const [k, v] of Object.entries(filters)) if (v) params.append(k, v);
			root.innerHTML = `<div class="bookingistic-calendar__loading">${escapeHtml(i18n.loading)}</div>`;
			try {
				const res = await fetch(`${cfg.restUrl}calendar/events?${params}`, { headers: { 'X-WP-Nonce': cfg.nonce } });
				const data = await res.json();
				if (!res.ok) throw new Error(data?.message || i18n.errorGeneric);
				state.events = data.events || [];
				draw();
			} catch (e) {
				root.innerHTML = `<div class="bookingistic-calendar__error">${escapeHtml(e.message || i18n.errorGeneric)}</div>`;
			}
		}

		function eventsForDay(date) {
			const key = ymd(date);
			return state.events.filter(e => (e.start || '').slice(0, 10) === key);
		}

		function eventBadge(e) {
			const c = STATUS_COLORS[e.status] || STATUS_COLORS.pending;
			const start = e.start ? new Date(e.start) : null;
			const time = start ? fmtTime(start) : '';
			return `<button type="button" class="bookingistic-calendar__event"
				data-bookingistic-event="${e.id}"
				style="background:${c.bg};color:${c.fg}"
				title="${escapeHtml(e.customer_name + ' · ' + e.service_name)}">
				<span class="bookingistic-calendar__event-time">${escapeHtml(time)}</span>
				<span class="bookingistic-calendar__event-title">${escapeHtml(e.customer_name || e.service_name)}</span>
			</button>`;
		}

		function draw() {
			if (state.view === 'month') drawMonth();
			else if (state.view === 'week') drawWeek();
			else if (state.view === 'day') drawDay();
			else drawList();
			root.querySelectorAll('[data-bookingistic-event]').forEach(btn => {
				btn.addEventListener('click', () => openDetail(parseInt(btn.dataset.bookingisticEvent, 10)));
			});
		}

		function drawMonth() {
			const y = state.cursor.getFullYear();
			const m = state.cursor.getMonth();
			const first = new Date(y, m, 1);
			const startDow = (first.getDay() + 6) % 7;
			const daysInMonth = new Date(y, m+1, 0).getDate();
			titleEl.textContent = state.cursor.toLocaleString(undefined, { month: 'long', year: 'numeric' });
			let html = `<div class="bookingistic-calendar__grid bookingistic-calendar__grid--month">`;
			for (const d of i18n.dows) html += `<div class="bookingistic-calendar__dow">${escapeHtml(d)}</div>`;
			for (let i = 0; i < startDow; i++) html += `<div class="bookingistic-calendar__cell bookingistic-calendar__cell--blank"></div>`;
			const today = ymd(new Date());
			for (let day = 1; day <= daysInMonth; day++) {
				const dt = new Date(y, m, day);
				const evts = eventsForDay(dt);
				const isToday = ymd(dt) === today;
				html += `<div class="bookingistic-calendar__cell${isToday ? ' is-today' : ''}">
					<header><span>${day}</span><span class="bookingistic-muted">${evts.length || ''}</span></header>
					<div class="bookingistic-calendar__cell-events">${evts.map(eventBadge).join('')}</div>
				</div>`;
			}
			html += `</div>`;
			root.innerHTML = html;
		}

		function drawWeek() {
			const start = startOfWeek(state.cursor);
			titleEl.textContent = `${start.toLocaleDateString(undefined, { month:'short', day:'numeric' })} – ${addDays(start, 6).toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' })}`;
			let html = `<div class="bookingistic-calendar__grid bookingistic-calendar__grid--week">`;
			for (let i = 0; i < 7; i++) {
				const dt = addDays(start, i);
				const evts = eventsForDay(dt);
				html += `<div class="bookingistic-calendar__col">
					<header>
						<span>${escapeHtml(i18n.dows[i])}</span>
						<span class="bookingistic-calendar__col-date">${dt.getDate()}</span>
					</header>
					<div class="bookingistic-calendar__col-events">${evts.map(eventBadge).join('') || `<div class="bookingistic-muted">—</div>`}</div>
				</div>`;
			}
			html += `</div>`;
			root.innerHTML = html;
		}

		function drawDay() {
			const dt = state.cursor;
			titleEl.textContent = dt.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
			const evts = eventsForDay(dt).sort((a, b) => (a.start || '').localeCompare(b.start || ''));
			if (!evts.length) {
				root.innerHTML = `<div class="bookingistic-card"><p class="bookingistic-muted">${escapeHtml(i18n.noEvents)}</p></div>`;
				return;
			}
			let html = `<div class="bookingistic-calendar__day">`;
			for (const e of evts) {
				const c = STATUS_COLORS[e.status] || STATUS_COLORS.pending;
				const start = new Date(e.start);
				const end = e.end ? new Date(e.end) : null;
				html += `<button type="button" class="bookingistic-calendar__day-event" data-bookingistic-event="${e.id}" style="border-left-color:${c.fg}">
					<span class="bookingistic-calendar__day-time">${escapeHtml(fmtTime(start))}${end ? ' – ' + escapeHtml(fmtTime(end)) : ''}</span>
					<span class="bookingistic-calendar__day-title">${escapeHtml(e.customer_name)} · ${escapeHtml(e.service_name)}</span>
					<span class="bookingistic-pill bookingistic-pill--${escapeHtml(e.status)}">${escapeHtml(e.status)}</span>
				</button>`;
			}
			html += `</div>`;
			root.innerHTML = html;
		}

		function drawList() {
			titleEl.textContent = `${state.cursor.toLocaleDateString(undefined, { month:'short', day:'numeric' })} – ${addDays(state.cursor, 29).toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' })}`;
			const evts = state.events.slice().sort((a, b) => (a.start || '').localeCompare(b.start || ''));
			if (!evts.length) {
				root.innerHTML = `<div class="bookingistic-card"><p class="bookingistic-muted">${escapeHtml(i18n.noEvents)}</p></div>`;
				return;
			}
			let html = `<table class="widefat striped"><thead><tr>
				<th>When</th><th>Customer</th><th>Service</th><th>Status</th><th>Payment</th><th></th>
			</tr></thead><tbody>`;
			for (const e of evts) {
				const start = new Date(e.start);
				html += `<tr>
					<td>${escapeHtml(start.toLocaleString())}</td>
					<td>${escapeHtml(e.customer_name)}</td>
					<td>${escapeHtml(e.service_name)}</td>
					<td><span class="bookingistic-pill bookingistic-pill--${escapeHtml(e.status)}">${escapeHtml(e.status)}</span></td>
					<td>${escapeHtml(e.payment_status)}</td>
					<td><button type="button" class="button" data-bookingistic-event="${e.id}">Open</button></td>
				</tr>`;
			}
			html += `</tbody></table>`;
			root.innerHTML = html;
		}

		function render() { fetchEvents(); }
		render();
	}

	/* ===== Booking detail modal ===== */
	function openDetail(bookingId) {
		const modal = document.querySelector('[data-bookingistic-modal]');
		if (!modal) {
			window.location.href = `${cfg.bookingsUrl}&view=${bookingId}`;
			return;
		}
		const body = modal.querySelector('[data-bookingistic-modal-body]');
		body.innerHTML = `<p class="bookingistic-muted">${i18n.loading || 'Loading…'}</p>`;
		modal.hidden = false;

		fetch(`${cfg.restUrl}bookings/${bookingId}`, { headers: { 'X-WP-Nonce': cfg.nonce } })
			.then(r => r.json())
			.then(b => {
				const start = b.start_datetime ? new Date(b.start_datetime.replace(' ', 'T') + 'Z') : null;
				const end = b.end_datetime ? new Date(b.end_datetime.replace(' ', 'T') + 'Z') : null;
				body.innerHTML = `
					<h2>${escapeHtml(b.service?.name || 'Booking')} <span class="bookingistic-pill bookingistic-pill--${escapeHtml(b.status)}">${escapeHtml(b.status)}</span></h2>
					<dl class="bookingistic-detail">
						<dt>When</dt><dd>${start ? escapeHtml(start.toLocaleString()) : '—'}${end ? ' – ' + escapeHtml(end.toLocaleString()) : ''}</dd>
						<dt>Customer</dt><dd><strong>${escapeHtml(b.customer?.full_name || '')}</strong><br><a href="mailto:${escapeHtml(b.customer?.email || '')}">${escapeHtml(b.customer?.email || '')}</a></dd>
						<dt>Phone</dt><dd>${escapeHtml(b.customer?.phone || '—')}</dd>
						<dt>Source</dt><dd>${escapeHtml(b.source || '—')}</dd>
						<dt>Notes</dt><dd>${escapeHtml(b.notes || '—')}</dd>
						<dt>Internal</dt><dd>${escapeHtml(b.internal_notes || '—')}</dd>
					</dl>
					<p>
						<a class="button button-primary" href="${cfg.bookingsUrl}&view=${b.id}">Open full record</a>
						<a class="button" href="${cfg.customersUrl}&view=${b.customer_id}">View customer</a>
					</p>
				`;
			})
			.catch(() => {
				body.innerHTML = `<p class="bookingistic-muted">${i18n.errorGeneric}</p>`;
			});
	}

	function bindModal() {
		const modal = document.querySelector('[data-bookingistic-modal]');
		if (!modal) return;
		modal.querySelectorAll('[data-bookingistic-modal-close]').forEach(el => {
			el.addEventListener('click', () => { modal.hidden = true; });
		});
		document.addEventListener('keydown', e => {
			if (e.key === 'Escape' && !modal.hidden) modal.hidden = true;
		});
	}

	/* ===== Repeatable date-range rows (staff days off, settings holidays) ===== */
	function bindDateRangeRepeaters() {
		document.querySelectorAll('[data-bookingistic-daterange]').forEach(table => {
			const fieldName = table.dataset.bookingisticDaterange; // e.g. "days_off" or "holidays"
			const rowsHost  = table.querySelector('[data-bookingistic-daterange-rows]');
			if (!rowsHost) return;

			function attachRowHandlers(row) {
				const removeBtn = row.querySelector('[data-bookingistic-daterange-remove]');
				if (removeBtn) {
					removeBtn.addEventListener('click', () => {
						const allRows = rowsHost.querySelectorAll('tr');
						if (allRows.length > 1) {
							row.remove();
						} else {
							row.querySelectorAll('input').forEach(i => { i.value = ''; });
						}
					});
				}
			}

			rowsHost.querySelectorAll('tr').forEach(attachRowHandlers);

			const addBtn = table.querySelector('[data-bookingistic-daterange-add]');
			if (addBtn) {
				addBtn.addEventListener('click', () => {
					const idx = rowsHost.querySelectorAll('tr').length;
					const tr = document.createElement('tr');
					tr.innerHTML = `
						<td><input type="date" name="${fieldName}[${idx}][from]" value=""></td>
						<td><input type="date" name="${fieldName}[${idx}][to]" value=""></td>
						<td><button type="button" class="button-link-delete" data-bookingistic-daterange-remove>×</button></td>
					`;
					rowsHost.appendChild(tr);
					attachRowHandlers(tr);
				});
			}
		});

		// Legacy support for the staff days-off table that uses the older attribute names.
		document.querySelectorAll('[data-bookingistic-daysoff]').forEach(table => {
			const rowsHost = table.querySelector('[data-bookingistic-daysoff-rows]');
			if (!rowsHost) return;
			function attach(row) {
				const remove = row.querySelector('[data-bookingistic-daysoff-remove]');
				if (!remove) return;
				remove.addEventListener('click', () => {
					const all = rowsHost.querySelectorAll('tr');
					if (all.length > 1) row.remove(); else row.querySelectorAll('input').forEach(i => { i.value = ''; });
				});
			}
			rowsHost.querySelectorAll('tr').forEach(attach);
			const addBtn = table.querySelector('[data-bookingistic-daysoff-add]');
			if (addBtn) {
				addBtn.addEventListener('click', () => {
					const idx = rowsHost.querySelectorAll('tr').length;
					const tr = document.createElement('tr');
					tr.innerHTML = `
						<td><input type="date" name="days_off[${idx}][from]"></td>
						<td><input type="date" name="days_off[${idx}][to]"></td>
						<td><button type="button" class="button-link-delete" data-bookingistic-daysoff-remove>×</button></td>
					`;
					rowsHost.appendChild(tr);
					attach(tr);
				});
			}
		});
	}

	/* ===== Email automation builder ===== */
	function bindAutomationEditor() {
		const body = document.getElementById('bookingistic-automation-body');
		if (body) {
			document.querySelectorAll('[data-bookingistic-var]').forEach(btn => {
				btn.addEventListener('click', () => {
					const token = btn.dataset.bookingisticVar;
					const start = body.selectionStart ?? body.value.length;
					const end = body.selectionEnd ?? body.value.length;
					body.value = body.value.slice(0, start) + token + body.value.slice(end);
					const caret = start + token.length;
					body.focus();
					body.setSelectionRange(caret, caret);
				});
			});
		}

		const testBtn = document.querySelector('[data-bookingistic-test-send]');
		if (testBtn) {
			testBtn.addEventListener('click', async () => {
				const id = testBtn.dataset.automationId;
				const to = prompt('Send test email to:', '');
				if (to === null) return;
				testBtn.disabled = true;
				const original = testBtn.textContent;
				testBtn.textContent = i18n.loading || 'Sending…';
				try {
					const res = await fetch(`${cfg.restUrl}email-automations/${id}/test`, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
						body: JSON.stringify({ to }),
					});
					const data = await res.json();
					if (!res.ok || data.code) throw new Error(data.message || i18n.errorGeneric);
					alert(data.sent
						? `Test email sent to ${data.to}.`
						: `Could not send. Check Email Logs for details.`);
				} catch (e) {
					alert(e.message || i18n.errorGeneric);
				}
				testBtn.disabled = false;
				testBtn.textContent = original;
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => { bindCalendar(); bindModal(); bindManualBookingForm(); bindAutomationEditor(); bindDateRangeRepeaters(); });
	} else {
		bindCalendar(); bindModal(); bindManualBookingForm(); bindAutomationEditor(); bindDateRangeRepeaters();
	}
})();
