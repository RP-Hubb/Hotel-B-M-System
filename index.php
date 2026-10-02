<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Homepage — Sanctuary in the Imperial Capital
 */

require_once __DIR__ . '/includes/header.php';

// Fetch active room types for the featured suites section
$roomTypes = get_room_types(true);
$amenities = get_amenities(true);
?>

<!-- 1. EDITORIAL HERO SECTION -->
<section class="hero-editorial-wrap" aria-label="Adishiv Imperial Sanctuary Entrance">
  <img 
    src="<?= asset_url('assets/images/branding/hero-facade.jpg') ?>" 
    alt="Adishiv Luxury Hotel Facade and Reflecting Pool at Dusk" 
    class="hero-bg-media"
    fetchpriority="high"
  >
  <div class="hero-gradient-overlay"></div>

  <div class="hero-content">
    <div class="eyebrow" style="color: var(--color-gold-light); margin-bottom: 1.25rem;">
      28°37'N · Connaught Place · New Delhi
    </div>
    <h1 class="hero-headline">
      Sanctuary in the Imperial Capital.
    </h1>
    <p class="hero-subheadline">
      Where Delhi's royal heritage meets quiet contemporary architectural minimalism. An intimate luxury hotel of private courtyards, reflective lotus waters, and bespoke butler service.
    </p>
    <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
      <a href="#quickBookingForm" class="btn btn-gold btn-lg">Explore Availability</a>
      <a href="<?= asset_url('rooms.php') ?>" class="btn btn-outline-gold btn-lg">View Suites</a>
    </div>
  </div>
</section>

