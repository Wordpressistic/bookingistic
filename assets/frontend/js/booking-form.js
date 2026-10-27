/**
 * Bookingistic — frontend booking form controller.
 * No dependencies, no network calls other than the plugin's own REST API. Vanilla ES6.
 *
 * Drives ALL form templates (classic / modern / split / minimal) and all
 * calendar views (month / pills / list) via the shared data-* DOM contract.
 * Optional hooks added in 2.6 (progress, status live region) are feature-
 * detected so theme overrides of older templates keep working.
 */
(function () {
	'use strict';

	if (typeof window.BookingisticConfig === 'undefined') return;

	const cfg = window.BookingisticConfig;

	// Build an endpoint URL that works under both permalink styles:
	// pretty  — https://site/wp-json/bookingistic/v1/
	// plain   — https://site/?rest_route=%2Fbookingistic%2Fv1%2F (already a query string,
	//           so extra params must join with '&' and the route path appends to the value).
	function apiUrl(path, params) {
		const base = cfg.restUrl + path;
		return params ? base + (base.indexOf('?') !== -1 ? '&' : '?') + params : base;
	}
	const i18n = cfg.i18n || {};

	const reduceMotion = () => !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

	function pad(n) { return String(n).padStart(2, '0'); }
	function fmtIsoDate(d) { return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; }
	function parseIso(iso) { const p = iso.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); }
	function esc(str) {
		return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
	}
	function fullDate(dt) { return dt.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }); }
	function sprintf1(tpl, val) { return String(tpl).replace('%s', val); }

	function detectTz() {
		try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; }
		catch (e) { return ''; }
	}

	/* Apply appearance (accent color / radius / font) as CSS variables on the form root. */
	function applyAppearance(root) {
		const app = cfg.appearance || {};
		if (app.accent && /^#[0-9a-f]{3,8}$/i.test(app.accent)) root.style.setProperty('--bk-accent', app.accent);
		if (app.radius !== undefined && app.radius !== '') root.style.setProperty('--bk-radius', parseInt(app.radius, 10) + 'px');
		if (app.font && app.font !== 'inherit' && /^[\w\s,"'-]+$/.test(app.font)) root.style.setProperty('--bk-font', app.font);
	}

	/* Keyboard: arrow keys move focus between enabled buttons in a set. */
	function arrowNav(container, selector, cols) {
		container.addEventListener('keydown', (e) => {
			const keys = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -(cols || 1), ArrowDown: cols || 1 };
			if (!(e.key in keys) && e.key !== 'Home' && e.key !== 'End') return;
			const items = Array.from(container.querySelectorAll(selector)).filter(b => !b.disabled);
			const idx = items.indexOf(document.activeElement);
			if (idx === -1) return;
			let next = idx;
			if (e.key === 'Home') next = 0;
			else if (e.key === 'End') next = items.length - 1;
			else next = Math.min(items.length - 1, Math.max(0, idx + keys[e.key]));
			if (cols && (e.key === 'ArrowUp' || e.key === 'ArrowDown')) {
				// Month grid: move by a calendar week using dates, not list index (disabled past days).
				const cur = parseIso(document.activeElement.dataset.calDay);
				cur.setDate(cur.getDate() + (e.key === 'ArrowUp' ? -7 : 7));
				const target = container.querySelector(`[data-cal-day="${fmtIsoDate(cur)}"]:not(:disabled)`);
				if (target) { e.preventDefault(); target.focus(); }
				return;
			}
			e.preventDefault();
			items[next].focus();
		});
	}

	/* Pressed-state helper with a spring "pop". */
	function press(group, btn) {
		group.forEach(b => {
			const on = b === btn;
			b.classList.toggle('is-selected', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		if (!reduceMotion()) {
			btn.classList.remove('is-popping');
			void btn.offsetWidth; // restart animation
			btn.classList.add('is-popping');
		}
	}

	/* ================= Calendar renderers ================= */

	function calendarRenderers() {
		return {
			month: renderMonthCalendar,
			pills: renderPillsCalendar,
			list: renderListCalendar
		};
	}

	function dayButtonAttrs(dt, iso, sel) {
		return `data-cal-day="${iso}" aria-pressed="${sel}" aria-label="${esc(fullDate(dt))}"`;
	}

	function renderMonthCalendar(root, state, onSelect) {
		const today = new Date();
		today.setHours(0, 0, 0, 0);

		const render = (dir) => {
			const y = state.cursor.getFullYear();
			const m = state.cursor.getMonth();
			const first = new Date(y, m, 1);
			const startDow = (first.getDay() + 6) % 7; // Mon=0
			const daysInMonth = new Date(y, m + 1, 0).getDate();
			const monthLabel = state.cursor.toLocaleString(undefined, { month: 'long', year: 'numeric' });
			const atCurrent = y === today.getFullYear() && m === today.getMonth();

			let html = `<header class="bk-cal__head">
				<button type="button" class="bk-cal__nav" data-cal-prev aria-label="${esc(i18n.prevMonth || 'Previous month')}"${atCurrent ? ' disabled' : ''}><span aria-hidden="true">‹</span></button>
				<span class="bk-cal__title" aria-live="polite">${esc(monthLabel)}</span>
				<button type="button" class="bk-cal__nav" data-cal-next aria-label="${esc(i18n.nextMonth || 'Next month')}"><span aria-hidden="true">›</span></button>
			</header><div class="bk-cal__grid${dir ? ' is-sliding-' + dir : ''}">`;
			// Localised weekday initials, Monday first (2024-01-01 was a Monday).
			for (let i = 0; i < 7; i++) {
				const wd = new Date(2024, 0, 1 + i);
				html += `<div class="bk-cal__dow" aria-hidden="true">${esc(wd.toLocaleDateString(undefined, { weekday: 'short' }))}</div>`;
			}
			for (let i = 0; i < startDow; i++) html += `<div aria-hidden="true"></div>`;

			for (let day = 1; day <= daysInMonth; day++) {
				const dt = new Date(y, m, day);
				const iso = fmtIsoDate(dt);
				const past = dt < today;
				const sel = state.selectedDate === iso;
				const isToday = dt.getTime() === today.getTime();
				html += `<button type="button" class="bk-cal__day${sel ? ' is-selected' : ''}${isToday ? ' is-today' : ''}"
					${dayButtonAttrs(dt, iso, sel)}
					${past ? 'disabled' : ''}>${day}</button>`;
			}
			html += `</div>`;
			root.innerHTML = html;
			bindNav(root, state, render);
			bindDays(root, state, onSelect);
		};
		render();
		arrowNav(root, '[data-cal-day]', 7);
	}

	function renderStrip(root, state, onSelect, kind) {
		const today = new Date();
		today.setHours(0, 0, 0, 0);
		const days = 14;
		const cls = kind === 'list' ? 'bk-list' : 'bk-pills';
		let html = `<div class="${cls}">`;
		for (let i = 0; i < days; i++) {
			const dt = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
			const iso = fmtIsoDate(dt);
			const sel = state.selectedDate === iso;
			if (kind === 'list') {
				const label = dt.toLocaleDateString(undefined, { weekday: 'long', month: 'short', day: 'numeric' });
				const rel = i === 0 ? (i18n.today || 'Today') : (i === 1 ? (i18n.tomorrow || 'Tomorrow') : '');
				html += `<button type="button" class="bk-list__row${sel ? ' is-selected' : ''}" ${dayButtonAttrs(dt, iso, sel)} style="--i:${i}">
					<span class="bk-list__date">${esc(label)}</span>
					${rel ? `<em class="bk-list__rel">${esc(rel)}</em>` : ''}
					<span class="bk-list__arrow" aria-hidden="true">→</span>
				</button>`;
			} else {
				const wd = dt.toLocaleDateString(undefined, { weekday: 'short' });
				html += `<button type="button" class="bk-pill${sel ? ' is-selected' : ''}" ${dayButtonAttrs(dt, iso, sel)} style="--i:${i}">
					<span class="bk-pill__wd">${esc(wd)}</span>
					<span class="bk-pill__day">${dt.getDate()}</span>
				</button>`;
			}
		}
		html += `</div>`;
		root.innerHTML = html;
		bindDays(root, state, onSelect);
		arrowNav(root, '[data-cal-day]', 0);
	}
	function renderPillsCalendar(root, state, onSelect) { renderStrip(root, state, onSelect, 'pills'); }
	function renderListCalendar(root, state, onSelect) { renderStrip(root, state, onSelect, 'list'); }

	function bindNav(root, state, render) {
		const prev = root.querySelector('[data-cal-prev]');
		const next = root.querySelector('[data-cal-next]');
		if (prev) prev.addEventListener('click', () => {
			state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth() - 1, 1);
			render('prev');
			const p = root.querySelector('[data-cal-prev]');
			(p && !p.disabled ? p : root.querySelector('[data-cal-next]')).focus();
		});
		if (next) next.addEventListener('click', () => {
			state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth() + 1, 1);
			render('next');
			root.querySelector('[data-cal-next]').focus();
		});
	}

	function bindDays(root, state, onSelect) {
		const all = Array.from(root.querySelectorAll('[data-cal-day]'));
		all.forEach(btn => {
			btn.addEventListener('click', () => {
				state.selectedDate = btn.dataset.calDay;
				press(all, btn);
				onSelect(state.selectedDate);
			});
		});
	}

	/* ================= Form controller ================= */

	function setupForm(root) {
		applyAppearance(root);
		root.classList.add('bookingistic-js');

		const state = {
			serviceId: parseInt(root.dataset.serviceId, 10),
			staffId: 0,
			rescheduleId: parseInt(root.dataset.rescheduleId || '0', 10),
			rescheduleToken: root.dataset.rescheduleToken || '',
			tz: detectTz(),
			cursor: new Date(),
			selectedDate: null,
			selectedSlot: null,
			availability: [],
			step: 1,
			reqId: 0,
		};

		const tzNode = root.querySelector('[data-bookingistic-tz]');
		const calNode = root.querySelector('[data-bookingistic-calendar]');
		const slotsList = root.querySelector('[data-bookingistic-slots]');
		const slotsEmpty = root.querySelector('.bookingistic-slots-empty, .bookingistic-form__slots-empty');
		const nextBtn = root.querySelector('[data-action="next"]');
		const backBtn = root.querySelector('[data-action="back"]');
		const errorBox = root.querySelector('[data-bookingistic-error]');
		const statusNode = root.querySelector('[data-bookingistic-status]');
		const progressText = root.querySelector('[data-bookingistic-progress-text]');
		const progressRoot = root.querySelector('[data-bookingistic-progress]');
		const summaryNode = root.querySelector('[data-bookingistic-summary]');
		const successTitle = root.querySelector('[data-bookingistic-success-title]');
		const successBody = root.querySelector('[data-bookingistic-success-body]');
		const stepsRoot = root.querySelector('.bookingistic-form__steps');
		const form = root.querySelector('[data-bookingistic-fields]');

		if (!calNode || !slotsList || !stepsRoot || !form || !nextBtn) return;

		if (tzNode && state.tz) tzNode.textContent = (i18n.timesIn || 'Times shown in') + ' ' + state.tz;

		/* CTA label lives in a span so it can crossfade without losing the button. */
		const ctaDefault = nextBtn.textContent.trim() || (i18n.continue || 'Continue');
		nextBtn.textContent = '';
		const ctaLabel = document.createElement('span');
		ctaLabel.className = 'bookingistic-btn__label';
		ctaLabel.textContent = ctaDefault;
		nextBtn.appendChild(ctaLabel);

		function setCta(text) {
			if (ctaLabel.textContent === text) return;
			if (reduceMotion()) { ctaLabel.textContent = text; return; }
			ctaLabel.classList.add('is-swapping');
			window.setTimeout(() => {
				ctaLabel.textContent = text;
				ctaLabel.classList.remove('is-swapping');
			}, 140);
		}

		function announce(msg) {
			if (!statusNode) return;
			statusNode.textContent = '';
			window.setTimeout(() => { statusNode.textContent = msg; }, 30);
		}

		function toast(title, body) {
			if (reduceMotion()) return;
			const t = document.createElement('div');
			t.className = 'bookingistic-toast';
			t.setAttribute('aria-hidden', 'true'); // status region already announces
			const strong = document.createElement('strong');
			strong.textContent = title;
			t.appendChild(strong);
			if (body) { const span = document.createElement('span'); span.textContent = body; t.appendChild(span); }
			root.appendChild(t);
			window.setTimeout(() => t.classList.add('is-leaving'), 2600);
			window.setTimeout(() => t.remove(), 3100);
		}

		const staffPicker = root.querySelector('[data-bookingistic-staff-picker]');
		if (staffPicker) {
			const opts = Array.from(staffPicker.querySelectorAll('.bookingistic-staff__option, .bookingistic-form__staff-option'));
			opts.forEach(btn => {
				btn.addEventListener('click', () => {
					press(opts, btn);
					state.staffId = parseInt(btn.dataset.staffId, 10) || 0;
					if (state.selectedDate) loadAvailability(state.selectedDate);
				});
			});
			arrowNav(staffPicker, '.bookingistic-staff__option, .bookingistic-form__staff-option', 0);
		}

		function showError(msg) {
			if (!errorBox) return;
			errorBox.textContent = msg;
			errorBox.hidden = false;
		}
		function clearError() {
			if (!errorBox) return;
			errorBox.hidden = true;
			errorBox.textContent = '';
		}

		function setStep(n) {
			const dir = n > state.step ? 'forward' : 'back';
			state.step = n;
			stepsRoot.dataset.step = String(n);
			stepsRoot.dataset.dir = dir;
			root.querySelectorAll('[data-step-content]').forEach(el => {
				const on = parseInt(el.dataset.stepContent, 10) === n;
				el.hidden = !on;
				el.classList.toggle('is-active', on);
			});
			if (progressText && i18n.stepOf) {
				progressText.textContent = i18n.stepOf.replace('%1$d', String(n)).replace('%2$d', '3');
			}
			if (progressRoot) progressRoot.dataset.current = String(n);
			// Move focus to the new step so keyboard / screen-reader users follow along.
			const panel = root.querySelector(`[data-step-content="${n}"]`);
			if (panel) {
				const target = panel.querySelector('h3, [data-bookingistic-success-title], input:not([type="hidden"]):not([tabindex="-1"])');
				if (target) {
					if (!/^(INPUT|TEXTAREA|BUTTON)$/.test(target.tagName)) target.setAttribute('tabindex', '-1');
					target.focus({ preventScroll: true });
				}
				if (root.getBoundingClientRect().top < 0) root.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'start' });
			}
		}

		function showSkeleton() {
			slotsList.innerHTML = '';
			slotsList.setAttribute('aria-busy', 'true');
			slotsList.classList.add('is-loading');
			for (let i = 0; i < 8; i++) {
				const li = document.createElement('li');
				li.className = 'bookingistic-skeleton';
				li.setAttribute('aria-hidden', 'true');
				li.style.setProperty('--i', String(i));
				slotsList.appendChild(li);
			}
			slotsList.hidden = false;
		}

		async function loadAvailability(date) {
			const reqId = ++state.reqId;
			clearError();
			slotsEmpty.hidden = true;
			showSkeleton();
			nextBtn.disabled = true;
			state.selectedSlot = null;
			setCta(ctaDefault);
			announce(i18n.loading || 'Loading…');

			const params = new URLSearchParams({
				service_id: String(state.serviceId),
				date,
				tz: state.tz,
			});
			if (state.staffId) params.set('staff_id', String(state.staffId));
			try {
				const res = await fetch(apiUrl('availability', params.toString()), {
					headers: { 'X-WP-Nonce': cfg.nonce },
				});
				const data = await res.json();
				if (reqId !== state.reqId) return; // a newer date was picked meanwhile
				if (!res.ok) throw new Error((data && data.message) || i18n.errorGeneric);
				state.availability = data.slots || [];
				renderSlots();
			} catch (e) {
				if (reqId !== state.reqId) return;
				slotsList.removeAttribute('aria-busy');
				slotsList.classList.remove('is-loading');
				slotsList.hidden = true;
				slotsList.innerHTML = '';
				showError(e.message || i18n.errorGeneric);
				slotsEmpty.hidden = false;
				slotsEmpty.textContent = i18n.noSlots || 'No times available.';
			}
		}

		function slotDate(slot) { return new Date(slot.start_utc.replace(' ', 'T') + 'Z'); }
		function timeLabel(d) { return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }); }

		function renderSlots() {
			slotsList.removeAttribute('aria-busy');
			slotsList.classList.remove('is-loading');
			slotsList.innerHTML = '';
			if (!state.availability.length) {
				slotsEmpty.hidden = false;
				slotsEmpty.textContent = i18n.noSlots || 'No times available on this date.';
				slotsList.hidden = true;
				announce(slotsEmpty.textContent);
				return;
			}
			slotsEmpty.hidden = true;
			slotsList.hidden = false;
			const buttons = [];
			state.availability.forEach((slot, i) => {
				const li = document.createElement('li');
				li.style.setProperty('--i', String(Math.min(i, 16)));
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'bookingistic-slot';
				btn.setAttribute('aria-pressed', 'false');
				btn.textContent = timeLabel(slotDate(slot));
				btn.dataset.startUtc = slot.start_utc;
				btn.addEventListener('click', () => {
					press(buttons, btn);
					state.selectedSlot = slot;
					nextBtn.disabled = false;
					const d = slotDate(slot);
					const when = (i18n.atTime || '%1$s at %2$s')
						.replace('%1$s', d.toLocaleDateString(undefined, { weekday: 'short' }))
						.replace('%2$s', timeLabel(d));
					setCta(sprintf1(i18n.continueWith || 'Continue with %s', when) + ' →');
				});
				buttons.push(btn);
				li.appendChild(btn);
				slotsList.appendChild(li);
			});
			const n = state.availability.length;
			const msg = (n === 1 ? (i18n.timesAvailableOne || '%d time available') : (i18n.timesAvailable || '%d times available')).replace('%d', String(n));
			announce(msg);
			toast(msg, state.selectedDate ? parseIso(state.selectedDate).toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }) : '');
		}
		arrowNav(slotsList, '.bookingistic-slot', 0);

		const renderer = calendarRenderers()[root.dataset.calendarView] || renderMonthCalendar;
		renderer(calNode, state, loadAvailability);

		nextBtn.addEventListener('click', () => {
			clearError();
			if (!state.selectedSlot) return;
			const local = slotDate(state.selectedSlot);
			if (summaryNode) summaryNode.textContent = `${local.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' })} · ${timeLabel(local)} (${state.tz})`;
			setStep(2);
		});

		if (backBtn) backBtn.addEventListener('click', () => {
			setStep(1);
			const sel = slotsList.querySelector('.bookingistic-slot.is-selected');
			if (sel) sel.focus();
		});

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			clearError();
			const submitBtn = form.querySelector('[data-action="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = i18n.submitting || 'Submitting…';
			submitBtn.classList.add('is-busy');
			form.setAttribute('aria-busy', 'true');
			announce(i18n.submitting || 'Submitting…');

			const fd = new FormData(form);
			const payload = {
				service_id: state.serviceId,
				staff_id: state.staffId || 0,
				start_utc: state.selectedSlot.start_utc,
				timezone: state.tz,
				name: fd.get('name'),
				email: fd.get('email'),
				phone: fd.get('phone') || '',
				company: fd.get('company') || '',
				notes: fd.get('notes') || '',
				website2: fd.get('website2') || '',
			};

			const isReschedule = state.rescheduleId > 0 && state.rescheduleToken;
			const url = isReschedule
				? apiUrl(`bookings/${state.rescheduleId}/reschedule`)
				: apiUrl('bookings');
			const body = isReschedule
				? { token: state.rescheduleToken, start_utc: payload.start_utc }
				: payload;

			try {
				const res = await fetch(url, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
					body: JSON.stringify(body),
				});
				const data = await res.json();
				if (!res.ok || data.code) {
					throw new Error(data.message || i18n.errorGeneric);
				}
				if (data.silent) {
					// honeypot triggered — pretend it worked
				}
				successTitle.textContent = isReschedule
					? (i18n.rescheduleConfirmed || 'Reschedule confirmed')
					: (data.status === 'confirmed' ? (i18n.bookingConfirmed || 'Booking confirmed') : (i18n.bookingReceived || 'Booking received'));
				successBody.textContent = data.message || (i18n.checkInbox || 'Check your inbox for the details.');

				// Paid booking: surface the pay-now CTA (gateway connected)
				// or a pay-later notice. payUrl is a signed link.
				var payBox = root.querySelector('[data-bookingistic-pay]');
				if (payBox) {
					payBox.innerHTML = '';
					if (data.payment && data.payment.pay_url) {
						var amt = data.payment.amount_due;
						var label = (i18n.payNow || 'Pay now') + (amt > 0 ? ' — ' + amt.toFixed(2) + ' ' + (data.payment.currency || '') : '');
						var a = document.createElement('a');
						a.className = 'bookingistic-payment-box__button';
						a.href = data.payment.pay_url;
						a.textContent = label;
						payBox.appendChild(a);
					} else if (data.payment) {
						var p = document.createElement('p');
						p.className = 'bookingistic-payment-notice';
						p.textContent = (i18n.payLater || 'Payment is due — we will send you payment instructions.');
						payBox.appendChild(p);
					}
					if (payBox.childNodes.length) payBox.hidden = false;
				}
				form.removeAttribute('aria-busy');
				setStep(3);
				toast(i18n.followUp || 'Follow-up, handled', i18n.followUpBody || 'Confirmation is on its way');
			} catch (err) {
				showError(err.message || i18n.errorGeneric);
				submitBtn.disabled = false;
				submitBtn.classList.remove('is-busy');
				form.removeAttribute('aria-busy');
				submitBtn.textContent = isReschedule ? (i18n.confirmReschedule || 'Confirm reschedule') : (i18n.confirmBooking || 'Confirm booking');
			}
		});
	}

	function init() {
		document.querySelectorAll('.bookingistic-form').forEach(setupForm);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
