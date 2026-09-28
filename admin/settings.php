<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Dynamic Hotel Configuration & Policy Settings
 */

$adminTitle = 'Hotel Settings';
$adminNav = 'settings';

require_once __DIR__ . '/header.php';

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        flash_message('error', 'Security session expired.');
    } else {
        $settingsToUpdate = [
            'hotel_name' => trim($_POST['hotel_name'] ?? ''),
            'hotel_tagline' => trim($_POST['hotel_tagline'] ?? ''),
            'hotel_location' => trim($_POST['hotel_location'] ?? ''),
            'hotel_address' => trim($_POST['hotel_address'] ?? ''),
            'hotel_phone' => trim($_POST['hotel_phone'] ?? ''),
            'hotel_email' => trim($_POST['hotel_email'] ?? ''),
            'tax_rate_percent' => trim($_POST['tax_rate_percent'] ?? '18.00'),
            'check_in_time' => trim($_POST['check_in_time'] ?? '14:00'),
            'check_out_time' => trim($_POST['check_out_time'] ?? '11:00'),
        ];

        foreach ($settingsToUpdate as $key => $val) {
            update_setting($key, $val);
        }

        flash_message('success', 'Hotel operational settings successfully updated in database.');
        header('Location: settings.php');
        exit;
    }
}
?>

<div class="admin-card" style="max-width: 820px; margin: 0 auto;">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Dynamic Brand & Operational Settings</h3>
      <span style="font-size: 0.8rem; color: #64748B;">Changes here dynamically update all public pages and booking calculations without touching code</span>
    </div>
  </div>

  <div style="padding: 2.25rem;">
    <form method="POST" action="settings.php">
      <?= csrf_field() ?>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
        <div class="form-group">
          <label for="hotelName" class="form-label">Hotel Brand Name</label>
          <input type="text" id="hotelName" name="hotel_name" class="form-control" value="<?= e(get_setting('hotel_name', 'Adishiv Hotel & Suites')) ?>" required>
        </div>

        <div class="form-group">
          <label for="hotelLocation" class="form-label">City / Region</label>
          <input type="text" id="hotelLocation" name="hotel_location" class="form-control" value="<?= e(get_setting('hotel_location', 'New Delhi, India')) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label for="hotelTagline" class="form-label">Editorial Tagline</label>
        <input type="text" id="hotelTagline" name="hotel_tagline" class="form-control" value="<?= e(get_setting('hotel_tagline', 'Sanctuary in the Imperial Capital')) ?>" required>
      </div>

      <div class="form-group">
        <label for="hotelAddress" class="form-label">Official Physical Address</label>
        <textarea id="hotelAddress" name="hotel_address" class="form-control" style="min-height: 80px;" required><?= e(get_setting('hotel_address', '14 Imperial Boulevard, Diplomatic Enclave, Chanakyapuri, New Delhi 110021, India')) ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
        <div class="form-group">
          <label for="hotelPhone" class="form-label">Concierge Primary Telephone</label>
          <input type="text" id="hotelPhone" name="hotel_phone" class="form-control" value="<?= e(get_setting('hotel_phone', '+91 11 4982 7700')) ?>" required>
        </div>

        <div class="form-group">
          <label for="hotelEmail" class="form-label">Concierge Primary Email</label>
          <input type="email" id="hotelEmail" name="hotel_email" class="form-control" value="<?= e(get_setting('hotel_email', 'concierge@adishivhotel.com')) ?>" required>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem;">
        <div class="form-group">
          <label for="taxRate" class="form-label">GST Tax Rate (%)</label>
          <input type="number" step="0.01" id="taxRate" name="tax_rate_percent" class="form-control" value="<?= e(get_setting('tax_rate_percent', '18.00')) ?>" required>
        </div>

        <div class="form-group">
          <label for="checkInTime" class="form-label">Standard Check-In</label>
          <input type="time" id="checkInTime" name="check_in_time" class="form-control" value="<?= e(get_setting('check_in_time', '14:00')) ?>" required>
        </div>

        <div class="form-group">
          <label for="checkOutTime" class="form-label">Standard Check-Out</label>
          <input type="time" id="checkOutTime" name="check_out_time" class="form-control" value="<?= e(get_setting('check_out_time', '11:00')) ?>" required>
        </div>
      </div>

      <div style="margin-top: 1.5rem; text-align: right;">
        <button type="submit" class="btn btn-gold btn-lg">
          Save Configuration Changes
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
