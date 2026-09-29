<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Multi-Step Booking Wizard
 */

$pageTitle = 'Reserve Your Stay | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Select your dates, choose from our private suites and residences, and complete your luxury reservation at Adishiv, New Delhi.';
$currentNav = 'booking';

require_once __DIR__ . '/includes/header.php';

$currentUser = current_user();
?>

<!-- Header Banner -->
<div class="page-hero-banner" style="padding: 7.5rem 0 3.5rem;">
  <div class="container-narrow">
    <div class="eyebrow center page-hero-eyebrow">Imperial Reservations</div>
    <h1 class="page-hero-title">
      Reserve Your Sanctuary
    </h1>
    <p class="page-hero-desc">
      Direct bookings enjoy complimentary airport transfers, daily imperial breakfast, and flexible cancellation.
    </p>
  </div>
</div>

<!-- Wizard Step Navigation -->
<div class="wizard-sticky-nav">
  <div class="container-narrow wizard-steps-row">
    <div class="wizard-indicator active" data-step="1">
      <span class="wizard-badge">1</span>
      <span>Dates & Suites</span>
    </div>

    <div class="wizard-indicator" data-step="2">
      <span class="wizard-badge">2</span>
      <span>Guest Information</span>
    </div>

    <div class="wizard-indicator" data-step="3">
      <span class="wizard-badge">3</span>
      <span>Review & Confirm</span>
    </div>
  </div>
</div>

<!-- Wizard Container -->
<div class="container section-spacing" id="bookingWizard" style="padding-top: 2.5rem; max-width: 1140px;">
  <div id="bookingFeedback"></div>
  <?= csrf_field() ?>

  <!-- STEP 1: DATES & SUITE SELECTION -->
  <div class="wizard-step-pane" id="wizardStep1">
    <!-- Date & Guest Controls -->
    <div class="wizard-card-surface">
      <h3 style="font-size: 1.4rem; margin-bottom: 1.25rem;">Select Travel Dates & Party Size</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; align-items: flex-end;">
        <div>
          <label for="bookCheckIn" class="form-label">Check-in Date</label>
          <input type="date" id="bookCheckIn" name="check_in" class="form-control" required>
        </div>
        <div>
          <label for="bookCheckOut" class="form-label">Check-out Date</label>
          <input type="date" id="bookCheckOut" name="check_out" class="form-control" required>
        </div>
        <div>
          <label for="bookGuests" class="form-label">Total Guests</label>
          <select id="bookGuests" name="guests" class="form-control">
            <option value="1">1 Guest</option>
            <option value="2" selected>2 Guests</option>
            <option value="3">3 Guests</option>
            <option value="4">4 Guests</option>
          </select>
        </div>
        <div>
          <button type="button" id="searchAvailabilityBtn" class="btn btn-gold" style="width: 100%; height: 48px;">
            Update Availability
          </button>
        </div>
      </div>
    </div>

    <!-- Available Suites Output -->
    <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;">Available Imperial Suites</h3>
    <div id="suiteOptionsContainer">
      <!-- Populated via AJAX by booking-widget.js -->
    </div>
  </div>

  <!-- STEP 2: GUEST DETAILS & SPECIAL REQUESTS -->
  <div class="wizard-step-pane" id="wizardStep2" style="display: none;">
    <div style="display: grid; grid-template-columns: 1fr; gap: 2.5rem;" class="lg-grid-step2">
      <!-- Guest Details Form -->
      <div class="wizard-card-surface">
        <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem;">Guest Identification</h3>
        <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
          Please enter primary resident details as they appear on government identification.
        </p>

        <div class="form-group">
          <label for="guestName" class="form-label">Full Name *</label>
          <input 
            type="text" 
            id="guestName" 
            name="name" 
            class="form-control" 
            placeholder="e.g. Maharani Gayatri Devi / Vikramaditya Singhania" 
            value="<?= e($currentUser['name'] ?? '') ?>" 
            required
          >
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="guestEmail" class="form-label">Email Address *</label>
            <input 
              type="email" 
              id="guestEmail" 
              name="email" 
              class="form-control" 
              placeholder="e.g. resident@domain.com" 
              value="<?= e($currentUser['email'] ?? '') ?>" 
              required
            >
          </div>
          <div class="form-group">
            <label for="guestPhone" class="form-label">Contact Mobile *</label>
            <input 
              type="tel" 
              id="guestPhone" 
              name="phone" 
              class="form-control" 
              placeholder="e.g. +91 98110 12345" 
              value="<?= e($currentUser['phone'] ?? '') ?>" 
              required
            >
          </div>
        </div>

        <div class="form-group">
          <label for="specialRequests" class="form-label">Bespoke Preferences & Requests</label>
          <textarea 
            id="specialRequests" 
            name="special_requests" 
            class="form-control" 
            placeholder="Dietary requirements, high floor preference, airport limousine arrival time, feather-free pillows..."
          ></textarea>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
          <button type="button" id="backToStep1Btn" class="btn btn-dark btn-sm">
            ← Change Suite or Dates
          </button>
          <button type="button" id="proceedToReviewBtn" class="btn btn-gold">
            Proceed to Review & Confirmation →
          </button>
        </div>
      </div>

      <!-- Live Summary Card -->
      <div id="bookingSummaryCard">
        <!-- Injected by booking-widget.js -->
      </div>
    </div>
  </div>

  <!-- STEP 3: REVIEW & GUARANTEE -->
  <div class="wizard-step-pane" id="wizardStep3" style="display: none;">
    <div class="wizard-card-surface" style="max-width: 760px; margin: 0 auto; box-shadow: var(--shadow-card);">
      <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem; text-align: center;">Review & Confirm Your Reservation</h3>
      <p style="text-align: center; font-size: 0.95rem; color: var(--color-text-muted); margin-bottom: 2rem;">
        No upfront payment charged today. Payment is settled at the hotel concierge desk upon arrival.
      </p>

      <div style="background: var(--color-parchment); border: 1px solid var(--color-border-subtle); border-radius: var(--radius-xs); padding: 1.5rem; margin-bottom: 2rem;">
        <h4 style="font-size: 1.15rem; margin-bottom: 1rem;">Payment Guarantee Option</h4>
        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; margin-bottom: 0.75rem;">
          <input type="radio" name="payment_method" value="pay_at_hotel" checked>
          <span><strong>Pay at Hotel (Counter Check-in)</strong> — Settle via UPI, Credit Card, or Cash on arrival.</span>
        </label>
        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
          <input type="radio" name="payment_method" value="upi">
          <span><strong>UPI / Net Banking Guarantee</strong> — Instant digital settlement voucher.</span>
        </label>
      </div>

      <div style="font-size: 0.82rem; color: var(--color-text-muted); line-height: 1.6; margin-bottom: 2rem;">
        By confirming, you agree to Adishiv's check-in policy (14:00 check-in, 11:00 check-out) and standard 48-hour cancellation policy.
      </div>

      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <button type="button" id="backToStep2Btn" class="btn btn-dark btn-sm">
          ← Edit Guest Details
        </button>
        <button type="button" id="finalConfirmBookingBtn" class="btn btn-gold btn-lg">
          Confirm & Reserve Suite
        </button>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
