<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Visual Gallery & Editorial Exhibition
 */

$pageTitle = 'Visual Gallery | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Explore the visual elegance of Hotel Adishiv New Delhi. Architectural craftsmanship, suite interiors, gastronomy, and courtyard pools.';
$currentNav = 'gallery';

require_once __DIR__ . '/includes/header.php';

$galleryItems = [
    [
        'title' => 'Imperial Facade & Courtyard Pool at Dusk',
        'category' => 'architecture',
        'image' => 'assets/images/branding/hero-facade.jpg',
        'caption' => 'The illuminated sandstone archway reflecting across quiet courtyard waters.'
    ],
    [
        'title' => 'The Adishiv Presidential Residence',
        'category' => 'suites',
        'image' => 'assets/images/rooms/presidential-residence.jpg',
        'caption' => 'Teakwood headboard with brass inlay, Italian marble ensuite, and panoramic diplomatic greens.'
    ],
    [
        'title' => 'Aura Fine Dining Restaurant',
        'category' => 'dining',
        'image' => 'assets/images/dining/aura-restaurant.jpg',
        'caption' => 'Intimate evening dining beneath bespoke crystal chandeliers and garden vistas.'
    ],
    [
        'title' => 'Heated Marble Courtyard Pool',
        'category' => 'wellness',
        'image' => 'assets/images/wellness/courtyard-pool.jpg',
        'caption' => 'Temperature-controlled lap pool surrounded by Mughal arch colonnades.'
    ],
    [
        'title' => 'Deluxe Verandah Chamber',
        'category' => 'suites',
        'image' => 'assets/images/rooms/deluxe-verandah.jpg',
        'caption' => 'Sun-drenched private balcony shaded by vibrant bougainvillea.'
    ],
    [
        'title' => 'Imperial Heritage Suite Salon',
        'category' => 'suites',
        'image' => 'assets/images/rooms/imperial-suite.jpg',
        'caption' => 'Handcrafted textiles and royal proportions designed for extended stays.'
    ],
];
?>

<!-- Header -->
<div style="background-color: var(--color-obsidian); color: #fff; padding: 8.5rem 0 4.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Visual Chronicle</div>
    <h1 style="color: #fff; font-size: clamp(2.5rem, 4vw, 4.5rem); margin-bottom: 1.25rem;">
      The Visual Exhibition
    </h1>
    <p style="color: rgba(255,255,255,0.7); max-width: 600px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7;">
      An architectural and photographic journey through our chambers, culinary spaces, and peaceful water courtyards.
    </p>
  </div>
</div>

<!-- Category Filters -->
<div style="background-color: #fff; border-bottom: 1px solid var(--color-border-hairline); padding: 1.25rem 0; text-align: center;">
  <div class="container" style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
    <button type="button" class="gallery-filter-btn btn btn-sm btn-gold" data-filter="all">All Works</button>
    <button type="button" class="gallery-filter-btn btn btn-sm btn-outline-gold" data-filter="architecture">Architecture</button>
    <button type="button" class="gallery-filter-btn btn btn-sm btn-outline-gold" data-filter="suites">Suites</button>
    <button type="button" class="gallery-filter-btn btn btn-sm btn-outline-gold" data-filter="dining">Gastronomy</button>
    <button type="button" class="gallery-filter-btn btn btn-sm btn-outline-gold" data-filter="wellness">Wellness</button>
  </div>
</div>

<!-- Gallery Grid -->
<div class="section-spacing container">
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem;" id="galleryGrid">
    <?php foreach ($galleryItems as $item): ?>
      <div class="gallery-card" data-category="<?= e($item['category']) ?>" style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); overflow: hidden; box-shadow: var(--shadow-subtle); cursor: pointer;" onclick="openLightbox('<?= asset_url($item['image']) ?>', '<?= e($item['title']) ?>', '<?= e($item['caption']) ?>')">
        <div style="height: 280px; overflow: hidden; position: relative;">
          <img 
            src="<?= asset_url($item['image']) ?>" 
            alt="<?= e($item['title']) ?>" 
            style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease;"
            loading="lazy"
          >
          <div style="position: absolute; inset: 0; background: rgba(12,15,18,0.3); opacity: 0; transition: opacity 0.3s; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.5rem;" class="gallery-hover-overlay">
            🔍
          </div>
        </div>
        <div style="padding: 1.25rem 1.5rem;">
          <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--color-gold); font-weight: 600; display: block; margin-bottom: 0.35rem;">
            <?= ucfirst(e($item['category'])) ?>
          </span>
          <h3 style="font-size: 1.25rem; margin-bottom: 0.4rem;"><?= e($item['title']) ?></h3>
          <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 0;"><?= e($item['caption']) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Lightbox Modal -->
