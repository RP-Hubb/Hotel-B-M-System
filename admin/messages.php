<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Concierge Inquiries & Guest Dispatch Inbox
 */

$adminTitle = 'Concierge Inquiries';
$adminNav = 'messages';

require_once __DIR__ . '/header.php';

$db = get_db();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token()) {
        flash_message('error', 'Security token expired.');
    } else {
        $msgId = (int)($_POST['message_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'read';

        if (in_array($newStatus, ['unread', 'read', 'replied', 'archived'])) {
            $stmt = $db->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $msgId]);
            flash_message('success', 'Message status updated.');
        }
    }
}

$stmtMsgs = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmtMsgs->fetchAll();
?>

<div class="admin-card">
  <div class="admin-card-header">
    <div>
      <h3 class="admin-card-title">Concierge & Event Inquiries</h3>
      <span style="font-size: 0.8rem; color: #64748B;">Dispatches received via the website contact form</span>
    </div>
  </div>

  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Received</th>
          <th>Sender Details</th>
          <th>Subject</th>
          <th>Message</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($messages)): ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 2.5rem; color: #64748B;">
              No contact inquiries currently pending.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($messages as $m): ?>
            <tr style="<?= $m['status'] === 'unread' ? 'background: #FFFBEB;' : '' ?>">
              <td>
                <div style="font-size: 0.8rem; color: #64748B;">
                  <?= date('d M Y', strtotime($m['created_at'])) ?><br>
                  <?= date('h:i A', strtotime($m['created_at'])) ?>
                </div>
              </td>
              <td>
                <strong><?= e($m['name']) ?></strong>
                <div style="font-size: 0.78rem;"><a href="mailto:<?= e($m['email']) ?>" style="color: var(--color-gold);"><?= e($m['email']) ?></a></div>
                <?php if (!empty($m['phone'])): ?>
                  <div style="font-size: 0.75rem; color: #64748B;"><?= e($m['phone']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= e($m['subject']) ?></strong>
              </td>
              <td style="max-width: 380px;">
                <div style="font-size: 0.88rem; line-height: 1.5; color: #334155;">
                  <?= nl2br(e($m['message'])) ?>
                </div>
              </td>
              <td>
                <span class="status-chip <?= $m['status'] === 'unread' ? 'maintenance' : ($m['status'] === 'replied' ? 'checked_in' : 'available') ?>">
                  <?= ucfirst(e($m['status'])) ?>
                </span>
              </td>
              <td>
                <form method="POST" style="display: flex; gap: 0.35rem;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="action" value="update_status">
                  <?php if ($m['status'] === 'unread'): ?>
                    <button type="submit" name="status" value="read" class="btn btn-dark btn-sm" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                      Mark Read
                    </button>
                  <?php else: ?>
                    <button type="submit" name="status" value="replied" class="btn btn-gold btn-sm" style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                      Mark Replied
                    </button>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
