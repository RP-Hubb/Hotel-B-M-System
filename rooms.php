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
<div class="page-hero-banner">
  <div class="container-narrow">
    <div class="eyebrow center page-hero-eyebrow">Private Quarters</div>
    <h1 class="page-hero-title">
      Suites & Residences
    </h1>
    <p class="page-hero-desc">
      Each chamber is a private sanctuary of handcrafted teakwood, pure raw silk textiles, Italian marble bathrooms, and bespoke 24-hour imperial butler service.
    </p>
  </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar-wrap">
  <div class="container filter-bar-container">
    <div class="filter-stat">
      Showing <strong><?= count($suites) ?></strong> Available Categories
    </div>

    <form method="GET" action="rooms.php" class="filter-controls">
      <label for="filterGuests" class="filter-label">
        Guests:
      </label>
      <select id="filterGuests" name="guests" onchange="this.form.submit()" class="filter-select-input">
        <option value="">Any Capacity</option>
        <option value="2" <?= $filterGuests === 2 ? 'selected' : '' ?>>2+ Guests</option>
        <option value="3" <?= $filterGuests === 3 ? 'selected' : '' ?>>3+ Guests</option>
        <option value="4" <?= $filterGuests === 4 ? 'selected' : '' ?>>4+ Guests</option>
      </select>
      <?php if ($filterGuests > 0): ?>
        <a href="rooms.php" class="filter-reset-link">Clear Filter</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Suites Grid -->
<div class="section-spacing container">
  <div class="suites-responsive-grid">
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
          <div class="room-card-spec-meta">
            <?= (int)$suite['room_size_sqft'] ?> SQ FT · <?= e($suite['bed_type']) ?>
          </div>
          <h2 class="room-title"><?= e($suite['name']) ?></h2>
          <p class="room-desc">
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
            <div class="room-actions-group">
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