<div id="galleryLightbox" role="dialog" aria-modal="true" aria-label="Photo Lightbox" style="position: fixed; inset: 0; background: rgba(12,15,18,0.96); z-index: var(--z-modal); display: none; align-items: center; justify-content: center; padding: 2rem;" onclick="closeLightbox(event)">
  <div style="max-width: 1000px; width: 100%; text-align: center; position: relative;" onclick="event.stopPropagation()">
    <button type="button" id="lightboxCloseBtn" onclick="closeLightbox()" aria-label="Close Lightbox" style="position: absolute; top: -3.2rem; right: 0; background: transparent; border: 1px solid rgba(255,255,255,0.25); color: #fff; width: 40px; height: 40px; border-radius: 50%; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;">✕</button>
    <img id="lightboxImg" src="" alt="" style="max-height: 72vh; max-width: 100%; margin: 0 auto 1.5rem; border-radius: var(--radius-xs); box-shadow: var(--shadow-modal);">
    <h3 id="lightboxTitle" style="color: #fff; font-size: 1.6rem; margin-bottom: 0.5rem; font-family: var(--font-serif);"></h3>
    <p id="lightboxCaption" style="color: rgba(255,255,255,0.7); font-size: 1rem; max-width: 650px; margin: 0 auto;"></p>
  </div>
</div>

<script>
let activeTriggerEl = null;

document.querySelectorAll('.gallery-filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.gallery-filter-btn').forEach(b => {
      b.classList.remove('btn-gold');
      b.classList.add('btn-outline-gold');
    });
    btn.classList.add('btn-gold');
    btn.classList.remove('btn-outline-gold');

    const filter = btn.dataset.filter;
    document.querySelectorAll('.gallery-card').forEach(card => {
      if (filter === 'all' || card.dataset.category === filter) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  });
});

// Make cards accessible via keyboard
document.querySelectorAll('.gallery-card').forEach(card => {
  card.setAttribute('tabindex', '0');
  card.setAttribute('role', 'button');
  card.setAttribute('aria-label', 'View photo in full screen');

  card.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      card.click();
    }
  });

  card.addEventListener('mouseenter', () => {
    const ov = card.querySelector('.gallery-hover-overlay');
    if (ov) ov.style.opacity = '1';
    const img = card.querySelector('img');
    if (img) img.style.transform = 'scale(1.05)';
  });
  card.addEventListener('mouseleave', () => {
    const ov = card.querySelector('.gallery-hover-overlay');
    if (ov) ov.style.opacity = '0';
    const img = card.querySelector('img');
    if (img) img.style.transform = 'scale(1)';
  });
});

function openLightbox(imgSrc, title, caption) {
  activeTriggerEl = document.activeElement;
  const lb = document.getElementById('galleryLightbox');
  document.getElementById('lightboxImg').src = imgSrc;
  document.getElementById('lightboxImg').alt = title;
  document.getElementById('lightboxTitle').textContent = title;
  document.getElementById('lightboxCaption').textContent = caption;
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';

  const closeBtn = document.getElementById('lightboxCloseBtn');
  if (closeBtn) closeBtn.focus();
}

function closeLightbox() {
  const lb = document.getElementById('galleryLightbox');
  lb.style.display = 'none';
  document.body.style.overflow = '';
  if (activeTriggerEl && typeof activeTriggerEl.focus === 'function') {
    activeTriggerEl.focus();
  }
}

// Global Keyboard Listener for Lightbox
document.addEventListener('keydown', (e) => {
  const lb = document.getElementById('galleryLightbox');
  if (lb && lb.style.display === 'flex') {
    if (e.key === 'Escape') {
      closeLightbox();
    }
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
