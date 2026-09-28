<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Global Footer & Scripts Template
 */
?>
  </main> <!-- End Main Content -->

  <!-- Editorial Luxury Footer -->
  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-grid">
        <!-- Col 1: Brand Philosophy & Location -->
        <div class="footer-col">
          <div class="brand-logo" style="align-items: flex-start; margin-bottom: 1.25rem;">
            <span class="brand-title" style="font-size: 1.8rem;">Adishiv</span>
            <span class="brand-subtitle">New Delhi · India</span>
          </div>
          <p style="color: rgba(255, 255, 255, 0.65); font-size: var(--text-sm); line-height: 1.7; max-width: 380px;">
            A sanctuary of imperial calm in the heart of Delhi. Contemporary architecture meets the quiet majesty of Mughal stone craftsmanship, private courtyards, and bespoke butler service.
          </p>
          <div style="font-size: var(--text-xs); color: var(--color-gold); margin-top: 1rem;">
            📍 <?= e(get_setting('hotel_address', '14 Imperial Boulevard, Diplomatic Enclave, Chanakyapuri, New Delhi 110021')) ?>
          </div>
        </div>

        <!-- Col 2: Navigation -->
        <div class="footer-col">
          <h5>The Sanctuary</h5>
          <ul class="footer-links">
            <li><a href="<?= asset_url('rooms.php') ?>">Suites & Residences</a></li>
            <li><a href="<?= asset_url('dining.php') ?>">Aura Fine Dining</a></li>
            <li><a href="<?= asset_url('experiences.php') ?>">Heated Courtyard Pool</a></li>
            <li><a href="<?= asset_url('experiences.php') ?>">Ayurvedic Spa & Wellness</a></li>
            <li><a href="<?= asset_url('gallery.php') ?>">Visual Exhibition</a></li>
          </ul>
        </div>

        <!-- Col 3: Hospitality Services -->
        <div class="footer-col">
          <h5>Concierge</h5>
          <ul class="footer-links">
            <li><a href="<?= asset_url('contact.php') ?>">Inquiries & Events</a></li>
            <li><a href="<?= asset_url('booking.php') ?>">Check Availability</a></li>
            <li><a href="<?= asset_url('about.php') ?>">Architectural Ethos</a></li>
            <li><a href="<?= asset_url('login.php') ?>">Guest Account Portal</a></li>
            <li><a href="<?= asset_url('admin/login.php') ?>">Staff Portal</a></li>
          </ul>
        </div>

        <!-- Col 4: Direct Reservations Contact -->
        <div class="footer-col">
          <h5>Direct Inquiries</h5>
          <p style="color: rgba(255, 255, 255, 0.65); font-size: var(--text-sm); margin-bottom: 0.75rem;">
            For private reservations, diplomatic stays, or bespoke private dining bookings:
          </p>
          <div style="margin-bottom: 0.5rem;">
            <a href="tel:<?= e(str_replace(' ', '', get_setting('hotel_phone', '+911149827700'))) ?>" style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--color-gold); font-weight: 600;">
              <?= e(get_setting('hotel_phone', '+91 11 4982 7700')) ?>
            </a>
          </div>
          <div>
            <a href="mailto:<?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>" style="font-size: var(--text-sm); color: rgba(255,255,255,0.8);">
              <?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>
            </a>
          </div>
          <div style="margin-top: 1.5rem;">
            <a href="<?= asset_url('booking.php') ?>" class="btn btn-outline-gold btn-sm">
              Reserve Online
            </a>
          </div>
        </div>
      </div>

      <!-- Footer Bottom -->
      <div class="footer-bottom">
        <div>
          © <?= date('Y') ?> Adishiv Hotel & Suites. All rights reserved. Built for discerning luxury travelers.
        </div>
        <div style="display: flex; gap: 1.5rem;">
          <span>Currency: INR (₹)</span>
          <span>Delhi, India</span>
          <span>Time: <?= date('h:i A T') ?></span>
        </div>
      </div>
    </div>
  </footer>

  <!-- Core JavaScript Modules -->
  <script src="<?= asset_url('assets/js/preloader.js') ?>"></script>
  <script src="<?= asset_url('assets/js/navigation.js') ?>"></script>
  <script src="<?= asset_url('assets/js/animations.js') ?>"></script>
  <script src="<?= asset_url('assets/js/booking-widget.js') ?>"></script>
</body>
</html>
