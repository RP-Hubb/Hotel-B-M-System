<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Room & Suite Deep Editorial Showcase
 */

require_once __DIR__ . '/includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
$suite = $slug ? get_room_type_by_slug($slug) : null;

// If invalid slug, fallback to first available suite
if (!$suite) {
    $all = get_room_types(true);
    $suite = $all[0] ?? null;
}

if (!$suite) {
    header('Location: rooms.php');
    exit;
}

$pageTitle = e($suite['name']) . ' | Adishiv Luxury Hotel New Delhi';
$metaDescription = e($suite['short_description']);
$currentNav = 'rooms';

require_once __DIR__ . '/includes/header.php';

// Fetch all amenities
$amenities = get_amenities();
// Fetch other suites for "Related Quarters"
$otherSuites = array_filter(get_room_types(true), fn($x) => (int)$x['id'] !== (int)$suite['id']);
?>

<!-- Room Header -->
<div class="suite-detail-header">
  <div class="container">
    <div class="suite-detail-backlink">
      <a href="<?= asset_url('rooms.php') ?>">
        ← Back to All Suites
      </a>
    </div>
    <div class="suite-detail-heading-row">
      <div>
        <div class="eyebrow page-hero-eyebrow">Private Sanctuary</div>
        <h1 class="page-hero-title">
          <?= e($suite['name']) ?>
        </h1>
        <p class="page-hero-desc" style="text-align: left; margin: 0;">
          <?= e($suite['short_description']) ?>
        </p>
      </div>
      <div>
        <div class="suite-detail-rate-box">
          <span class="suite-rate-from">From</span>
          <span class="suite-rate-figure">
            <?= format_inr($suite['price_per_night']) ?>
          </span>
          <span class="suite-rate-taxes">/ night + taxes</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Photo Showcase -->
<div class="container" style="margin-top: -1.5rem; margin-bottom: 3.5rem;">
  <div class="suite-showcase-frame">
    <img 
      src="<?= asset_url($suite['featured_image']) ?>" 
      alt="<?= e($suite['name']) ?>" 
      class="suite-showcase-img"
      fetchpriority="high"
    >
  </div>
</div>

<!-- Details & Booking Layout -->
<div class="container section-spacing" style="padding-top: 0;">
  <div style="display: grid; grid-template-columns: 1fr; gap: 3.5rem;" class="lg-grid-details">
    <!-- Left Column: Editorial Description & Amenities -->
    <div>
      <h2 style="font-size: var(--text-xl); margin-bottom: 1.5rem;">The Chamber Experience</h2>
      <p style="font-size: 1.08rem; line-height: 1.85; color: var(--color-text-main); margin-bottom: 2rem;">
        <?= nl2br(e($suite['description'])) ?>
      </p>

      <!-- Key Specifications Table -->
      <h3 style="font-size: 1.4rem; margin-bottom: 1.25rem;">Suite Specifications</h3>
      <div class="suite-specs-grid">
        <div class="spec-tile">
          <span class="spec-tile-label">Dimensions</span>
          <strong class="spec-tile-value"><?= (int)$suite['room_size_sqft'] ?> Sq. Ft.</strong>
        </div>
        <div class="spec-tile">
          <span class="spec-tile-label">Bed Configuration</span>
          <strong class="spec-tile-value"><?= e($suite['bed_type']) ?></strong>
        </div>
        <div class="spec-tile">
          <span class="spec-tile-label">Max Occupancy</span>
          <strong class="spec-tile-value"><?= (int)$suite['max_guests'] ?> Adults</strong>
        </div>
        <div class="spec-tile">
          <span class="spec-tile-label">Outlook</span>
          <strong class="spec-tile-value"><?= e($suite['view_type']) ?></strong>
        </div>
      </div>

      <!-- Included Amenities -->
      <h3 style="font-size: 1.4rem; margin-bottom: 1.25rem;">Inclusive Amenities & Privileges</h3>
      <div class="amenities-editorial-grid">
        <?php foreach ($amenities as $amenity): ?>
          <div class="amenity-tile">
            <span class="amenity-bullet">✦</span>
            <div>
              <strong class="amenity-name"><?= e($amenity['name']) ?></strong>
              <span class="amenity-desc"><?= e($amenity['description']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right Column: Sticky Booking Card -->
    <div>
      <div class="sticky-booking-card">
        <div class="sticky-booking-header">
          <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted);">Nightly Rate</span>
          <div class="sticky-pricing-row">
            <span class="sticky-price-num">
              <?= format_inr($suite['price_per_night']) ?>
            </span>
            <span style="font-size: 0.85rem; color: var(--color-text-muted);">/ night</span>
          </div>
          <span style="font-size: 0.75rem; color: var(--color-gold);">* Plus 18% Goods & Services Tax (GST)</span>
        </div>

        <div class="sticky-details-list">
          <div class="sticky-detail-row">
            <span class="text-muted">Max Capacity:</span>
            <strong><?= (int)$suite['max_guests'] ?> Guests</strong>
          </div>
          <div class="sticky-detail-row">
            <span class="text-muted">Check-in:</span>
            <span>14:00 onwards</span>
          </div>
          <div class="sticky-detail-row">
            <span class="text-muted">Check-out:</span>
            <span>Until 11:00</span>
          </div>
          <div class="sticky-detail-row">
            <span class="text-muted">Cancellation:</span>
            <span style="color: var(--color-success);">Complimentary up to 48h prior</span>
          </div>
        </div>

        <a href="<?= asset_url('booking.php?room_type_id=' . (int)$suite['id']) ?>" class="btn btn-gold btn-lg" style="width: 100%; text-align: center; margin-bottom: 1rem;">
          Reserve This Suite
        </a>

        <div style="text-align: center; font-size: 0.78rem; color: var(--color-text-muted);">
          Questions? Call Concierge: <a href="tel:+911149827700" style="color: var(--color-gold); font-weight: 600;">+91 11 4982 7700</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
