<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Concierge & Inquiries
 */

$pageTitle = 'Concierge & Inquiries | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Contact the concierge desk at Adishiv Hotel & Suites, Chanakyapuri, New Delhi. Direct reservations, private events, and airport transfers.';
$currentNav = 'contact';

require_once __DIR__ . '/includes/header.php';

$presetSubject = trim($_GET['subject'] ?? 'General Concierge Inquiry');
?>

<!-- Header -->
<div style="background-color: var(--color-obsidian); color: #fff; padding: 8.5rem 0 4.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">At Your Service</div>
    <h1 style="color: #fff; font-size: clamp(2.5rem, 4vw, 4.5rem); margin-bottom: 1.25rem;">
      Concierge & Inquiries
    </h1>
    <p style="color: rgba(255,255,255,0.7); max-width: 620px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7;">
      Our 24-hour head concierge and reservations team are at your complete disposal to arrange bespoke itineraries, private dining, or diplomatic receptions.
    </p>
  </div>
</div>

<!-- Contact Content Grid -->
<div class="section-spacing container">
  <div style="display: grid; grid-template-columns: 1fr; gap: 4rem;" class="lg-grid-contact">
    <!-- Left Column: Contact Cards & Coordinates -->
    <div>
      <h2 style="font-size: var(--text-xl); margin-bottom: 1.5rem;">Direct Communications</h2>

      <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2.5rem;">
        <div style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 1.5rem;">
          <strong style="color: var(--color-gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.12em; display: block; margin-bottom: 0.4rem;">Physical Location</strong>
          <p style="font-size: 0.95rem; margin-bottom: 0.25rem; color: var(--color-text-main);">
            <?= e(get_setting('hotel_address', 'G-59, Connaught Circus, Connaught Place, New Delhi, Delhi 110001')) ?>
          </p>
          <span style="font-size: 0.8rem; color: var(--color-text-muted);">Central Delhi · 35 minutes from Indira Gandhi International Airport (DEL)</span>
        </div>

        <div style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 1.5rem;">
          <strong style="color: var(--color-gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.12em; display: block; margin-bottom: 0.4rem;">Head Concierge & Desk</strong>
          <p style="font-size: 1.2rem; font-family: var(--font-serif); font-weight: 600; margin-bottom: 0.25rem;">
            <a href="tel:<?= e(str_replace(' ', '', get_setting('hotel_phone', '+911149827700'))) ?>" style="color: var(--color-text-main);">
              <?= e(get_setting('hotel_phone', '+91 11 4982 7700')) ?>
            </a>
          </p>
          <span style="font-size: 0.82rem; color: var(--color-text-muted);">Available 24 hours, 7 days a week</span>
        </div>

        <div style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 1.5rem;">
          <strong style="color: var(--color-gold); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.12em; display: block; margin-bottom: 0.4rem;">Electronic Mail</strong>
          <p style="font-size: 1.05rem; margin-bottom: 0.25rem;">
            <a href="mailto:<?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>" style="color: var(--color-text-main);">
              <?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>
            </a>
          </p>
          <span style="font-size: 0.82rem; color: var(--color-text-muted);">Inquiries answered within 2 hours</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Interactive Form -->
    <div style="background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 2.5rem; box-shadow: var(--shadow-card);">
      <h3 style="font-size: 1.6rem; margin-bottom: 0.5rem;">Transmit an Inquiry</h3>
      <p style="font-size: 0.9rem; color: var(--color-text-muted); margin-bottom: 2rem;">
        Complete the dispatch form below and our head concierge will attend to your request.
      </p>

      <div id="contactFormFeedback"></div>

      <form id="contactForm">
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="contactName" class="form-label">Full Name *</label>
          <input type="text" id="contactName" name="name" class="form-control" placeholder="Your name" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div class="form-group">
            <label for="contactEmail" class="form-label">Email Address *</label>
            <input type="email" id="contactEmail" name="email" class="form-control" placeholder="name@domain.com" required>
          </div>
          <div class="form-group">
            <label for="contactPhone" class="form-label">Contact Telephone</label>
            <input type="tel" id="contactPhone" name="phone" class="form-control" placeholder="+91 / International">
          </div>
        </div>

        <div class="form-group">
          <label for="contactSubject" class="form-label">Subject</label>
          <input type="text" id="contactSubject" name="subject" class="form-control" value="<?= e($presetSubject) ?>" required>
        </div>

        <div class="form-group">
          <label for="contactMessage" class="form-label">Message Details *</label>
          <textarea id="contactMessage" name="message" class="form-control" placeholder="Specify your desired dates, party requirements, or bespoke inquiries..." required></textarea>
        </div>

        <button type="submit" id="contactSubmitBtn" class="btn btn-gold btn-lg" style="width: 100%;">
          Transmit to Concierge
        </button>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('contactForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('contactSubmitBtn');
  const fb = document.getElementById('contactFormFeedback');

  btn.disabled = true;
  btn.textContent = 'Transmitting...';
  fb.innerHTML = '';

  const payload = {
    csrf_token: form.querySelector('input[name="csrf_token"]').value,
    name: document.getElementById('contactName').value.trim(),
    email: document.getElementById('contactEmail').value.trim(),
    phone: document.getElementById('contactPhone').value.trim(),
    subject: document.getElementById('contactSubject').value.trim(),
    message: document.getElementById('contactMessage').value.trim()
  };

  try {
    const res = await fetch('api/contact-submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      fb.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
      form.reset();
    } else {
      fb.innerHTML = `<div class="alert alert-error">${data.error}</div>`;
    }
  } catch (err) {
    fb.innerHTML = `<div class="alert alert-error">Unable to transmit message. Please call the concierge directly.</div>`;
  } finally {
    btn.disabled = false;
    btn.textContent = 'Transmit to Concierge';
  }
});
</script>

<style>
@media (min-width: 992px) {
  .lg-grid-contact {
    grid-template-columns: 1fr 1.3fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
