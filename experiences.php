<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Wellness, Courtyard Pool & Curated Experiences
 */

$pageTitle = 'Wellness & Curated Experiences | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Discover the heated courtyard pool, authentic Ayurvedic spa rituals, and curated private heritage tours at Hotel Adishiv New Delhi.';
$currentNav = 'experiences';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div style="background-color: var(--color-obsidian); color: #fff; padding: 8.5rem 0 4.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Holistic Wellbeing</div>
    <h1 style="color: #fff; font-size: clamp(2.5rem, 4vw, 4.5rem); margin-bottom: 1.25rem;">
      Experiences & Wellness
    </h1>
    <p style="color: rgba(255,255,255,0.7); max-width: 620px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7;">
      Harmonize body and spirit through temperature-controlled courtyard waters, classical Vedic healing, and curated excursions into imperial Delhi.
    </p>
  </div>
</div>

<!-- Experience 1: Heated Courtyard Pool -->
<section class="section-spacing container" id="pool">
  <div style="display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center;" class="lg-grid-2">
    <div>
      <img 
        src="<?= asset_url('assets/images/wellness/courtyard-pool.jpg') ?>" 
        alt="Temperature-controlled Heated Marble Courtyard Pool" 
        style="width: 100%; border-radius: var(--radius-xs); box-shadow: var(--shadow-card);"
        loading="lazy"
      >
    </div>

    <div>
      <div class="eyebrow">Water Sanctuary</div>
      <h2 style="font-size: var(--text-2xl); margin-bottom: 1.25rem;">The Heated Courtyard Pool</h2>
      <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem;">
        Framed by hand-carved sandstone colonnades and fragrant frangipani blossoms, our heated lap pool offers year-round serenity. Recline upon plush private cabanas with complimentary chilled coconut water and bespoke poolside refreshment.
      </p>
      <div style="display: flex; gap: 2rem; font-size: 0.9rem; margin-bottom: 1.5rem;">
        <div>
          <strong style="color: var(--color-text-main); display: block;">Temperature</strong>
          <span class="text-muted">Maintained at 29°C</span>
        </div>
        <div>
          <strong style="color: var(--color-text-main); display: block;">Hours</strong>
          <span class="text-muted">06:00 AM – 10:00 PM</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Experience 2: Ayurvedic Spa -->
<section class="section-spacing" style="background-color: var(--color-parchment); border-top: 1px solid var(--color-border-subtle); border-bottom: 1px solid var(--color-border-subtle);" id="spa">
  <div class="container">
    <div style="display: grid; grid-template-columns: 1fr; gap: 4rem; align-items: center;" class="lg-grid-2">
      <div>
        <div class="eyebrow">Vedic Botanical Science</div>
        <h2 style="font-size: var(--text-2xl); margin-bottom: 1.25rem;">The Ayurvedic & Modern Spa</h2>
        <p style="font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem;">
          Our sanctuary spa bridges ancient Ayurvedic healing with state-of-the-art European hydrotherapy. Personalized multi-day wellness programs are designed by our resident Vaidya (Ayurvedic physician) to detoxify and restore vitality.
        </p>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; font-size: 0.88rem; margin-bottom: 2rem;">
          <div style="background: #fff; padding: 1rem; border-radius: var(--radius-xs); border: 1px solid var(--color-border-hairline);">
            <strong>Ksheerdhara Therapy</strong>
            <p class="text-muted" style="margin-bottom: 0;">Medicated milk stream over the forehead for insomnia and deep stress relief.</p>
          </div>
          <div style="background: #fff; padding: 1rem; border-radius: var(--radius-xs); border: 1px solid var(--color-border-hairline);">
            <strong>Udvartana Scrub</strong>
            <p class="text-muted" style="margin-bottom: 0;">Herbal powder exfoliation stimulating lymphatic drainage and circulation.</p>
          </div>
        </div>

        <div>
          <a href="<?= asset_url('contact.php?subject=' . urlencode('Spa Appointment Request')) ?>" class="btn btn-gold">
            Book Spa Treatment
          </a>
        </div>
      </div>

      <div>
        <img 
          src="<?= asset_url('assets/images/rooms/deluxe-verandah.jpg') ?>" 
          alt="Verandah Relaxation Suite at Adishiv" 
          style="width: 100%; border-radius: var(--radius-xs); box-shadow: var(--shadow-card);"
          loading="lazy"
        >
      </div>
    </div>
  </div>
</section>

<!-- Experience 3: Private City Heritage Tours -->
<section class="section-spacing container" id="heritage-tours">
  <div class="text-center" style="margin-bottom: 3.5rem;">
    <div class="eyebrow center">Curated Excursions</div>
    <h2 style="font-size: var(--text-2xl);">Imperial Delhi by Private Chauffeur</h2>
    <p style="color: var(--color-text-muted); max-width: 650px; margin: 0 auto; font-size: 1.05rem;">
      Experience the capital’s architectural marvels accompanied by leading historians and private luxury limousine transit.
    </p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
    <div style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 2rem; box-shadow: var(--shadow-subtle);">
      <div style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--color-gold);">🏛</div>
      <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Mughal Splendors at Sunrise</h3>
      <p style="font-size: 0.9rem; color: var(--color-text-muted);">
        Exclusive pre-opening access to Humayun's Tomb and the tranquil garden pavilions of Nizamuddin.
      </p>
    </div>

    <div style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 2rem; box-shadow: var(--shadow-subtle);">
      <div style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--color-gold);">🌳</div>
      <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Lodhi Gardens Heritage Walk</h3>
      <p style="font-size: 0.9rem; color: var(--color-text-muted);">
        A serene morning stroll amongst 15th-century Sayyid and Lodi tombs with botanical breakfast hampers.
      </p>
    </div>

    <div style="background: #fff; border: 1px solid var(--color-border-hairline); border-radius: var(--radius-xs); padding: 2rem; box-shadow: var(--shadow-subtle);">
      <div style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--color-gold);">🏺</div>
      <h3 style="font-size: 1.3rem; margin-bottom: 0.5rem;">Artisanal Textile & Antique Curations</h3>
      <p style="font-size: 0.9rem; color: var(--color-text-muted);">
        Private appointments with master carpet weavers, pashmina artisans, and heritage jeweler ateliers.
      </p>
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
