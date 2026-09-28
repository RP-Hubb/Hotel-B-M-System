<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Room Inventory & Nightly Rate Management
 */

$adminTitle = 'Rooms & Rates Management';
$adminNav = 'rooms';

require_once __DIR__ . '/header.php';

$db = get_db();

// Handle room status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        flash_message('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        if ($action === 'update_room_status') {
            $roomId = (int)($_POST['room_id'] ?? 0);
            $newStatus = $_POST['status'] ?? 'available';
            if (in_array($newStatus, ['available', 'occupied', 'maintenance', 'housekeeping'])) {
                $stmt = $db->prepare("UPDATE rooms SET status = :status, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $roomId]);
                flash_message('success', "Physical room #{$roomId} status updated to {$newStatus}.");
            }
        } elseif ($action === 'add_physical_room') {
            $roomNumber = trim($_POST['room_number'] ?? '');
            $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
            $floor = trim($_POST['floor'] ?? 'Ground');

            if (!empty($roomNumber) && $roomTypeId > 0) {
                try {
                    $stmt = $db->prepare("INSERT INTO rooms (room_number, room_type_id, floor, status) VALUES (:num, :rtid, :floor, 'available')");
                    $stmt->execute([':num' => $roomNumber, ':rtid' => $roomTypeId, ':floor' => $floor]);
                    flash_message('success', "Room #{$roomNumber} successfully registered into inventory.");
                } catch (Exception $e) {
                    flash_message('error', "Room number #{$roomNumber} already exists in inventory.");
                }
            }
        } elseif ($action === 'update_suite_price') {
            $typeId = (int)($_POST['room_type_id'] ?? 0);
            $newPrice = (float)($_POST['price_per_night'] ?? 0);

            if ($typeId > 0 && $newPrice > 0) {
                $stmt = $db->prepare("UPDATE room_types SET price_per_night = :price, updated_at = NOW() WHERE id = :id");
                $stmt->execute([':price' => $newPrice, ':id' => $typeId]);
                flash_message('success', "Suite category nightly rate updated to " . format_inr($newPrice) . ".");
            }
        }
    }
}

// Fetch physical rooms with their type info
$stmtRooms = $db->query("
    SELECT r.*, rt.name AS suite_name, rt.price_per_night 
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    ORDER BY r.room_number ASC
");
$rooms = $stmtRooms->fetchAll();

// Fetch suite categories
$roomTypes = $db->query("SELECT * FROM room_types ORDER BY sort_order ASC")->fetchAll();
?>

<!-- 1. Physical Room Inventory Grid -->
<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Physical Room Inventory (<?= count($rooms) ?> Units)</h3>
      <span style="font-size: 0.8rem; color: #64748B;">Monitor operational readiness, housekeeping, and maintenance</span>
    </div>
    <div>
      <button type="button" onclick="document.getElementById('addRoomModal').style.display='flex'" class="btn btn-gold btn-sm">
        + Add Physical Room
      </button>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Room #</th>
          <th>Suite Category</th>
          <th>Floor / Wing</th>
          <th>Operational Status</th>
          <th>Change Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
          <tr>
            <td>
              <strong style="font-size: 1.1rem; color: var(--color-obsidian);">Room <?= e($r['room_number']) ?></strong>
            </td>
            <td><?= e($r['suite_name']) ?></td>
            <td><?= e($r['floor']) ?></td>
            <td>
              <span class="status-chip <?= e($r['status']) ?>">
                <?= ucfirst(e($r['status'])) ?>
              </span>
            </td>
            <td>
              <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_room_status">
                <input type="hidden" name="room_id" value="<?= (int)$r['id'] ?>">
                <select name="status" class="form-control" style="width: auto; padding: 0.3rem 0.6rem; font-size: 0.78rem;">
                  <option value="available" <?= $r['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                  <option value="occupied" <?= $r['status'] === 'occupied' ? 'selected' : '' ?>>Occupied</option>
                  <option value="housekeeping" <?= $r['status'] === 'housekeeping' ? 'selected' : '' ?>>Housekeeping</option>
                  <option value="maintenance" <?= $r['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
                <button type="submit" class="btn btn-dark btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;">
                  Update
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 2. Suite Category Rates & Pricing Manager -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Suite Pricing & Seasonal Rates</h3>
    <span style="font-size: 0.8rem; color: #64748B;">Modify standard nightly base rates in INR (₹)</span>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Category Name</th>
          <th>Capacity</th>
          <th>Dimensions</th>
          <th>Current Nightly Rate (INR)</th>
          <th>Update Nightly Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($roomTypes as $rt): ?>
          <tr>
            <td>
              <strong><?= e($rt['name']) ?></strong>
              <div style="font-size: 0.75rem; color: #64748B;"><?= e($rt['view_type']) ?></div>
            </td>
            <td><?= (int)$rt['max_guests'] ?> Guests</td>
            <td><?= (int)$rt['room_size_sqft'] ?> sq.ft</td>
            <td>
              <strong style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--color-gold);">
                <?= format_inr($rt['price_per_night']) ?>
              </strong>
            </td>
            <td>
              <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_suite_price">
                <input type="hidden" name="room_type_id" value="<?= (int)$rt['id'] ?>">
                <input 
                  type="number" 
                  step="100" 
                  name="price_per_night" 
                  class="form-control" 
                  value="<?= (float)$rt['price_per_night'] ?>" 
                  style="width: 140px; padding: 0.3rem 0.6rem; font-size: 0.85rem;"
                  required
                >
                <button type="submit" class="btn btn-gold btn-sm" style="padding: 0.3rem 0.75rem; font-size: 0.75rem;">
                  Save Rate
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Physical Room -->
<div id="addRoomModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: var(--z-modal); display: none; align-items: center; justify-content: center; padding: 1.5rem;">
  <div style="background: #fff; border-radius: var(--radius-sm); max-width: 460px; width: 100%; padding: 2rem; box-shadow: var(--shadow-modal);">
    <h3 style="margin-bottom: 1rem;">Add Physical Room to Inventory</h3>
    <form method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_physical_room">

      <div class="form-group">
        <label for="roomNumber" class="form-label">Room Number *</label>
        <input type="text" id="roomNumber" name="room_number" class="form-control" placeholder="e.g. 104 / 203 / 302" required>
      </div>

      <div class="form-group">
        <label for="modalRoomType" class="form-label">Suite Category *</label>
        <select id="modalRoomType" name="room_type_id" class="form-control" required>
          <?php foreach ($roomTypes as $rt): ?>
            <option value="<?= (int)$rt['id'] ?>"><?= e($rt['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="floor" class="form-label">Floor / Wing</label>
        <input type="text" id="floor" name="floor" class="form-control" value="First Floor">
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
        <button type="button" onclick="document.getElementById('addRoomModal').style.display='none'" class="btn btn-dark btn-sm">
          Cancel
        </button>
        <button type="submit" class="btn btn-gold btn-sm">
          Register Room
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