<!-- 2. INTEGRATED QUICK BOOKING STRIP -->
<div class="container booking-bar-wrapper">
  <form id="quickBookingForm" class="booking-bar" role="search" aria-label="Check room availability">
    <div class="booking-field-group">
      <label for="quickCheckIn" class="booking-label">Check-in</label>
      <input type="date" id="quickCheckIn" name="check_in" class="booking-input" required>
    </div>

    <div class="booking-field-group">
      <label for="quickCheckOut" class="booking-label">Check-out</label>
      <input type="date" id="quickCheckOut" name="check_out" class="booking-input" required>
    </div>

    <div class="booking-field-group">
      <label for="quickGuests" class="booking-label">Guests</label>
      <select id="quickGuests" name="guests" class="booking-select">
        <option value="1">1 Guest</option>
        <option value="2" selected>2 Guests</option>
        <option value="3">3 Guests</option>
        <option value="4">4 Guests</option>
      </select>
    </div>

    <div class="booking-field-group">
      <label for="quickRoomType" class="booking-label">Suite Category</label>
      <select id="quickRoomType" name="room_type" class="booking-select">
        <option value="">All Categories</option>
        <?php foreach ($roomTypes as $rt): ?>
          <option value="<?= (int)$rt['id'] ?>"><?= e($rt['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <button type="submit" class="btn btn-gold" style="width: 100%; height: 50px;">
        Check Availability
      </button>
    </div>
  </form>
</div>

<!-- 3. HOTEL INTRODUCTION NARRATIVE (Editorial Prose) -->
<section class="section-spacing container-narrow" style="padding-top: var(--space-4xl);" aria-labelledby="introHeading">
  <div class="text-center" data-reveal>
    <div class="eyebrow center">The Architectural Philosophy</div>
    <h2 id="introHeading" style="font-size: clamp(2.2rem, 3.2vw, 3.6rem); margin-bottom: 1.75rem; line-height: 1.2;">
      A timeless stillness amidst the rhythm of India's capital.
    </h2>
    <p class="lead" style="margin-bottom: 2rem;">
      Set within the tranquil greenery of Chanakyapuri’s diplomatic quarter, <strong>Adishiv</strong> was conceived as a poetic tribute to New Delhi’s dual souls: the quiet geometric grandeur of Mughal stone pavilions and the restrained elegance of contemporary bespoke craft.
    </p>
    <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 2.5rem;">
      Every stone has been hand-chiseled from regional red sandstone and pristine Makrana marble. Within our walls, the clamor of the metropolis fades into the soft murmur of water flowing over stepped lotus basins, shaded frangipani verandahs, and the understated scent of Indian sandalwood.
    </p>
    <div>
      <a href="<?= asset_url('about.php') ?>" class="btn btn-dark">
        Read Our Story & Architecture
      </a>
    </div>
  </div>
</section>

<!-- 4. FEATURED SUITES SHOWCASE -->
<section class="section-spacing" style="background-color: var(--color-parchment); border-top: 1px solid var(--color-border-subtle); border-bottom: 1px solid var(--color-border-subtle);" aria-labelledby="suitesHeading">
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 3rem; flex-wrap: wrap; gap: 1.5rem;">
      <div>
        <div class="eyebrow">Private Residences</div>
        <h2 id="suitesHeading" style="font-size: var(--text-2xl);">Curated Suites & Penthouses</h2>
      </div>
      <div>
        <a href="<?= asset_url('rooms.php') ?>" class="btn btn-outline-gold">
          View All <?= count($roomTypes) ?> Categories →
        </a>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem;">
      <?php foreach ($roomTypes as $room): ?>
        <article class="room-card" data-reveal>
          <div class="room-card-media">
            <span class="room-badge"><?= e($room['view_type']) ?></span>
            <img 
              src="<?= asset_url($room['featured_image']) ?>" 
              alt="<?= e($room['name']) ?>" 
              class="room-card-img"
              loading="lazy"
            >
          </div>
          <div class="room-card-body">
            <h3 class="room-title"><?= e($room['name']) ?></h3>
            <p style="font-size: 0.92rem; line-height: 1.6; margin-bottom: 1.25rem;">
              <?= e($room['short_description']) ?>
            </p>
            <div class="room-specs">
              <span class="room-spec-item">📏 <?= (int)$room['room_size_sqft'] ?> sq.ft</span>
              <span class="room-spec-item">🛏 <?= e($room['bed_type']) ?></span>
              <span class="room-spec-item">👥 Up to <?= (int)$room['max_guests'] ?> Guests</span>
            </div>
            <div class="room-card-footer">
              <div class="room-price-wrap">
                <span class="room-price-val"><?= format_inr($room['price_per_night']) ?></span>
                <span class="room-price-unit">per night + taxes</span>
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <a href="<?= asset_url('room-details.php?slug=' . urlencode($room['slug'])) ?>" class="btn btn-dark btn-sm">
                  Details
                </a>
                <a href="<?= asset_url('booking.php?room_type_id=' . (int)$room['id']) ?>" class="btn btn-gold btn-sm">
                  Book
                </a>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- 5. THE COURTYARD POOL & WELLNESS EXPERIENCE (Haven Annecy Inspired Editorial Layout) -->
<section class="section-spacing container" aria-labelledby="wellnessHeading">
  <div style="display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center;" class="lg-grid-2">
    <div style="position: relative;" data-reveal>
      <img 
        src="<?= asset_url('assets/images/wellness/courtyard-pool.jpg') ?>" 
        alt="Temperature-controlled Heated Marble Courtyard Pool at Adishiv" 
        style="width: 100%; border-radius: var(--radius-xs); box-shadow: var(--shadow-card);"
        loading="lazy"
      >
      <div style="position: absolute; bottom: 2rem; left: 2rem; background: rgba(12,15,18,0.85); backdrop-filter: var(--glass-blur); padding: 1.25rem 1.75rem; border-left: 2px solid var(--color-gold); max-width: 320px; color: #fff;">
        <span style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--color-gold); display: block; margin-bottom: 0.35rem;">Thermal Hydrotherapy</span>
        <span style="font-family: var(--font-serif); font-size: 1.15rem;">Heated all year to 29°C beneath Mughal sandstone arches</span>
      </div>
    </div>

    <div data-reveal>
      <div class="eyebrow">Restorative Healing</div>
      <h2 id="wellnessHeading" style="font-size: clamp(2rem, 3vw, 2.8rem); margin-bottom: 1.5rem; line-height: 1.2;">
        Ancient Ayurvedic wisdom, calibrated for the modern connoisseur.
      </h2>
      <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem;">
        The Spa at Adishiv draws upon centuries of Vedic botanical science. Every therapy begins with a private diagnostic consultation by our resident Ayurvedic physician, followed by custom-blended herbal formulations prepared fresh from indigenous Himalayan and Kerala botanicals.
      </p>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 2rem; font-size: 0.9rem;">
        <div>
          <strong style="color: var(--color-text-main); display: block; margin-bottom: 0.25rem;">🌿 Shirodhara & Abhyanga</strong>
          <span class="text-muted">Rhythmic warm herbal oil massages for deep neurological restoration.</span>
        </div>
        <div>
          <strong style="color: var(--color-text-main); display: block; margin-bottom: 0.25rem;">🧘 Sunrise Yoga Pavilion</strong>
          <span class="text-muted">Private dawn sessions facing our quiet reflecting pools.</span>
        </div>
      </div>
      <div>
        <a href="<?= asset_url('experiences.php') ?>" class="btn btn-outline-gold">
          Explore Spa & Wellness Menu
        </a>
      </div>
    </div>
  </div>
</section>

<!-- 6. CULINARY SHOWCASE: AURA FINE DINING & THE PEACOCK BAR -->
<section class="section-spacing" style="background-color: var(--color-obsidian); color: var(--color-text-white);" aria-labelledby="diningHeading">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center;" class="lg-grid-2">
      <div data-reveal>
        <div class="eyebrow" style="color: var(--color-gold-light);">Gastronomy</div>
        <h2 id="diningHeading" style="font-size: clamp(2.2rem, 3vw, 3.2rem); color: var(--color-text-white); margin-bottom: 1.5rem; line-height: 1.2;">
          Aura: Modern Indian Imperial Gastronomy.
        </h2>
        <p style="color: rgba(255,255,255,0.75); font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.75rem;">
          Under the crystal chandeliers of Aura, historic royal recipes from Awadh, Kashmir, and Rajasthan are reimagined through contemporary culinary techniques and sustainably sourced organic regional produce.
        </p>
        <div style="border-left: 2px solid var(--color-gold); padding-left: 1.5rem; margin-bottom: 2.25rem;">
          <blockquote style="font-family: var(--font-serif); font-size: 1.35rem; font-style: italic; color: rgba(255,255,255,0.9); margin-bottom: 0.5rem;">
            "An extraordinary triumph of flavor and grace, anchoring Delhi as an undisputed culinary capital."
          </blockquote>
          <cite style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--color-gold);">
            — Global Epicure Magazine
          </cite>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <a href="<?= asset_url('dining.php') ?>" class="btn btn-gold">
            Reserve Table at Aura
          </a>
          <a href="<?= asset_url('dining.php#menus') ?>" class="btn btn-outline-gold">
            View Degustation Menu
          </a>
        </div>
      </div>

      <div data-reveal>
        <img 
          src="<?= asset_url('assets/images/dining/aura-restaurant.jpg') ?>" 
          alt="Aura Fine Dining Restaurant Interior at Adishiv" 
          style="width: 100%; border-radius: var(--radius-xs); border: 1px solid var(--color-border-dark); box-shadow: var(--shadow-modal);"
          loading="lazy"
        >
      </div>
    </div>
  </div>
