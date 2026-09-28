/**
 * Adishiv Luxury Hotel & Suites
 * Booking Subsystem & Real-Time Calculation Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Initialise default dates (Tomorrow -> 2 nights later)
  const today = new Date();
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);
  const departure = new Date(tomorrow);
  departure.setDate(departure.getDate() + 2);

  const formatDate = (d) => d.toISOString().split('T')[0];

  const checkInInputs = document.querySelectorAll('input[name="check_in"]');
  const checkOutInputs = document.querySelectorAll('input[name="check_out"]');

  checkInInputs.forEach((el) => {
    if (!el.value) el.value = formatDate(tomorrow);
    el.min = formatDate(today);
    el.addEventListener('change', () => {
      // Ensure check-out is after check-in
      const selectedIn = new Date(el.value);
      const minOut = new Date(selectedIn);
      minOut.setDate(minOut.getDate() + 1);
      checkOutInputs.forEach((outEl) => {
        outEl.min = formatDate(minOut);
        if (new Date(outEl.value) <= selectedIn) {
          outEl.value = formatDate(minOut);
        }
      });
    });
  });

  checkOutInputs.forEach((el) => {
    if (!el.value) el.value = formatDate(departure);
    el.min = formatDate(tomorrow);
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

      let url = `booking.php?check_in=${encodeURIComponent(inVal)}&check_out=${encodeURIComponent(outVal)}&guests=${encodeURIComponent(guestsVal)}`;
      if (roomTypeVal) {
        url += `&room_type_id=${encodeURIComponent(roomTypeVal)}`;
      }
      window.location.href = url;
    });
  }

  // 3. Multi-Step Booking Page Engine (booking.php)
  const bookingWizard = document.getElementById('bookingWizard');
  if (bookingWizard) {
    const step1 = document.getElementById('wizardStep1'); // Dates & Category
    const step2 = document.getElementById('wizardStep2'); // Guest Details
    const step3 = document.getElementById('wizardStep3'); // Review & Confirm
    const suiteOptionsContainer = document.getElementById('suiteOptionsContainer');
    const bookingSummaryCard = document.getElementById('bookingSummaryCard');
    const bookingFeedback = document.getElementById('bookingFeedback');

    let state = {
      checkIn: '',
      checkOut: '',
      guests: 1,
      roomTypeId: null,
      selectedSuite: null,
      pricing: null
    };

    // Load suites based on dates
    const fetchAvailableSuites = async () => {
      const checkIn = document.getElementById('bookCheckIn').value;
      const checkOut = document.getElementById('bookCheckOut').value;
      const guests = document.getElementById('bookGuests').value;

      state.checkIn = checkIn;
      state.checkOut = checkOut;
      state.guests = guests;

      suiteOptionsContainer.innerHTML = `
        <div style="padding: 2.5rem; text-align: center; color: var(--color-text-muted);">
          <div class="spinner" style="margin: 0 auto 1rem; width: 28px; height: 28px; border: 2px solid var(--color-gold); border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite;"></div>
          Checking live imperial sanctuary availability...
        </div>
      `;

      try {
        const res = await fetch(`api/check-availability.php?check_in=${encodeURIComponent(checkIn)}&check_out=${encodeURIComponent(checkOut)}&guests=${encodeURIComponent(guests)}`);
        const data = await res.json();

        if (!data.success) {
          suiteOptionsContainer.innerHTML = `<div class="alert alert-error">${data.error}</div>`;
          return;
        }

        if (data.suites.length === 0) {
          suiteOptionsContainer.innerHTML = `
            <div class="alert alert-info">
              No suites available for ${guests} guest(s) between ${checkIn} and ${checkOut}. Please adjust your dates or party size.
            </div>
          `;
          return;
        }

        renderSuiteOptions(data.suites, data.nights);
      } catch (err) {
        suiteOptionsContainer.innerHTML = `<div class="alert alert-error">Unable to query live availability. Please check server connection.</div>`;
      }
    };

    const renderSuiteOptions = (suites, nights) => {
      let html = '<div style="display: grid; gap: 1.5rem;">';
      suites.forEach((s) => {
        const isSelected = state.roomTypeId === s.id;
        html += `
          <div class="room-card" style="border: 2px solid ${isSelected ? 'var(--color-gold)' : 'var(--color-border-hairline)'};">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
              <div style="height: 220px; overflow: hidden;">
                <img src="${s.featured_image}" alt="${s.name}" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
              <div style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                  <div style="font-size: 0.72rem; color: var(--color-gold); text-transform: uppercase; font-weight: 600; letter-spacing: 0.1em; margin-bottom: 0.35rem;">
                    ${s.view_type} · ${s.room_size_sqft} SQ FT
                  </div>
                  <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">${s.name}</h3>
                  <p style="font-size: 0.88rem; margin-bottom: 0.75rem;">${s.short_description}</p>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--color-border-hairline); padding-top: 1rem;">
                  <div>
                    <span style="font-family: var(--font-serif); font-size: 1.4rem; font-weight: 600;">${s.price_per_night_formatted}</span>
                    <span style="font-size: 0.75rem; color: var(--color-text-muted);">/ night</span>
                    <div style="font-size: 0.75rem; color: var(--color-gold);">Total (${nights} nights + 18% GST): ${s.calculated_total_formatted}</div>
                  </div>
                  <button type="button" class="btn btn-gold btn-sm select-suite-btn" data-id="${s.id}">
                    Select Suite
                  </button>
                </div>
              </div>
            </div>
          </div>
        `;
      });
      html += '</div>';
      suiteOptionsContainer.innerHTML = html;

      document.querySelectorAll('.select-suite-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = parseInt(btn.dataset.id, 10);
          const suite = suites.find((x) => x.id === id);
          selectSuite(suite);
        });
      });
    };

    const selectSuite = (suite) => {
      state.roomTypeId = suite.id;
      state.selectedSuite = suite;
      fetchPricing(suite.id);
    };

    const fetchPricing = async (roomTypeId) => {
      try {
        const res = await fetch(`api/calculate-pricing.php?room_type_id=${roomTypeId}&check_in=${encodeURIComponent(state.checkIn)}&check_out=${encodeURIComponent(state.checkOut)}`);
        const pricing = await res.json();
        if (pricing.success) {
          state.pricing = pricing;
          renderSummary();
          goToStep(2);
        }
      } catch (err) {
        alert('Error calculating reservation pricing.');
      }
    };

    const renderSummary = () => {
      if (!state.pricing || !bookingSummaryCard) return;
      const p = state.pricing;
      bookingSummaryCard.innerHTML = `
        <div style="background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 1.75rem; box-shadow: var(--shadow-card);">
          <h4 style="font-size: 1.3rem; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border-hairline); padding-bottom: 0.75rem;">
            Reservation Summary
          </h4>
          <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.9rem; margin-bottom: 1.25rem;">
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Suite</span>
              <strong>${state.selectedSuite.name}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Dates</span>
              <span>${state.checkIn} to ${state.checkOut}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Duration</span>
              <span>${p.nights} Night(s)</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">Guests</span>
              <span>${state.guests} Guest(s)</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--color-border-hairline); padding-top: 0.65rem;">
              <span class="text-muted">Room Subtotal</span>
              <span>${p.subtotal_formatted}</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span class="text-muted">GST (${p.tax_rate}%)</span>
              <span>${p.tax_amount_formatted}</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-top: 2px solid var(--color-obsidian); padding-top: 0.75rem; font-size: 1.15rem;">
              <strong>Total (INR)</strong>
              <strong style="color: var(--color-text-main); font-family: var(--font-serif); font-size: 1.35rem;">
                ${p.total_amount_formatted}
              </strong>
            </div>
          </div>
          <div style="font-size: 0.75rem; color: var(--color-text-muted); background: var(--color-parchment); padding: 0.75rem; border-radius: var(--radius-xs);">
            ✓ Complimentary airport limousine transfer & 24h butler service included.
          </div>
        </div>
      `;
    };

    const goToStep = (stepNumber) => {
      document.querySelectorAll('.wizard-step-pane').forEach((p) => (p.style.display = 'none'));
      document.querySelectorAll('.wizard-indicator').forEach((ind) => {
        const indNum = parseInt(ind.dataset.step, 10);
        if (indNum === stepNumber) {
          ind.classList.add('active');
        } else if (indNum < stepNumber) {
          ind.classList.add('completed');
        } else {
          ind.classList.remove('active', 'completed');
        }
      });

      if (stepNumber === 1) step1.style.display = 'block';
      if (stepNumber === 2) step2.style.display = 'block';
      if (stepNumber === 3) step3.style.display = 'block';

      window.scrollTo({ top: bookingWizard.offsetTop - 80, behavior: 'smooth' });
    };

    // Attach step listeners
    document.getElementById('searchAvailabilityBtn')?.addEventListener('click', fetchAvailableSuites);
    document.getElementById('backToStep1Btn')?.addEventListener('click', () => goToStep(1));
    document.getElementById('proceedToReviewBtn')?.addEventListener('click', () => {
      const name = document.getElementById('guestName').value.trim();
      const email = document.getElementById('guestEmail').value.trim();
      const phone = document.getElementById('guestPhone').value.trim();

      if (!name || !email || !phone) {
        alert('Please fill in your name, email, and phone number.');
        return;
      }
      goToStep(3);
    });
    document.getElementById('backToStep2Btn')?.addEventListener('click', () => goToStep(2));

    // Submit Final Booking
    document.getElementById('finalConfirmBookingBtn')?.addEventListener('click', async () => {
      const confirmBtn = document.getElementById('finalConfirmBookingBtn');
      confirmBtn.disabled = true;
      confirmBtn.textContent = 'Processing Imperial Reservation...';

      const payload = {
        csrf_token: document.querySelector('input[name="csrf_token"]').value,
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
        const res = await fetch('api/create-booking.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result.success) {
          window.location.href = `confirmation.php?ref=${encodeURIComponent(result.booking_reference)}`;
        } else {
          bookingFeedback.innerHTML = `<div class="alert alert-error">${result.error}</div>`;
          confirmBtn.disabled = false;
          confirmBtn.textContent = 'Confirm & Reserve Suite';
        }
      } catch (err) {
        bookingFeedback.innerHTML = `<div class="alert alert-error">A communication error occurred. Please contact the concierge desk.</div>`;
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm & Reserve Suite';
      }
    });

    // Check if URL has params (e.g. redirected from homepage)
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
