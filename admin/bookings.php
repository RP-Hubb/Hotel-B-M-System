<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Admin Booking Management
 */

$adminTitle = 'Booking Management';
$adminNav = 'bookings';

require_once __DIR__ . '/header.php';

$db = get_db();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        flash_message('error', 'Security token expired.');
    } else {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $action = $_POST['action'];

        $stmtB = $db->prepare("SELECT * FROM bookings WHERE id = :id LIMIT 1");
        $stmtB->execute([':id' => $bookingId]);
        $booking = $stmtB->fetch();

        if ($booking) {
            if ($action === 'check_in') {
                $db->prepare("UPDATE bookings SET status = 'checked_in', updated_at = NOW() WHERE id = :id")->execute([':id' => $bookingId]);
                if ($booking['room_id']) {
                    $db->prepare("UPDATE rooms SET status = 'occupied' WHERE id = :rid")->execute([':rid' => $booking['room_id']]);
                }
                flash_message('success', "Resident marked as Checked In.");
            } elseif ($action === 'check_out') {
                $db->prepare("UPDATE bookings SET status = 'checked_out', updated_at = NOW() WHERE id = :id")->execute([':id' => $bookingId]);
                if ($booking['room_id']) {
                    $db->prepare("UPDATE rooms SET status = 'available' WHERE id = :rid")->execute([':rid' => $booking['room_id']]);
                }
                flash_message('success', "Resident marked as Checked Out. Room released.");
            } elseif ($action === 'cancel') {
                $db->prepare("UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = :id")->execute([':id' => $bookingId]);
                if ($booking['room_id']) {
                    $db->prepare("UPDATE rooms SET status = 'available' WHERE id = :rid")->execute([':rid' => $booking['room_id']]);
                }
                flash_message('warning', "Reservation has been cancelled.");
            }
        }
    }
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT b.*, rt.name AS suite_name, r.room_number, 
           bg.name AS guest_name, bg.email AS guest_email, bg.phone AS guest_phone,
           p.payment_status, p.payment_method
    FROM bookings b
    JOIN room_types rt ON b.room_type_id = rt.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN booking_guests bg ON bg.booking_id = b.id AND bg.is_primary = 1
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (b.booking_reference LIKE :search OR bg.name LIKE :search OR bg.phone LIKE :search OR bg.email LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $sql .= " AND b.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY b.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">All Reservations</h3>
      <span style="font-size: 0.8rem; color: #64748B;">Total records found: <?= count($bookings) ?></span>
    </div>

    <!-- Search & Filter Form -->
    <form method="GET" action="bookings.php" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <input 
        type="text" 
        name="search" 
        class="form-control" 
        placeholder="Search reference, guest, phone..." 
        value="<?= e($search) ?>" 
        style="width: 240px; padding: 0.45rem 0.85rem; font-size: 0.85rem;"
      >
      <select name="status" class="form-control" style="width: auto; padding: 0.45rem 0.85rem; font-size: 0.85rem;" onchange="this.form.submit()">
        <option value="all" <?= $statusFilter === 'all' || $statusFilter === '' ? 'selected' : '' ?>>All Statuses</option>
        <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="checked_in" <?= $statusFilter === 'checked_in' ? 'selected' : '' ?>>Checked In</option>
        <option value="checked_out" <?= $statusFilter === 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
      <button type="submit" class="btn btn-dark btn-sm">Filter</button>
      <?php if ($search || ($statusFilter && $statusFilter !== 'all')): ?>
        <a href="bookings.php" class="btn btn-outline-gold btn-sm">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Voucher Ref</th>
          <th>Resident Details</th>
          <th>Suite & Room</th>
          <th>Check-in / Out</th>
          <th>Total & Tax (INR)</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bookings)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 2.5rem; color: #64748B;">
              No reservations matched your criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td>
                <strong style="color: var(--color-gold);"><?= e($b['booking_reference']) ?></strong>
                <div style="font-size: 0.72rem; color: #64748B;"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></div>
              </td>
              <td>
                <strong><?= e($b['guest_name'] ?? 'Guest') ?></strong>
                <div style="font-size: 0.78rem; color: #64748B;"><?= e($b['guest_email'] ?? '') ?></div>
                <div style="font-size: 0.78rem; color: #64748B;"><?= e($b['guest_phone'] ?? '') ?></div>
              </td>
              <td>
                <strong><?= e($b['suite_name']) ?></strong>
                <div style="font-size: 0.78rem;">Room: <span style="background: #E2E8F0; padding: 0.1rem 0.4rem; border-radius: 2px; font-weight: 600;"><?= e($b['room_number'] ?? 'Assigned') ?></span></div>
                <div style="font-size: 0.72rem; color: #64748B;"><?= (int)$b['guests_count'] ?> Guest(s)</div>
              </td>
              <td>
                <div>In: <strong><?= e($b['check_in']) ?></strong></div>
                <div>Out: <strong><?= e($b['check_out']) ?></strong></div>
                <div style="font-size: 0.72rem; color: var(--color-gold);"><?= (int)$b['total_nights'] ?> Night(s)</div>
              </td>
              <td>
                <strong><?= format_inr($b['total_amount']) ?></strong>
                <div style="font-size: 0.72rem; color: #64748B;">(incl. 18% GST: <?= format_inr($b['tax_amount']) ?>)</div>
              </td>
              <td>
                <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
                  <?= e(str_replace('_', ' ', $b['payment_method'] ?? 'Hotel Counter')) ?>
                </span>
              </td>
              <td>
                <span class="status-chip <?= e($b['status']) ?>">
                  <?= ucfirst(str_replace('_', ' ', e($b['status']))) ?>
                </span>
              </td>
              <td>
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                  <?php if ($b['status'] === 'confirmed'): ?>
                    <form method="POST" onsubmit="return confirm('Confirm resident check-in?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                      <input type="hidden" name="action" value="check_in">
                      <button type="submit" class="btn btn-gold btn-sm" style="width: 100%; font-size: 0.68rem; padding: 0.2rem 0.5rem;">
                        ✓ Check In
                      </button>
                    </form>
                  <?php elseif ($b['status'] === 'checked_in'): ?>
                    <form method="POST" onsubmit="return confirm('Confirm resident check-out and release room?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                      <input type="hidden" name="action" value="check_out">
                      <button type="submit" class="btn btn-dark btn-sm" style="width: 100%; font-size: 0.68rem; padding: 0.2rem 0.5rem;">
                        Depart (Check Out)
                      </button>
                    </form>
                  <?php endif; ?>

                  <?php if (in_array($b['status'], ['confirmed', 'checked_in'])): ?>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                      <input type="hidden" name="action" value="cancel">
                      <button type="submit" style="background: transparent; border: none; color: #EF4444; font-size: 0.7rem; cursor: pointer; text-decoration: underline;">
                        Cancel Booking
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