</section>

<!-- 7. HOTEL AMENITIES GRID -->
<section class="section-spacing container" aria-labelledby="amenitiesHeading">
  <div class="text-center" style="margin-bottom: 3.5rem;" data-reveal>
    <div class="eyebrow center">Inclusive Hospitality</div>
    <h2 id="amenitiesHeading" style="font-size: var(--text-2xl);">Bespoke Services for Every Resident</h2>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
    <?php foreach ($amenities as $am): ?>
      <div style="background: #FFFFFF; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 2rem; box-shadow: var(--shadow-subtle);" data-reveal>
        <div style="font-size: 1.6rem; margin-bottom: 1rem; color: var(--color-gold);">✦</div>
        <h4 style="font-size: 1.2rem; margin-bottom: 0.65rem;"><?= e($am['name']) ?></h4>
        <p style="font-size: 0.88rem; color: var(--color-text-muted); margin-bottom: 0;">
          <?= e($am['description']) ?>
        </p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- 8. INSTANT BOOKING INVITATION BANNER -->
<section style="background-color: var(--color-obsidian-soft); color: #fff; padding: var(--space-4xl) 0; border-top: 1px solid var(--color-border-subtle); text-align: center;">
  <div class="container-narrow" data-reveal>
    <div class="eyebrow center" style="color: var(--color-gold-light);">Your Delhi Retreat Awaits</div>
    <h2 style="font-size: clamp(2.4rem, 3.5vw, 4rem); color: #fff; margin-bottom: 1.25rem;">
      Experience the Sanctuary of Adishiv.
    </h2>
    <p style="color: rgba(255,255,255,0.7); max-width: 620px; margin: 0 auto 2.5rem; font-size: 1.05rem;">
      Whether arriving for high-level diplomatic affairs, cultural discovery, or a tranquil holiday, our personal butlers and concierge stand ready to craft an unforgettable stay.
    </p>
    <a href="<?= asset_url('booking.php') ?>" class="btn btn-gold btn-lg">
      Begin Your Reservation
    </a>
  </div>
</section>

<style>
@media (min-width: 992px) {
  .lg-grid-2 {
    grid-template-columns: 1fr 1fr !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
