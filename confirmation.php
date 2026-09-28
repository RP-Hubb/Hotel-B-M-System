<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Booking Confirmation & Guest Voucher
 */

require_once __DIR__ . '/includes/functions.php';

$ref = trim($_GET['ref'] ?? '');
if (empty($ref)) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare("
    SELECT b.*, rt.name AS suite_name, rt.featured_image, rt.view_type, 
           bg.name AS guest_name, bg.email AS guest_email, bg.phone AS guest_phone,
           p.payment_method, p.payment_status
    FROM bookings b
    JOIN room_types rt ON b.room_type_id = rt.id
    LEFT JOIN booking_guests bg ON bg.booking_id = b.id AND bg.is_primary = 1
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.booking_reference = :ref
    LIMIT 1
");
$stmt->execute([':ref' => $ref]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Reservation not found. Please verify your reference code.");
}

$pageTitle = 'Reservation Confirmed: ' . e($booking['booking_reference']) . ' | Adishiv';
$currentNav = 'booking';

require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-obsidian); color: #fff; padding: 7.5rem 0 3.5rem; text-align: center;">
  <div class="container-narrow">
    <div style="display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 50%; background: rgba(200,164,107,0.2); border: 2px solid var(--color-gold); color: var(--color-gold); font-size: 1.6rem; margin-bottom: 1.25rem;">
      ✓
    </div>
    <div class="eyebrow center" style="color: var(--color-gold-light);">Imperial Sanctuary Confirmed</div>
    <h1 style="color: #fff; font-size: clamp(2rem, 3.5vw, 3.2rem); margin-bottom: 0.5rem;">
      Your Reservation is Confirmed
    </h1>
    <p style="color: rgba(255,255,255,0.7); font-size: 1.05rem;">
      Thank you, <strong><?= e($booking['guest_name']) ?></strong>. We look forward to welcoming you to Adishiv New Delhi.
    </p>
  </div>
</div>

<!-- Voucher Pass Container -->
<div class="container section-spacing" style="max-width: 860px; padding-top: 2rem;">
  <div style="background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); box-shadow: var(--shadow-card); overflow: hidden;" id="printableVoucher">
    <!-- Voucher Top Ribbon -->
    <div style="background: var(--color-obsidian); color: #fff; padding: 1.5rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
      <div>
        <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; color: var(--color-gold); display: block;">Official Booking Voucher</span>
        <span style="font-family: var(--font-serif); font-size: 1.5rem; font-weight: 600; color: #fff;"><?= e($booking['booking_reference']) ?></span>
      </div>
      <div style="text-align: right;">
        <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; color: rgba(255,255,255,0.6); display: block;">Status</span>
        <span class="status-chip confirmed" style="font-size: 0.8rem;"><?= ucfirst(e($booking['status'])) ?></span>
      </div>
    </div>

    <!-- Voucher Details Body -->
    <div style="padding: 2.5rem;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.75rem; margin-bottom: 2rem; border-bottom: 1px solid var(--color-border-hairline); padding-bottom: 2rem;">
        <div>
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Primary Resident</span>
          <strong><?= e($booking['guest_name']) ?></strong>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);"><?= e($booking['guest_email']) ?></div>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);"><?= e($booking['guest_phone']) ?></div>
        </div>

        <div>
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Suite Category</span>
          <strong style="color: var(--color-gold); font-size: 1.1rem;"><?= e($booking['suite_name']) ?></strong>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);"><?= e($booking['view_type']) ?></div>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);"><?= (int)$booking['guests_count'] ?> Guest(s)</div>
        </div>

        <div>
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Stay Itinerary</span>
          <div><strong>Check-in:</strong> <?= e($booking['check_in']) ?> (From 14:00)</div>
          <div><strong>Check-out:</strong> <?= e($booking['check_out']) ?> (Until 11:00)</div>
          <div style="font-size: 0.85rem; color: var(--color-gold);">Total: <?= (int)$booking['total_nights'] ?> Night(s)</div>
        </div>
      </div>

      <!-- Financial Ledger Summary -->
      <h4 style="font-size: 1.2rem; margin-bottom: 1rem;">Payment & Billing Summary</h4>
      <div style="background: var(--color-parchment); border-radius: var(--radius-xs); padding: 1.25rem 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem;">
          <span>Room Subtotal (<?= (int)$booking['total_nights'] ?> nights × <?= format_inr($booking['price_per_night']) ?>)</span>
          <span><?= format_inr($booking['subtotal_amount']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem;">
          <span>Goods & Services Tax (18% GST)</span>
          <span><?= format_inr($booking['tax_amount']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--color-border-hairline); padding-top: 0.75rem; font-size: 1.15rem; font-weight: 700;">
          <span>Total Payable (INR)</span>
          <span style="color: var(--color-text-main); font-family: var(--font-serif); font-size: 1.35rem;"><?= format_inr($booking['total_amount']) ?></span>
        </div>
      </div>

      <!-- Special Requests -->
      <?php if (!empty($booking['special_requests'])): ?>
        <div style="margin-bottom: 2rem; font-size: 0.88rem; background: #fff; border: 1px solid #E2E8F0; padding: 1rem; border-radius: var(--radius-xs);">
          <strong style="display: block; margin-bottom: 0.25rem;">Special Preferences Logged:</strong>
          <span class="text-muted"><?= nl2br(e($booking['special_requests'])) ?></span>
        </div>
      <?php endif; ?>

      <!-- Actions: Print / Return Home -->
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;" class="no-print">
        <a href="<?= asset_url('index.php') ?>" class="btn btn-dark btn-sm">
          ← Return to Homepage
        </a>
        <div style="display: flex; gap: 0.75rem;">
          <button type="button" onclick="window.print()" class="btn btn-outline-gold btn-sm">
            🖨 Print / Save Voucher (PDF)
          </button>
          <a href="tel:+911149827700" class="btn btn-gold btn-sm">
            Concierge Assistance
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  .site-header, .site-footer, .no-print, .skip-link, .custom-cursor-dot, .custom-cursor-ring {
    display: none !important;
  }
  body {
    background: #fff !important;
    color: #000 !important;
  }
  #printableVoucher {
    box-shadow: none !important;
    border: 1px solid #000 !important;
  }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
