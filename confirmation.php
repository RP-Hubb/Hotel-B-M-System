<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Booking Confirmation & Guest Voucher
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Rate-limit lookup attempts (WP2.7)
if (!check_rate_limit('confirm_lookup', 20, 300)) {
    http_response_code(429);
    die("Too many requests. Please wait a few minutes before querying confirmation again.");
}

$ref = trim($_GET['ref'] ?? '');
$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));

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
    http_response_code(404);
    die("Reservation not found. Please verify your reference code.");
}

// Security Check: Enforce token, matching email, or logged-in authorization (WP2.7)
$currentUser = current_user();
$isStaff = is_admin();
$isOwner = $currentUser && !empty($booking['user_id']) && (int)$currentUser['id'] === (int)$booking['user_id'];
$hasValidToken = (!empty($token) && !empty($booking['access_token']) && hash_equals($booking['access_token'], $token));
$hasMatchingEmail = (!empty($email) && hash_equals(strtolower($booking['guest_email'] ?? ''), $email));

if (!$hasValidToken && !$hasMatchingEmail && !$isStaff && !$isOwner) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <title>Sanctuary Access Verification | Adishiv</title>
      <link rel="stylesheet" href="assets/css/variables.css">
      <link rel="stylesheet" href="assets/css/base.css">
      <link rel="stylesheet" href="assets/css/components.css">
    </head>
    <body style="display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--color-obsidian); color:#fff; font-family:var(--font-sans); padding:1.5rem;">
      <div style="max-width:440px; width:100%; padding:2.5rem; background:#161B22; border:1px solid var(--color-gold); border-radius:var(--radius-sm); text-align:center;">
        <h2 style="font-family:var(--font-serif); color:var(--color-gold); margin-bottom:1rem;">Confidential Sanctuary Access</h2>
        <p style="font-size:0.9rem; color:rgba(255,255,255,0.7); margin-bottom:1.5rem;">To protect resident privacy, please verify the resident email address associated with reservation <strong><?= e($ref) ?></strong>.</p>
        <form method="GET" action="confirmation.php" style="display:flex; flex-direction:column; gap:1rem;">
          <input type="hidden" name="ref" value="<?= e($ref) ?>">
          <input type="email" name="email" required placeholder="Resident Email Address" class="form-control" style="background:#0C0F12; color:#fff; border:1px solid rgba(200,164,107,0.4); padding:0.75rem; text-align:center;">
          <button type="submit" class="btn btn-gold" style="width:100%;">Verify & View Voucher</button>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$pageTitle = 'Reservation Confirmed: ' . e($booking['booking_reference']) . ' | Adishiv';
$currentNav = 'booking';

// Compute CGST / SGST split (WP2.5)
$taxRate = (float)$booking['tax_rate'];
$halfRate = $taxRate / 2;
$taxAmount = (float)$booking['tax_amount'];
$halfTax = round($taxAmount / 2, 2);

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
          <strong style="color: var(--color-gold-ink); font-size: 1.1rem;"><?= e($booking['suite_name']) ?></strong>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);"><?= e($booking['view_type']) ?></div>
          <div style="font-size: 0.85rem; color: var(--color-text-muted);"><?= (int)$booking['guests_count'] ?> Guest(s)</div>
        </div>

        <div>
          <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.25rem;">Stay Itinerary</span>
          <div><strong>Check-in:</strong> <?= e($booking['check_in']) ?> (From 14:00)</div>
          <div><strong>Check-out:</strong> <?= e($booking['check_out']) ?> (Until 11:00)</div>
          <div style="font-size: 0.85rem; color: var(--color-gold-ink);">Total: <?= (int)$booking['total_nights'] ?> Night(s)</div>
        </div>
      </div>

      <!-- Financial Ledger Summary with CGST/SGST Split (WP2.5) -->
      <h4 style="font-size: 1.2rem; margin-bottom: 1rem;">Payment & Billing Summary</h4>
      <div style="background: var(--color-parchment); border-radius: var(--radius-xs); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem;">
          <span>Room Subtotal (<?= (int)$booking['total_nights'] ?> nights × <?= format_inr($booking['price_per_night']) ?>)</span>
          <span><?= format_inr($booking['subtotal_amount']) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--color-text-muted);">
          <span>Central GST (CGST @ <?= $halfRate ?>%)</span>
          <span><?= format_inr($halfTax) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--color-text-muted);">
          <span>State GST (SGST / UTGST @ <?= $halfRate ?>%)</span>
          <span><?= format_inr($halfTax) ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--color-border-hairline); padding-top: 0.75rem; font-size: 1.15rem; font-weight: 700;">
          <span>Total Payable (INR)</span>
          <span style="color: var(--color-text-main); font-family: var(--font-serif); font-size: 1.35rem;"><?= format_inr($booking['total_amount']) ?></span>
        </div>
      </div>

      <!-- Compliance & Tax Treatment Notice (WP2.5) -->
      <p style="font-size: 0.75rem; color: var(--color-text-muted); margin-bottom: 1.5rem; line-height: 1.5;">
        * Note: GST computation reflects applicable Indian luxury hospitality tax slabs (HSN/SAC 9963). GSTIN details and formal Tax Invoice numbering will be issued upon arrival. Formal tax treatment must be confirmed by hotel accountant.
      </p>

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
