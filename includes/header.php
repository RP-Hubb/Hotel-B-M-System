<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Global Header & Navigation Template
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Adishiv | Sanctuary in the Imperial Capital';
$metaDescription = $metaDescription ?? 'Adishiv Hotel & Suites is an ultra-luxury hospitality retreat in New Delhi, India. Experience serene courtyards, Mughal architectural grace, and world-class fine dining.';
$currentNav = $currentNav ?? 'home';
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDescription) ?>">
  <link rel="canonical" href="<?= e(APP_URL . $_SERVER['REQUEST_URI']) ?>">

  <!-- OpenGraph Metadata -->
  <meta property="og:site_name" content="Adishiv Hotel & Suites">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($metaDescription) ?>">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= e(APP_URL . $_SERVER['REQUEST_URI']) ?>">
  <meta property="og:image" content="<?= asset_url('assets/images/branding/hero-facade.jpg') ?>">

  <!-- Structured Data JSON-LD for Hotel -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": ["Hotel", "LodgingBusiness"],
    "name": "Adishiv Hotel & Suites",
    "description": "Ultra-luxury hotel in New Delhi featuring bespoke suites, fine dining, and Ayurvedic wellness.",
    "url": "<?= e(APP_URL) ?>",
    "telephone": "<?= e(get_setting('hotel_phone', '+91 11 4982 7700')) ?>",
    "email": "<?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "14 Imperial Boulevard, Diplomatic Enclave, Chanakyapuri",
      "addressLocality": "New Delhi",
      "postalCode": "110021",
      "addressCountry": "IN"
    },
    "priceRange": "₹₹₹₹",
    "currenciesAccepted": "INR"
  }
  </script>

  <!-- Design System & Stylesheets -->
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/motion.css') ?>">
</head>
<body>
  <!-- Accessible Skip Link -->
  <a href="#mainContent" class="skip-link">Skip to main content</a>

  <!-- Custom Cursor Elements (Desktop only) -->
  <div class="custom-cursor-dot" aria-hidden="true"></div>
  <div class="custom-cursor-ring" aria-hidden="true"></div>

  <!-- Left Coast Inspired Signature Preloader Curtain -->
  <div class="preloader-curtain" id="adishivPreloader" aria-hidden="true">
    <div class="preloader-monogram">
      <svg class="preloader-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- Mughal Arch Geometry & A Monogram -->
        <path class="preloader-path" d="M15 90V45C15 25 35 10 50 10C65 10 85 25 85 45V90M50 10V90M25 60H75M35 75L50 55L65 75" />
      </svg>
    </div>
    <div class="preloader-text-group">
      <div class="preloader-brand-title">A D I S H I V</div>
      <div class="preloader-brand-sub">New Delhi · Sanctuary in the Imperial Capital</div>
      <div class="preloader-progress-container">
        <div class="preloader-progress-bar"></div>
      </div>
      <div class="preloader-counter">00 / 100</div>
    </div>
  </div>

  <!-- Global Luxury Header -->
  <header class="site-header" role="banner">
    <div class="container">
      <!-- Left: Fullscreen Menu Trigger -->
      <button type="button" class="menu-trigger" aria-expanded="false" aria-controls="navOverlay" aria-label="Open luxury navigation menu">
        <span class="hamburger" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
        </span>
        <span>Menu</span>
      </button>

      <!-- Center: Adishiv Brand Mark -->
      <a href="<?= asset_url('index.php') ?>" class="brand-logo" aria-label="Adishiv Hotel & Suites, Homepage">
        <span class="brand-title">Adishiv</span>
        <span class="brand-subtitle">New Delhi</span>
      </a>

      <!-- Right: Navigation Links & Reserve Button -->
      <div class="header-actions">
        <nav aria-label="Primary Navigation" class="header-nav-links">
          <a href="<?= asset_url('rooms.php') ?>" class="<?= $currentNav === 'rooms' ? 'text-gold' : '' ?>">Suites</a>
          <a href="<?= asset_url('dining.php') ?>" class="<?= $currentNav === 'dining' ? 'text-gold' : '' ?>">Dining</a>
          <a href="<?= asset_url('experiences.php') ?>" class="<?= $currentNav === 'experiences' ? 'text-gold' : '' ?>">Wellness</a>
          <a href="<?= asset_url('about.php') ?>" class="<?= $currentNav === 'about' ? 'text-gold' : '' ?>">The Heritage</a>
          <a href="<?= asset_url('contact.php') ?>" class="<?= $currentNav === 'contact' ? 'text-gold' : '' ?>">Concierge</a>
          <?php if (is_logged_in()): ?>
            <?php if (is_admin()): ?>
              <a href="<?= asset_url('admin/index.php') ?>" class="text-gold" style="font-weight: 700;">Admin</a>
            <?php endif; ?>
            <a href="<?= asset_url('logout.php') ?>" style="opacity: 0.8;">Sign Out</a>
          <?php else: ?>
            <a href="<?= asset_url('login.php') ?>" style="opacity: 0.85;">Sign In</a>
          <?php endif; ?>
        </nav>
        <a href="<?= asset_url('booking.php') ?>" class="btn btn-gold btn-sm">Reserve</a>
      </div>
    </div>
  </header>

  <!-- Fullscreen Luxury Overlay Drawer Menu (Qissa Inspired) -->
  <nav class="nav-overlay" id="navOverlay" aria-label="Fullscreen Navigation" aria-hidden="true">
    <div class="nav-overlay-header">
      <a href="<?= asset_url('index.php') ?>" class="brand-logo" style="text-align: left; align-items: flex-start;">
        <span class="brand-title">Adishiv</span>
        <span class="brand-subtitle">New Delhi · Luxury Hospitality</span>
      </a>
      <button type="button" class="nav-close-btn" aria-label="Close navigation menu">
        <span>✕</span>
        <span>Close</span>
      </button>
    </div>

    <div class="nav-overlay-body">
      <ul class="nav-menu-list">
        <li class="nav-menu-item">
          <a href="<?= asset_url('index.php') ?>">
            <span class="menu-num">01</span> Sanctuary
          </a>
        </li>
        <li class="nav-menu-item">
          <a href="<?= asset_url('rooms.php') ?>">
            <span class="menu-num">02</span> Suites & Residences
          </a>
        </li>
        <li class="nav-menu-item">
          <a href="<?= asset_url('dining.php') ?>">
            <span class="menu-num">03</span> Aura Fine Dining & Bar
          </a>
        </li>
        <li class="nav-menu-item">
          <a href="<?= asset_url('experiences.php') ?>">
            <span class="menu-num">04</span> Courtyard Pool & Spa
          </a>
        </li>
        <li class="nav-menu-item">
          <a href="<?= asset_url('gallery.php') ?>">
            <span class="menu-num">05</span> Visual Gallery
          </a>
        </li>
        <li class="nav-menu-item">
          <a href="<?= asset_url('about.php') ?>">
            <span class="menu-num">06</span> Architectural Story
          </a>
        </li>
        <li class="nav-menu-item">
          <a href="<?= asset_url('contact.php') ?>">
            <span class="menu-num">07</span> Concierge & Inquiries
          </a>
        </li>
      </ul>

      <div class="nav-overlay-meta">
        <div>
          <h6>Location</h6>
          <p style="color: rgba(255,255,255,0.8); margin-bottom: 0.5rem;">
            <?= e(get_setting('hotel_address', '14 Imperial Boulevard, Diplomatic Enclave, Chanakyapuri, New Delhi 110021')) ?>
          </p>
          <span style="font-size: 0.75rem; color: var(--color-gold);">Coordinates: 28°36'N · 77°12'E</span>
        </div>

        <div>
          <h6>Reservations Desk</h6>
          <p style="color: rgba(255,255,255,0.8); margin-bottom: 0.25rem;">
            <?= e(get_setting('hotel_phone', '+91 11 4982 7700')) ?>
          </p>
          <p style="color: rgba(255,255,255,0.8);">
            <?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>
          </p>
        </div>

        <div>
          <a href="<?= asset_url('booking.php') ?>" class="btn btn-gold btn-lg" style="width: 100%; text-align: center;">
            Begin Reservation
          </a>
        </div>
      </div>
    </div>

    <div style="border-top: 1px solid var(--color-border-dark); padding-top: 1.5rem; display: flex; justify-content: space-between; font-size: 0.75rem; color: rgba(255,255,255,0.4);">
      <span>© <?= date('Y') ?> Adishiv Hotel & Suites. All rights reserved.</span>
      <span>Currency: INR (₹)</span>
    </div>
  </nav>

  <!-- Main Content Wrapper -->
  <main id="mainContent" role="main" style="flex: 1 0 auto;">
    <?php
    // Render flash messages if any
    $flashes = get_flash_messages();
    if (!empty($flashes)): ?>
      <div class="container" style="margin-top: 6.5rem;">
        <?php foreach ($flashes as $flash): ?>
          <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
            <?= e($flash['message']) ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
