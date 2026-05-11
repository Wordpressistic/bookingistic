/**
 * Bookingistic — frontend booking form.
 * No dependencies. Vanilla ES6.
 */
(function () {
	'use strict';

	if (typeof window.BookingisticConfig === 'undefined') return;

	const cfg = window.BookingisticConfig;
	const i18n = cfg.i18n || {};

	function pad(n) { return String(n).padStart(2, '0'); }
	function fmtIsoDate(d) { return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; }

	function detectTz() {
		try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; }
		catch (e) { return ''; }
	}

	function buildCalendar(root, state, onSelect) {
		const today = new Date();
		today.setHours(0,0,0,0);

		const render = () => {
			const y = state.cursor.getFullYear();
			const m = state.cursor.getMonth();
			const first = new Date(y, m, 1);
			const startDow = (first.getDay() + 6) % 7; // make Mon=0
			const daysInMonth = new Date(y, m+1, 0).getDate();
			const monthLabel = state.cursor.toLocaleString(undefined, { month:'long', year:'numeric' });

			const dows = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
			let html = `<header>
				<button type="button" data-cal-prev aria-label="Previous month">‹</button>
				<span class="bookingistic-form__calendar-title">${monthLabel}</span>
				<button type="button" data-cal-next aria-label="Next month">›</button>
			</header><div class="bookingistic-form__calendar-grid">`;
			for (const d of dows) html += `<div class="bookingistic-form__calendar-dow">${d}</div>`;

			// leading blanks
			for (let i = 0; i < startDow; i++) html += `<div></div>`;

			for (let day = 1; day <= daysInMonth; day++) {
				const dt = new Date(y, m, day);
				const iso = fmtIsoDate(dt);
				const past = dt < today;
				const sel = state.selectedDate === iso;
				html += `<button type="button" class="bookingistic-form__calendar-day"
					data-cal-day="${iso}"
					${past ? 'disabled' : ''}
					aria-pressed="${sel}">${day}</button>`;
			}
			html += `</div>`;
			root.innerHTML = html;

			root.querySelector('[data-cal-prev]').addEventListener('click', () => {
				state.cursor = new Date(y, m-1, 1); render();
			});
			root.querySelector('[data-cal-next]').addEventListener('click', () => {
				state.cursor = new Date(y, m+1, 1); render();
			});
			root.querySelectorAll('[data-cal-day]').forEach(btn => {
				btn.addEventListener('click', () => {
					state.selectedDate = btn.dataset.calDay;
					render();
					onSelect(state.selectedDate);
				});
			});
		};
		render();
	}

	function setupForm(root) {
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
		};

		const tzNode  = root.querySelector('[data-bookingistic-tz]');
		const calNode = root.querySelector('[data-bookingistic-calendar]');
		const slotsList = root.querySelector('[data-bookingistic-slots]');
		const slotsEmpty = root.querySelector('.bookingistic-form__slots-empty');
		const nextBtn = root.querySelector('[data-action="next"]');
		const backBtn = root.querySelector('[data-action="back"]');
		const errorBox = root.querySelector('[data-bookingistic-error]');
		const summaryNode = root.querySelector('[data-bookingistic-summary]');
		const successTitle = root.querySelector('[data-bookingistic-success-title]');
		const successBody = root.querySelector('[data-bookingistic-success-body]');
		const stepsRoot = root.querySelector('.bookingistic-form__steps');
		const form = root.querySelector('[data-bookingistic-fields]');

		if (tzNode && state.tz) tzNode.textContent = `Times shown in ${state.tz}`;

		const staffPicker = root.querySelector('[data-bookingistic-staff-picker]');
		if (staffPicker) {
			staffPicker.querySelectorAll('.bookingistic-form__staff-option').forEach(btn => {
				btn.addEventListener('click', () => {
					staffPicker.querySelectorAll('.bookingistic-form__staff-option').forEach(b => b.setAttribute('aria-pressed','false'));
					btn.setAttribute('aria-pressed','true');
					state.staffId = parseInt(btn.dataset.staffId, 10) || 0;
					if (state.selectedDate) loadAvailability(state.selectedDate);
				});
			});
		}

		function showError(msg) {
			errorBox.textContent = msg;
			errorBox.hidden = false;
		}
		function clearError() { errorBox.hidden = true; errorBox.textContent = ''; }

		function setStep(n) {
			stepsRoot.dataset.step = String(n);
			root.querySelectorAll('[data-step-content]').forEach(el => {
				el.hidden = parseInt(el.dataset.stepContent, 10) !== n;
			});
		}

		async function loadAvailability(date) {
			slotsEmpty.textContent = i18n.loading || 'Loading…';
			slotsEmpty.hidden = false;
			slotsList.hidden = true;
			slotsList.innerHTML = '';
			nextBtn.disabled = true;
			state.selectedSlot = null;

			const params = new URLSearchParams({
				service_id: String(state.serviceId),
				date,
				tz: state.tz,
			});
			if (state.staffId) params.set('staff_id', String(state.staffId));
			try {
				const res = await fetch(`${cfg.restUrl}availability?${params}`, {
					headers: { 'X-WP-Nonce': cfg.nonce },
				});
				const data = await res.json();
				if (!res.ok) throw new Error(data?.message || i18n.errorGeneric);
				state.availability = data.slots || [];
				renderSlots();
			} catch (e) {
				showError(e.message || i18n.errorGeneric);
				slotsEmpty.hidden = false;
				slotsEmpty.textContent = i18n.noSlots || 'No times available.';
			}
		}

		function renderSlots() {
			if (!state.availability.length) {
				slotsEmpty.hidden = false;
				slotsEmpty.textContent = i18n.noSlots || 'No times available.';
				slotsList.hidden = true;
				return;
			}
			slotsEmpty.hidden = true;
			slotsList.hidden = false;
			slotsList.innerHTML = '';
			for (const slot of state.availability) {
				const li = document.createElement('li');
				const btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'bookingistic-form__slot';
				btn.setAttribute('aria-pressed', 'false');
				const local = new Date(slot.start_utc.replace(' ', 'T') + 'Z');
				btn.textContent = local.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
				btn.dataset.startUtc = slot.start_utc;
				btn.addEventListener('click', () => {
					slotsList.querySelectorAll('.bookingistic-form__slot').forEach(b => b.setAttribute('aria-pressed','false'));
					btn.setAttribute('aria-pressed','true');
					state.selectedSlot = slot;
					nextBtn.disabled = false;
				});
				li.appendChild(btn);
				slotsList.appendChild(li);
			}
		}

		buildCalendar(calNode, state, loadAvailability);

		nextBtn.addEventListener('click', () => {
			clearError();
			if (!state.selectedSlot) return;
			const local = new Date(state.selectedSlot.start_utc.replace(' ', 'T') + 'Z');
			summaryNode.textContent = `${local.toLocaleDateString(undefined, { weekday:'long', month:'long', day:'numeric' })} · ${local.toLocaleTimeString(undefined, { hour:'numeric', minute:'2-digit' })} (${state.tz})`;
			setStep(2);
		});

		backBtn.addEventListener('click', () => setStep(1));

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			clearError();
			const submitBtn = form.querySelector('[data-action="submit"]');
			submitBtn.disabled = true;
			submitBtn.textContent = i18n.submitting || 'Submitting…';

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
				? `${cfg.restUrl}bookings/${state.rescheduleId}/reschedule`
				: `${cfg.restUrl}bookings`;
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
				successTitle.textContent = isReschedule ? 'Reschedule confirmed' : (data.status === 'confirmed' ? 'Booking confirmed' : 'Booking received');
				successBody.textContent  = data.message || 'Check your inbox for the details.';
				setStep(3);
			} catch (err) {
				showError(err.message || i18n.errorGeneric);
				submitBtn.disabled = false;
				submitBtn.textContent = isReschedule ? 'Confirm reschedule' : 'Confirm booking';
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
