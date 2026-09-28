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
<div style="background-color: var(--color-obsidian); color: #fff; padding: 8rem 0 3.5rem;">
  <div class="container">
    <div style="margin-bottom: 1.5rem;">
      <a href="<?= asset_url('rooms.php') ?>" style="color: var(--color-gold); font-size: var(--text-xs); text-transform: uppercase; letter-spacing: 0.1em; display: inline-flex; align-items: center; gap: 0.4rem;">
        ← Back to All Suites
      </a>
    </div>
    <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1.5rem;">
      <div>
        <div class="eyebrow" style="color: var(--color-gold-light);">Private Sanctuary</div>
        <h1 style="color: #fff; font-size: clamp(2.4rem, 4vw, 4rem); margin-bottom: 0.5rem;">
          <?= e($suite['name']) ?>
        </h1>
        <p style="color: rgba(255,255,255,0.75); font-size: 1.1rem; max-width: 650px;">
          <?= e($suite['short_description']) ?>
        </p>
      </div>
      <div>
        <div style="text-align: right;">
          <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.6); display: block;">From</span>
          <span style="font-family: var(--font-serif); font-size: 2.2rem; font-weight: 600; color: var(--color-gold);">
            <?= format_inr($suite['price_per_night']) ?>
          </span>
          <span style="font-size: 0.8rem; color: rgba(255,255,255,0.6);">/ night + taxes</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Photo Showcase -->
<div class="container" style="margin-top: -1.5rem; margin-bottom: 3.5rem;">
  <div style="border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-card); max-height: 560px;">
    <img 
      src="<?= asset_url($suite['featured_image']) ?>" 
      alt="<?= e($suite['name']) ?>" 
      style="width: 100%; height: 560px; object-fit: cover;"
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
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 3rem;">
        <div style="background: #fff; padding: 1.25rem; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs);">
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Dimensions</span>
          <strong><?= (int)$suite['room_size_sqft'] ?> Square Feet</strong>
        </div>
        <div style="background: #fff; padding: 1.25rem; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs);">
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Bed Configuration</span>
          <strong><?= e($suite['bed_type']) ?></strong>
        </div>
        <div style="background: #fff; padding: 1.25rem; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs);">
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Max Occupancy</span>
          <strong><?= (int)$suite['max_guests'] ?> Adults</strong>
        </div>
        <div style="background: #fff; padding: 1.25rem; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs);">
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Outlook</span>
          <strong><?= e($suite['view_type']) ?></strong>
        </div>
      </div>

      <!-- Included Amenities -->
      <h3 style="font-size: 1.4rem; margin-bottom: 1.25rem;">Inclusive Amenities & Privileges</h3>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 3rem;">
        <?php foreach ($amenities as $amenity): ?>
          <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
            <span style="color: var(--color-gold); font-size: 1.1rem; line-height: 1;">✦</span>
            <div>
              <strong style="font-size: 0.92rem; display: block;"><?= e($amenity['name']) ?></strong>
              <span class="text-muted" style="font-size: 0.8rem;"><?= e($amenity['description']) ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Right Column: Sticky Booking Card -->
    <div>
      <div style="position: sticky; top: 100px; background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 2rem; box-shadow: var(--shadow-card);">
        <div style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--color-border-hairline); padding-bottom: 1rem;">
          <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-text-muted);">Nightly Rate</span>
          <div style="display: flex; align-items: baseline; gap: 0.4rem; margin-top: 0.25rem;">
            <span style="font-family: var(--font-serif); font-size: 2.2rem; font-weight: 600; color: var(--color-text-main);">
              <?= format_inr($suite['price_per_night']) ?>
            </span>
            <span style="font-size: 0.85rem; color: var(--color-text-muted);">/ night</span>
          </div>
          <span style="font-size: 0.75rem; color: var(--color-gold);">* Plus 18% Goods & Services Tax (GST)</span>
        </div>

        <div style="margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.88rem;">
          <div style="display: flex; justify-content: space-between;">
            <span class="text-muted">Max Capacity:</span>
            <strong><?= (int)$suite['max_guests'] ?> Guests</strong>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span class="text-muted">Check-in:</span>
            <span>14:00 onwards</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span class="text-muted">Check-out:</span>
            <span>Until 11:00</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
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

<style>
@media (min-width: 992px) {
  .lg-grid-details {
    grid-template-columns: 2fr 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
