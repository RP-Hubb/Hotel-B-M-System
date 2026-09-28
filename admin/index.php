<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Admin Dashboard Overview & Key Performance Indicators
 */

$adminTitle = 'Overview Dashboard';
$adminNav = 'dashboard';

require_once __DIR__ . '/header.php';

$db = get_db();
$today = date('Y-m-d');

// 1. Calculate Real KPIs
// Total Bookings
$totalBookings = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Total Confirmed / Active Revenue
$totalRevenue = (float)$db->query("SELECT SUM(total_amount) FROM bookings WHERE status IN ('confirmed', 'checked_in', 'checked_out')")->fetchColumn();

// Today's Check-ins
$stmtCheckIns = $db->prepare("SELECT COUNT(*) FROM bookings WHERE check_in = :today AND status = 'confirmed'");
$stmtCheckIns->execute([':today' => $today]);
$todayCheckIns = (int)$stmtCheckIns->fetchColumn();

// Today's Check-outs
$stmtCheckOuts = $db->prepare("SELECT COUNT(*) FROM bookings WHERE check_out = :today AND status = 'checked_in'");
$stmtCheckOuts->execute([':today' => $today]);
$todayCheckOuts = (int)$stmtCheckOuts->fetchColumn();

// Physical Rooms Total & Occupied for Occupancy Rate
$totalPhysicalRooms = (int)$db->query("SELECT COUNT(*) FROM rooms WHERE status != 'maintenance'")->fetchColumn();
$occupiedRooms = (int)$db->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
$occupancyRate = $totalPhysicalRooms > 0 ? round(($occupiedRooms / $totalPhysicalRooms) * 100, 1) : 0;

// Pending Inquiries
$unreadMessages = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

// 2. Fetch Recent Reservations
$stmtRecent = $db->query("
    SELECT b.*, rt.name AS suite_name, r.room_number, bg.name AS guest_name, bg.phone AS guest_phone
    FROM bookings b
    JOIN room_types rt ON b.room_type_id = rt.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN booking_guests bg ON bg.booking_id = b.id AND bg.is_primary = 1
    ORDER BY b.created_at DESC
    LIMIT 8
");
$recentBookings = $stmtRecent->fetchAll();
?>

<!-- Metric Cards Grid -->
<div class="kpi-grid">
  <div class="kpi-card">
    <span class="kpi-label">Total Revenue</span>
    <span class="kpi-value" style="color: var(--color-gold);"><?= format_inr($totalRevenue) ?></span>
    <span class="kpi-sub">Across all confirmed stays</span>
  </div>

  <div class="kpi-card">
    <span class="kpi-label">Current Occupancy</span>
    <span class="kpi-value"><?= $occupancyRate ?>%</span>
    <span class="kpi-sub"><?= $occupiedRooms ?> of <?= $totalPhysicalRooms ?> rooms occupied</span>
  </div>

  <div class="kpi-card">
    <span class="kpi-label">Today's Check-ins</span>
    <span class="kpi-value"><?= $todayCheckIns ?></span>
    <span class="kpi-sub">Arrivals scheduled for <?= date('d M Y') ?></span>
  </div>

  <div class="kpi-card">
    <span class="kpi-label">Today's Check-outs</span>
    <span class="kpi-value"><?= $todayCheckOuts ?></span>
    <span class="kpi-sub">Departures scheduled today</span>
  </div>

  <div class="kpi-card">
    <span class="kpi-label">Concierge Inquiries</span>
    <span class="kpi-value" style="<?= $unreadMessages > 0 ? 'color: var(--color-terracotta);' : '' ?>"><?= $unreadMessages ?></span>
    <span class="kpi-sub"><a href="messages.php" style="color: var(--color-gold); text-decoration: underline;">View Inquiries →</a></span>
  </div>
</div>

<!-- Recent Reservations Management Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Recent Suite Reservations</h3>
    <div style="display: flex; gap: 0.75rem;">
      <a href="bookings.php" class="btn btn-dark btn-sm">View All Bookings</a>
      <a href="<?= asset_url('booking.php') ?>" target="_blank" class="btn btn-gold btn-sm">+ Walk-in Reservation</a>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Resident</th>
          <th>Suite Category</th>
          <th>Room</th>
          <th>Dates</th>
          <th>Total (INR)</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentBookings)): ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: 2rem; color: #64748B;">
              No reservations recorded yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentBookings as $b): ?>
            <tr>
              <td>
                <strong><?= e($b['booking_reference']) ?></strong>
                <div style="font-size: 0.72rem; color: #64748B;"><?= date('d M, h:i A', strtotime($b['created_at'])) ?></div>
              </td>
              <td>
                <strong><?= e($b['guest_name'] ?? 'Guest') ?></strong>
                <div style="font-size: 0.75rem; color: #64748B;"><?= e($b['guest_phone'] ?? '') ?></div>
              </td>
              <td><?= e($b['suite_name']) ?></td>
              <td>
                <span style="font-weight: 600; background: #F1F5F9; padding: 0.2rem 0.5rem; border-radius: var(--radius-xs);">
                  <?= e($b['room_number'] ?? 'Unassigned') ?>
                </span>
              </td>
              <td>
                <div><?= date('d M', strtotime($b['check_in'])) ?> → <?= date('d M', strtotime($b['check_out'])) ?></div>
                <div style="font-size: 0.72rem; color: #64748B;"><?= (int)$b['total_nights'] ?> Night(s)</div>
              </td>
              <td><strong><?= format_inr($b['total_amount']) ?></strong></td>
              <td>
                <span class="status-chip <?= e($b['status']) ?>">
                  <?= ucfirst(str_replace('_', ' ', e($b['status']))) ?>
                </span>
              </td>
              <td>
                <a href="bookings.php?search=<?= urlencode($b['booking_reference']) ?>" class="btn btn-dark btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.7rem;">
                  Manage
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
