<?php
/**
 * Adishiv Luxury Hotel & Suites
 * The Heritage & Architectural Narrative
 */

$pageTitle = 'The Heritage & Architecture | Adishiv Luxury Hotel New Delhi';
$metaDescription = 'Discover the architectural ethos, stone craftsmanship, and imperial heritage behind Hotel Adishiv New Delhi.';
$currentNav = 'about';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header -->
<div style="background-color: var(--color-obsidian); color: #fff; padding: 8.5rem 0 4.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Our Ethos</div>
    <h1 style="color: #fff; font-size: clamp(2.5rem, 4vw, 4.5rem); margin-bottom: 1.25rem;">
      The Story of Adishiv
    </h1>
    <p style="color: rgba(255,255,255,0.7); max-width: 620px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7;">
      A labor of architectural devotion—celebrating the profound stillness, stone artistry, and imperial hospitality of India’s historic capital.
    </p>
  </div>
</div>

<!-- Narrative Section 1: The Vision -->
<section class="section-spacing container-narrow">
  <div style="text-align: center; margin-bottom: 3.5rem;">
    <div class="eyebrow center">Conception</div>
    <h2 style="font-size: var(--text-2xl); margin-bottom: 1.5rem;">Born of Stone, Light & Water</h2>
    <p class="lead">
      Adishiv was founded upon a singular philosophical premise: that true luxury in a modern metropolis is not defined by excess, but by the rarity of quiet, unhurried space.
    </p>
  </div>

  <div style="font-size: 1.05rem; line-height: 1.85; color: var(--color-text-main); display: flex; flex-direction: column; gap: 1.5rem;">
    <p>
      Located in New Delhi’s iconic Connaught Place, the estate was designed by master architects who spent years studying the classical proportional harmonies of Fatehpur Sikri and Lutyens' imperial boulevards. Rather than mimicking historical forms, they distilled them into clean, monumental lines of stone and glass.
    </p>
    <p>
      The hotel's heart is its stepped central water courtyard. Here, cool breezes glide across lotus-laden pools before circulating through high-ceilinged verandahs, natural stone lattices (<em>jaalis</em>), and teak-paneled salons.
    </p>
  </div>
</section>

<!-- Full-bleed Image -->
<div class="container" style="margin-bottom: 5rem;">
  <img 
    src="<?= asset_url('assets/images/branding/hero-facade.jpg') ?>" 
    alt="Adishiv Architectural Colonnade and Courtyard" 
    style="width: 100%; border-radius: var(--radius-xs); box-shadow: var(--shadow-card); max-height: 520px; object-fit: cover;"
    loading="lazy"
  >
</div>

<!-- Pillar 3: Three Pillars of Hospitality -->
<section class="section-spacing" style="background-color: var(--color-parchment); border-top: 1px solid var(--color-border-subtle); border-bottom: 1px solid var(--color-border-subtle);">
  <div class="container">
    <div class="text-center" style="margin-bottom: 3.5rem;">
      <div class="eyebrow center">Our Pillars</div>
      <h2 style="font-size: var(--text-2xl);">Principles of the Adishiv Experience</h2>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
      <div style="background: #fff; padding: 2.25rem; border-radius: var(--radius-xs); border: 1px solid var(--color-border-hairline);">
        <span style="font-family: var(--font-serif); font-size: 2rem; color: var(--color-gold); display: block; margin-bottom: 0.5rem;">01</span>
        <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem;">Atithi Devo Bhava</h3>
        <p style="font-size: 0.92rem; color: var(--color-text-muted); line-height: 1.65;">
          The sacred Indian tradition treating the resident guest as an honored deity. Intuitive, anticipatory service delivered without intrusion.
        </p>
      </div>

      <div style="background: #fff; padding: 2.25rem; border-radius: var(--radius-xs); border: 1px solid var(--color-border-hairline);">
        <span style="font-family: var(--font-serif); font-size: 2rem; color: var(--color-gold); display: block; margin-bottom: 0.5rem;">02</span>
        <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem;">Artisanal Heritage</h3>
        <p style="font-size: 0.92rem; color: var(--color-text-muted); line-height: 1.65;">
          Every brass fixture, hand-knotted silk carpet, and carved stone lattice was commissioned directly from fourth-generation craft families across India.
        </p>
      </div>

      <div style="background: #fff; padding: 2.25rem; border-radius: var(--radius-xs); border: 1px solid var(--color-border-hairline);">
        <span style="font-family: var(--font-serif); font-size: 2rem; color: var(--color-gold); display: block; margin-bottom: 0.5rem;">03</span>
        <h3 style="font-size: 1.3rem; margin-bottom: 0.75rem;">Conscious Sanctuary</h3>
        <p style="font-size: 0.92rem; color: var(--color-text-muted); line-height: 1.65;">
          100% solar rainwater harvesting, zero single-use plastics, and complete organic waste composting, preserving Delhi’s environment for generations.
        </p>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
