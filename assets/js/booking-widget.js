/**
 * Adishiv Luxury Hotel & Suites
 * Booking Subsystem & Real-Time Calculation Controller (WP1.4, WP2.2, WP2.3, WP3.5, WP5.6)
 */

document.addEventListener('DOMContentLoaded', () => {
  // Safe string escaper for DOM text insertion
  const escapeHTML = (str) => {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  };

  // Base URL & hotel date from APP global
  const baseUrl = (window.APP && window.APP.baseUrl) ? window.APP.baseUrl : '';
  const hotelTodayStr = (window.APP && window.APP.today) ? window.APP.today : new Date().toISOString().split('T')[0];

  // Helper to parse YYYY-MM-DD cleanly into Date object in local time
  const parseISODate = (str) => {
    if (!str || !/^\d{4}-\d{2}-\d{2}$/.test(str)) return null;
    const [y, m, d] = str.split('-').map(Number);
    return new Date(y, m - 1, d);
  };

  const formatLocalDate = (d) => {
    if (!d || isNaN(d.getTime())) return '';
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  // 1. Initialise default dates (Tomorrow -> 2 nights later) using hotel timezone
  const todayDate = parseISODate(hotelTodayStr) || new Date();
  const tomorrowDate = new Date(todayDate);
  tomorrowDate.setDate(tomorrowDate.getDate() + 1);
  const defaultDeparture = new Date(tomorrowDate);
  defaultDeparture.setDate(defaultDeparture.getDate() + 2);

  const checkInInputs = document.querySelectorAll('input[name="check_in"]');
  const checkOutInputs = document.querySelectorAll('input[name="check_out"]');

  checkInInputs.forEach((el) => {
    if (!el.value) el.value = formatLocalDate(tomorrowDate);
    el.min = formatLocalDate(todayDate);
    el.addEventListener('change', () => {
      const selectedIn = parseISODate(el.value);
      if (!selectedIn) return;

      const minOut = new Date(selectedIn);
      minOut.setDate(minOut.getDate() + 1);
      const minOutStr = formatLocalDate(minOut);

      checkOutInputs.forEach((outEl) => {
        outEl.min = minOutStr;
        const currentOut = parseISODate(outEl.value);
        if (!currentOut || currentOut <= selectedIn) {
          outEl.value = minOutStr;
        }
      });
    });
  });

  checkOutInputs.forEach((el) => {
    if (!el.value) el.value = formatLocalDate(defaultDeparture);
    el.min = formatLocalDate(tomorrowDate);
  });

  // 2. Homepage Quick Booking Bar Submission
  const quickBookingForm = document.getElementById('quickBookingForm');
  if (quickBookingForm) {
    quickBookingForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const inVal = quickBookingForm.querySelector('input[name="check_in"]').value;
      const outVal = quickBookingForm.querySelector('input[name="check_out"]').value;
      const guestsVal = quickBookingForm.querySelector('select[name="guests"]').value;
      const roomTypeVal = quickBookingForm.querySelector('select[name="room_type"]')?.value || '';

      let url = `${baseUrl}/booking.php?check_in=${encodeURIComponent(inVal)}&check_out=${encodeURIComponent(outVal)}&guests=${encodeURIComponent(guestsVal)}`;
      if (roomTypeVal) {
        url += `&room_type_id=${encodeURIComponent(roomTypeVal)}`;
      }
      window.location.href = url;
    });
  }

  // 3. Multi-Step Booking Page Engine (booking.php)
  const bookingWizard = document.getElementById('bookingWizard');
  if (bookingWizard) {
    const step1 = document.getElementById('wizardStep1');
    const step2 = document.getElementById('wizardStep2');
    const step3 = document.getElementById('wizardStep3');
    const suiteOptionsContainer = document.getElementById('suiteOptionsContainer');
    const bookingSummaryCard = document.getElementById('bookingSummaryCard');
    const bookingFeedback = document.getElementById('bookingFeedback');

    // Generate unique client idempotency key per wizard session (WP2.2)
    const idempotencyKey = (window.crypto && typeof crypto.randomUUID === 'function')
      ? crypto.randomUUID()
      : ('idemp_' + Date.now() + '_' + Math.random().toString(36).substring(2, 12));

    let activeAbortController = null;

    let state = {
      checkIn: '',
      checkOut: '',
      guests: 1,
      roomTypeId: null,
      selectedSuite: null,
      pricing: null
    };

    const showInlineError = (container, message) => {
      if (!container) return;
      container.innerHTML = '';
      const alertDiv = document.createElement('div');
      alertDiv.className = 'alert alert-error';
      alertDiv.setAttribute('role', 'alert');
      alertDiv.textContent = message;
      container.appendChild(alertDiv);
    };

    // Load available suites from API
    const fetchAvailableSuites = async () => {
      const checkIn = document.getElementById('bookCheckIn').value;
      const checkOut = document.getElementById('bookCheckOut').value;
      const guests = parseInt(document.getElementById('bookGuests').value, 10) || 1;

      state.checkIn = checkIn;
      state.checkOut = checkOut;
      state.guests = guests;

      // Cancel previous pending availability request
      if (activeAbortController) {
        activeAbortController.abort();
      }
      activeAbortController = new AbortController();

      suiteOptionsContainer.innerHTML = '';
      const loadingWrap = document.createElement('div');
      loadingWrap.style.padding = '2.5rem';
      loadingWrap.style.textAlign = 'center';
      loadingWrap.style.color = 'var(--color-text-muted)';
      loadingWrap.innerHTML = `
        <div class="spinner" role="status" style="margin: 0 auto 1rem; width: 28px; height: 28px; border: 2px solid var(--color-gold); border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite;">
          <span class="sr-only">Checking availability...</span>
        </div>
        <div>Checking live imperial sanctuary availability...</div>
      `;
      suiteOptionsContainer.appendChild(loadingWrap);

      try {
        const url = `${baseUrl}/api/check-availability.php?check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}&guests=${encodeURIComponent(guests)}`;
        const res = await fetch(url, { signal: activeAbortController.signal });
        const data = await res.json();

        if (!data.success) {
          showInlineError(suiteOptionsContainer, data.error || 'Failed to query room availability.');
          return;
        }

        if (!data.suites || data.suites.length === 0) {
          suiteOptionsContainer.innerHTML = '';
          const infoAlert = document.createElement('div');
          infoAlert.className = 'alert alert-info';
          infoAlert.setAttribute('role', 'status');
          const guestText = guests === 1 ? '1 guest' : `${guests} guests`;
          infoAlert.textContent = `No suites are available for ${guestText} between ${checkIn} and ${checkOut}. Please adjust your dates or party size.`;
          suiteOptionsContainer.appendChild(infoAlert);
          return;
        }

        renderSuiteOptions(data.suites, data.nights);
      } catch (err) {
        if (err.name === 'AbortError') return;
        showInlineError(suiteOptionsContainer, 'Unable to query live availability. Please check server connectivity.');
      }
    };

    // Render suite cards using DOM construction to prevent HTML injection (WP3.5)
    const renderSuiteOptions = (suites, nights) => {
      suiteOptionsContainer.innerHTML = '';
      suiteOptionsContainer.setAttribute('aria-live', 'polite');

      const grid = document.createElement('div');
      grid.style.display = 'grid';
      grid.style.gap = '1.5rem';

      suites.forEach((s) => {
        const isSelected = state.roomTypeId === s.id;
        const card = document.createElement('div');
        card.className = 'room-card';
        card.style.border = isSelected ? '2px solid var(--color-gold)' : '2px solid var(--color-border-hairline)';

        const cardLayout = document.createElement('div');
        cardLayout.style.display = 'grid';
        cardLayout.style.gridTemplateColumns = 'repeat(auto-fit, minmax(260px, 1fr))';

        // Image Column
        const imgWrap = document.createElement('div');
        imgWrap.style.height = '220px';
        imgWrap.style.overflow = 'hidden';

        const img = document.createElement('img');
        img.src = s.featured_image || 'assets/images/branding/hero-facade.jpg';
        img.alt = s.name;
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        imgWrap.appendChild(img);

        // Content Column
        const contentWrap = document.createElement('div');
        contentWrap.style.padding = '1.5rem';
        contentWrap.style.display = 'flex';
        contentWrap.style.flexDirection = 'column';
        contentWrap.style.justifyContent = 'space-between';

        const topMeta = document.createElement('div');
        const badge = document.createElement('div');
        badge.style.fontSize = '0.72rem';
        badge.style.color = 'var(--color-gold-ink)';
        badge.style.textTransform = 'uppercase';
        badge.style.fontWeight = '600';
        badge.style.letterSpacing = '0.1em';
        badge.style.marginBottom = '0.35rem';
        badge.textContent = `${s.view_type || 'Sanctuary View'} · ${s.room_size_sqft} SQ FT`;

        const title = document.createElement('h3');
        title.style.fontSize = '1.5rem';
        title.style.marginBottom = '0.5rem';
        title.textContent = s.name;

        const desc = document.createElement('p');
        desc.style.fontSize = '0.88rem';
        desc.style.marginBottom = '0.75rem';
        desc.textContent = s.short_description || '';

        topMeta.appendChild(badge);
        topMeta.appendChild(title);
        topMeta.appendChild(desc);

        // Price & Select Footer
        const bottomRow = document.createElement('div');
        bottomRow.style.display = 'flex';
        bottomRow.style.alignItems = 'center';
        bottomRow.style.justifyContent = 'space-between';
        bottomRow.style.borderTop = '1px solid var(--color-border-hairline)';
        bottomRow.style.paddingTop = '1rem';

        const priceBlock = document.createElement('div');
        const pricePerNight = document.createElement('span');
        pricePerNight.style.fontFamily = 'var(--font-serif)';
        pricePerNight.style.fontSize = '1.4rem';
        pricePerNight.style.fontWeight = '600';
        pricePerNight.textContent = s.price_per_night_formatted;

        const perNightLabel = document.createElement('span');
        perNightLabel.style.fontSize = '0.75rem';
        perNightLabel.style.color = 'var(--color-text-muted)';
        perNightLabel.textContent = ' / night';

        const totalHint = document.createElement('div');
        totalHint.style.fontSize = '0.75rem';
        totalHint.style.color = 'var(--color-gold-ink)';
        const nightText = nights === 1 ? '1 night' : `${nights} nights`;
        totalHint.textContent = `Total (${nightText} + GST): ${s.calculated_total_formatted}`;

        priceBlock.appendChild(pricePerNight);
        priceBlock.appendChild(perNightLabel);
        priceBlock.appendChild(totalHint);

        const selectBtn = document.createElement('button');
        selectBtn.type = 'button';
        selectBtn.className = isSelected ? 'btn btn-gold btn-sm is-success select-suite-btn' : 'btn btn-gold btn-sm select-suite-btn';
        selectBtn.textContent = isSelected ? 'Selected ✓' : 'Select Suite';
        selectBtn.addEventListener('click', () => {
          selectSuite(s);
        });

        bottomRow.appendChild(priceBlock);
        bottomRow.appendChild(selectBtn);

        contentWrap.appendChild(topMeta);
        contentWrap.appendChild(bottomRow);

        cardLayout.appendChild(imgWrap);
        cardLayout.appendChild(contentWrap);
        card.appendChild(cardLayout);
        grid.appendChild(card);
      });

      suiteOptionsContainer.appendChild(grid);
    };

    const selectSuite = (suite) => {
      state.roomTypeId = suite.id;
      state.selectedSuite = suite;
      fetchPricing(suite.id);
    };

    const fetchPricing = async (roomTypeId) => {
      try {
        const url = `${baseUrl}/api/calculate-pricing.php?room_type_id=${roomTypeId}&check_in=${encodeURIComponent(state.checkIn)}&check_out=${encodeURIComponent(state.checkOut)}`;
        const res = await fetch(url);
        const pricing = await res.json();
        if (pricing.success) {
          state.pricing = pricing;
          renderSummary();
          goToStep(2);
        } else {
          showInlineError(bookingFeedback, pricing.error || 'Error calculating reservation pricing.');
        }
      } catch (err) {
        showInlineError(bookingFeedback, 'Error connecting to reservation pricing server.');
      }
    };

    const renderSummary = () => {
      if (!state.pricing || !bookingSummaryCard) return;
      const p = state.pricing;
      const nights = p.nights;
      const nightLabel = nights === 1 ? '1 Night' : `${nights} Nights`;
      const guestCount = state.guests;
      const guestLabel = guestCount === 1 ? '1 Guest' : `${guestCount} Guests`;

      bookingSummaryCard.innerHTML = `
        <div style="background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 1.75rem; box-shadow: var(--shadow-card);">
          <h4 style="font-size: 1.3rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border-hairline); padding-bottom: 0.75rem;">
            Reservation Summary
          </h4>
          <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.9rem; margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Suite Category</span>
              <strong>${escapeHTML(state.selectedSuite.name)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Stay Itinerary</span>
              <span>${escapeHTML(state.checkIn)} to ${escapeHTML(state.checkOut)}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Duration</span>
              <span>${nightLabel}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Party Size</span>
              <span>${guestLabel}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--color-border-hairline); padding-top: 0.65rem;">
              <span class="text-muted">Room Subtotal</span>
              <span>${escapeHTML(p.subtotal_formatted)}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">GST (${p.tax_rate}%)</span>
              <span>${escapeHTML(p.tax_amount_formatted)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 2px solid var(--color-obsidian); padding-top: 0.75rem; font-size: 1.15rem;">
              <strong>Total (INR)</strong>
              <strong style="color: var(--color-text-main); font-family: var(--font-serif); font-size: 1.35rem;">
                ${escapeHTML(p.total_amount_formatted)}
              </strong>
            </div>
          </div>
          <div style="font-size: 0.75rem; color: var(--color-text-muted); background: var(--color-parchment); padding: 0.75rem; border-radius: var(--radius-xs);">
            ✓ Daily bespoke breakfast & sanctuary wellness access included.
          </div>
        </div>
      `;
    };

    const goToStep = (stepNumber) => {
      document.querySelectorAll('.wizard-step-pane').forEach((p) => {
        p.style.display = 'none';
      });

      document.querySelectorAll('.wizard-indicator').forEach((ind) => {
        const indNum = parseInt(ind.dataset.step, 10);
        ind.classList.remove('active', 'completed');
        ind.removeAttribute('aria-current');

        if (indNum === stepNumber) {
          ind.classList.add('active');
          ind.setAttribute('aria-current', 'step');
        } else if (indNum < stepNumber) {
          ind.classList.add('completed');
        }
      });

      let targetPane = null;
      if (stepNumber === 1) { step1.style.display = 'block'; targetPane = step1; }
      if (stepNumber === 2) { step2.style.display = 'block'; targetPane = step2; }
      if (stepNumber === 3) { step3.style.display = 'block'; targetPane = step3; }

      // Focus management: move focus to step heading for screen readers
      if (targetPane) {
        const heading = targetPane.querySelector('h2, h3, h4');
        if (heading) {
          heading.setAttribute('tabindex', '-1');
          heading.focus();
        }
      }

      window.scrollTo({ top: bookingWizard.offsetTop - 80, behavior: 'smooth' });
    };

    // Attach step navigation listeners
    document.getElementById('searchAvailabilityBtn')?.addEventListener('click', fetchAvailableSuites);
    document.getElementById('backToStep1Btn')?.addEventListener('click', () => goToStep(1));
    document.getElementById('proceedToReviewBtn')?.addEventListener('click', () => {
      const name = document.getElementById('guestName').value.trim();
      const email = document.getElementById('guestEmail').value.trim();
      const phone = document.getElementById('guestPhone').value.trim();

      if (!name || !email || !phone) {
        showInlineError(bookingFeedback, 'Please fill in your full name, email address, and phone number.');
        return;
      }
      bookingFeedback.innerHTML = '';
      goToStep(3);
    });
    document.getElementById('backToStep2Btn')?.addEventListener('click', () => goToStep(2));

    // Submit Final Booking (Atomically via API with CSRF & Idempotency)
    document.getElementById('finalConfirmBookingBtn')?.addEventListener('click', async () => {
      const confirmBtn = document.getElementById('finalConfirmBookingBtn');
      confirmBtn.disabled = true;
      confirmBtn.classList.add('is-loading');
      confirmBtn.textContent = 'Securing Imperial Reservation...';

      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfInput = document.querySelector('input[name="csrf_token"]');
      const csrfVal = (csrfMeta ? csrfMeta.content : (csrfInput ? csrfInput.value : ''));

      const payload = {
        csrf_token: csrfVal,
        idempotency_key: idempotencyKey,
        room_type_id: state.roomTypeId,
        check_in: state.checkIn,
        check_out: state.checkOut,
        guests_count: state.guests,
        name: document.getElementById('guestName').value.trim(),
        email: document.getElementById('guestEmail').value.trim(),
        phone: document.getElementById('guestPhone').value.trim(),
        special_requests: document.getElementById('specialRequests')?.value.trim() || '',
        payment_method: document.querySelector('input[name="payment_method"]:checked')?.value || 'pay_at_hotel'
      };

      try {
        const res = await fetch(`${baseUrl}/api/create-booking.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfVal
          },
          body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
          const redirectUrl = `${baseUrl}/confirmation.php?ref=${encodeURIComponent(result.booking_reference)}&token=${encodeURIComponent(result.access_token || '')}`;
          window.location.href = redirectUrl;
        } else {
          showInlineError(bookingFeedback, result.error || 'Failed to complete booking.');
          confirmBtn.disabled = false;
          confirmBtn.classList.remove('is-loading');
          confirmBtn.textContent = 'Confirm & Reserve Suite';
        }
      } catch (err) {
        showInlineError(bookingFeedback, 'A communication error occurred with the reservation server. Please contact concierge desk.');
        confirmBtn.disabled = false;
        confirmBtn.classList.remove('is-loading');
        confirmBtn.textContent = 'Confirm & Reserve Suite';
      }
    });

    // Populate initial inputs from URL query params (e.g. redirected from hero)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('check_in')) {
      document.getElementById('bookCheckIn').value = urlParams.get('check_in');
    }
    if (urlParams.get('check_out')) {
      document.getElementById('bookCheckOut').value = urlParams.get('check_out');
    }
    if (urlParams.get('guests')) {
      document.getElementById('bookGuests').value = urlParams.get('guests');
    }
    if (urlParams.get('room_type_id')) {
      state.roomTypeId = parseInt(urlParams.get('room_type_id'), 10);
    }

    // Run initial search
    fetchAvailableSuites();
  }
});
