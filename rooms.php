<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Rooms & Suites Catalog
 */

$pageTitle = 'Suites & Penthouses | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Explore our collection of private suites, imperial verandah rooms, and signature presidential residences at Adishiv, New Delhi.';
$currentNav = 'rooms';

require_once __DIR__ . '/includes/header.php';

// Handle filter inputs
$filterGuests = isset($_GET['guests']) && $_GET['guests'] !== '' ? (int)$_GET['guests'] : 0;

$db = get_db();
$query = "SELECT * FROM room_types WHERE is_active = 1";
$params = [];

if ($filterGuests > 0) {
    $query .= " AND max_guests >= :guests";
    $params[':guests'] = $filterGuests;
}

$query .= " ORDER BY sort_order ASC, price_per_night ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$suites = $stmt->fetchAll();
?>

<!-- Page Editorial Header -->
<div style="background-color: var(--color-obsidian); color: #fff; padding: 9rem 0 5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Private Quarters</div>
    <h1 style="color: #fff; font-size: clamp(2.5rem, 4vw, 4.5rem); margin-bottom: 1.25rem;">
      Suites & Residences
    </h1>
    <p style="color: rgba(255,255,255,0.7); max-width: 640px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7;">
      Each chamber is a private sanctuary of handcrafted teakwood, pure raw silk textiles, Italian marble bathrooms, and bespoke 24-hour imperial butler service.
    </p>
  </div>
</div>

<!-- Filter Bar -->
<div style="background-color: #FFFFFF; border-bottom: 1px solid var(--color-border-hairline); padding: 1.25rem 0;">
  <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div style="font-size: var(--text-sm); color: var(--color-text-muted);">
      Showing <strong><?= count($suites) ?></strong> Available Categories
    </div>

    <form method="GET" action="rooms.php" style="display: flex; align-items: center; gap: 1rem;">
      <label for="filterGuests" style="font-size: var(--text-xs); font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">
        Guests:
      </label>
      <select id="filterGuests" name="guests" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 0.4rem 1rem; font-size: var(--text-xs);">
        <option value="">Any Capacity</option>
        <option value="2" <?= $filterGuests === 2 ? 'selected' : '' ?>>2+ Guests</option>
        <option value="3" <?= $filterGuests === 3 ? 'selected' : '' ?>>3+ Guests</option>
        <option value="4" <?= $filterGuests === 4 ? 'selected' : '' ?>>4+ Guests</option>
      </select>
      <?php if ($filterGuests > 0): ?>
        <a href="rooms.php" style="font-size: var(--text-xs); color: var(--color-terracotta); text-decoration: underline;">Clear Filter</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Suites Grid -->
<div class="section-spacing container">
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2.5rem;">
    <?php foreach ($suites as $suite): ?>
      <article class="room-card" data-reveal>
        <div class="room-card-media" style="height: 320px;">
          <span class="room-badge"><?= e($suite['view_type']) ?></span>
          <img 
            src="<?= asset_url($suite['featured_image']) ?>" 
            alt="<?= e($suite['name']) ?>" 
            class="room-card-img"
            loading="lazy"
          >
        </div>
        <div class="room-card-body">
          <div style="font-size: 0.72rem; color: var(--color-gold); font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 0.35rem;">
            <?= (int)$suite['room_size_sqft'] ?> SQ FT · <?= e($suite['bed_type']) ?>
          </div>
          <h2 class="room-title" style="font-size: 1.7rem;"><?= e($suite['name']) ?></h2>
          <p style="font-size: 0.95rem; line-height: 1.65; margin-bottom: 1.5rem;">
            <?= e($suite['short_description']) ?>
          </p>

          <div class="room-specs">
            <span class="room-spec-item">👥 Capacity: <?= (int)$suite['max_guests'] ?> Guests</span>
            <span class="room-spec-item">🌅 <?= e($suite['view_type']) ?></span>
            <span class="room-spec-item">🛎 24h Personal Butler</span>
          </div>

          <div class="room-card-footer">
            <div class="room-price-wrap">
              <span class="room-price-val"><?= format_inr($suite['price_per_night']) ?></span>
              <span class="room-price-unit">per night + 18% GST</span>
            </div>
            <div style="display: flex; gap: 0.65rem;">
              <a href="<?= asset_url('room-details.php?slug=' . urlencode($suite['slug'])) ?>" class="btn btn-dark btn-sm">
                Details & Floorplan
              </a>
              <a href="<?= asset_url('booking.php?room_type_id=' . (int)$suite['id']) ?>" class="btn btn-gold btn-sm">
                Book Suite
              </a>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
