<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Resident & Customer Directory
 */

$adminTitle = 'Residents Directory';
$adminNav = 'customers';

require_once __DIR__ . '/header.php';

$db = get_db();
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT bg.name, bg.email, bg.phone, 
           COUNT(DISTINCT b.id) AS total_stays, 
           SUM(b.total_amount) AS total_expenditure,
           MAX(b.check_out) AS last_departure
    FROM booking_guests bg
    JOIN bookings b ON bg.booking_id = b.id
    WHERE bg.is_primary = 1
";
$params = [];

if ($search !== '') {
    $sql .= " AND (bg.name LIKE :search OR bg.email LIKE :search OR bg.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

$sql .= " GROUP BY bg.email, bg.phone, bg.name ORDER BY total_expenditure DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Resident Guest Directory</h3>
      <span style="font-size: 0.8rem; color: #64748B;">Profiles and lifetime hospitality history (<?= count($customers) ?> recorded)</span>
    </div>

    <form method="GET" action="customers.php" style="display: flex; gap: 0.75rem;">
      <input 
        type="text" 
        name="search" 
        class="form-control" 
        placeholder="Search resident name, email, mobile..." 
        value="<?= e($search) ?>" 
        style="width: 260px; padding: 0.45rem 0.85rem; font-size: 0.85rem;"
      >
      <button type="submit" class="btn btn-dark btn-sm">Search</button>
      <?php if ($search): ?>
        <a href="customers.php" class="btn btn-outline-gold btn-sm">Clear</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Resident Name</th>
          <th>Contact Coordinates</th>
          <th>Total Stays</th>
          <th>Lifetime Spend (INR)</th>
          <th>Recent Stay</th>
          <th>VIP Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($customers)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 2.5rem; color: #64748B;">
              No resident guest records found.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <strong style="font-size: 1.05rem;"><?= e($c['name']) ?></strong>
              </td>
              <td>
                <div>📧 <a href="mailto:<?= e($c['email']) ?>" style="color: var(--color-obsidian);"><?= e($c['email']) ?></a></div>
                <div style="font-size: 0.8rem; color: #64748B;">📱 <?= e($c['phone']) ?></div>
              </td>
              <td>
                <strong><?= (int)$c['total_stays'] ?> Stay(s)</strong>
              </td>
              <td>
                <strong style="color: var(--color-gold); font-family: var(--font-serif); font-size: 1.15rem;">
                  <?= format_inr($c['total_expenditure'] ?? 0) ?>
                </strong>
              </td>
              <td>
                <?= $c['last_departure'] ? date('d M Y', strtotime($c['last_departure'])) : 'N/A' ?>
              </td>
              <td>
                <?php if ((float)$c['total_expenditure'] >= 100000): ?>
                  <span class="status-chip" style="background: #FEF3C7; color: #92400E; font-weight: 700;">★ Imperial Connoisseur</span>
                <?php else: ?>
                  <span class="status-chip" style="background: #F1F5F9; color: #475569;">Resident</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
