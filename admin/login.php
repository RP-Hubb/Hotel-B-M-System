<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Dedicated Staff & Admin Login
 */

require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in() && is_admin()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session expired. Please reload and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (login_user($email, $password)) {
            if (is_admin()) {
                header('Location: index.php');
                exit;
            } else {
                logout_user();
                $error = 'Access restricted to hotel administrators.';
            }
        } else {
            $error = 'Invalid administrative credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff & Concierge Access | Adishiv Luxury Hotel</title>
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/admin.css') ?>">
</head>
<body style="background-color: var(--color-obsidian); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">

<div style="background: #FFFFFF; border-radius: var(--radius-sm); padding: 2.75rem 2.5rem; width: 100%; max-width: 440px; box-shadow: var(--shadow-modal);">
  <div style="text-align: center; margin-bottom: 2rem;">
    <div style="font-family: var(--font-serif); font-size: 2rem; font-weight: 500; letter-spacing: 0.2em; text-transform: uppercase;">Adishiv</div>
    <span class="admin-badge" style="margin-top: 0.5rem; display: inline-block;">Hotel Management Portal</span>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error" style="font-size: 0.85rem; padding: 0.75rem 1rem;"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="adminEmail" class="form-label">Staff Email</label>
      <input type="email" id="adminEmail" name="email" class="form-control" value="admin@adishivhotel.com" required autofocus>
    </div>

    <div class="form-group">
      <label for="adminPassword" class="form-label">Password</label>
      <input type="password" id="adminPassword" name="password" class="form-control" placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn btn-gold btn-lg" style="width: 100%; margin-top: 1rem;">
      Enter Portal
    </button>
  </form>

  <div style="margin-top: 2rem; text-align: center;">
    <a href="<?= asset_url('index.php') ?>" style="font-size: 0.8rem; color: var(--color-text-muted);">
      ← Return to Public Website
    </a>
  </div>
</div>

</body>
</html>
