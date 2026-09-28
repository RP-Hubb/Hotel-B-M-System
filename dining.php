<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Dining & Culinary Showcase
 */

$pageTitle = 'Aura Fine Dining & Peacock Bar | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Experience modern Indian imperial gastronomy at Aura and curated single malts at The Peacock Bar, Hotel Adishiv New Delhi.';
$currentNav = 'dining';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Dining Header -->
<div style="background-color: var(--color-obsidian); color: #fff; padding: 8.5rem 0 4.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Imperial Gastronomy</div>
    <h1 style="color: #fff; font-size: clamp(2.5rem, 4vw, 4.5rem); margin-bottom: 1.25rem;">
      Culinary Elegance
    </h1>
    <p style="color: rgba(255,255,255,0.7); max-width: 620px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7;">
      From Awadhi royal durbars to modern culinary precision, dining at Adishiv is an intimate celebration of India’s rich gastronomic legacy.
    </p>
  </div>
</div>

<!-- Aura Section -->
<section class="section-spacing container" id="aura">
  <div style="display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center;" class="lg-grid-2">
    <div>
      <div class="eyebrow">Flagship Restaurant</div>
      <h2 style="font-size: var(--text-2xl); margin-bottom: 1.25rem;">Aura Restaurant</h2>
      <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem;">
        Under sparkling Bohemian crystal chandeliers and looking out onto reflecting water pavilions, Aura presents seasonal degustation menus celebrating rare indigenous grains, hand-ground heirloom spices, and slow-braised delicacies.
      </p>
      <div style="background: var(--color-parchment); padding: 1.5rem; border-left: 2px solid var(--color-gold); margin-bottom: 2rem;">
        <h4 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Timings & Dress Code</h4>
        <div style="font-size: 0.88rem; color: var(--color-text-muted);">
          <div>Lunch: 12:30 PM – 3:30 PM</div>
          <div>Dinner: 7:00 PM – 11:30 PM</div>
          <div>Attire: Smart Elegant / Traditional Indian</div>
        </div>
      </div>
      <div>
        <a href="<?= asset_url('contact.php?subject=' . urlencode('Aura Table Reservation')) ?>" class="btn btn-gold">
          Request Table Reservation
        </a>
      </div>
    </div>

    <div>
      <img 
        src="<?= asset_url('assets/images/dining/aura-restaurant.jpg') ?>" 
        alt="Aura Fine Dining Restaurant Interior" 
        style="width: 100%; border-radius: var(--radius-xs); box-shadow: var(--shadow-card);"
        loading="lazy"
      >
    </div>
  </div>
</section>

<!-- The Peacock Library Bar Section -->
<section class="section-spacing" style="background-color: var(--color-parchment); border-top: 1px solid var(--color-border-subtle); border-bottom: 1px solid var(--color-border-subtle);" id="peacock-bar">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center;" class="lg-grid-2">
      <div>
        <img 
          src="<?= asset_url('assets/images/branding/hero-facade.jpg') ?>" 
          alt="The Peacock Library Bar at Adishiv" 
          style="width: 100%; border-radius: var(--radius-xs); box-shadow: var(--shadow-card);"
          loading="lazy"
        >
      </div>

      <div>
        <div class="eyebrow">Rare Spirits & Botanicals</div>
        <h2 style="font-size: var(--text-2xl); margin-bottom: 1.25rem;">The Peacock Library Bar</h2>
        <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem;">
          An intimate, wood-paneled retreat wrapped in leather-bound volumes and velvet armchairs. The bar features rare single malts, botanical gins infused with Himalayan juniper, and artisanal cocktails inspired by ancient Ayurvedic elixirs.
        </p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem; font-size: 0.88rem;">
          <div>
            <strong>🥃 Master Distiller Flight</strong>
            <p class="text-muted">A curated exploration of India's finest single malts.</p>
          </div>
          <div>
            <strong>🌿 Botanical Tonics</strong>
            <p class="text-muted">Small-batch botanicals with fresh Indian herbs and citrus.</p>
          </div>
        </div>
      </div>
    </div>
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
