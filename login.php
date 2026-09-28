<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Guest & Resident Authentication
 */

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    if (is_admin()) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session expired. Please reload and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } elseif (login_user($email, $password)) {
            flash_message('success', 'Welcome back to Adishiv.');
            if (is_admin()) {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Invalid credentials provided. Please verify your email and password.';
        }
    }
}

$pageTitle = 'Sign In | Adishiv Luxury Hotel New Delhi';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-obsidian); color: #fff; padding: 7.5rem 0 3.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Resident Portal</div>
    <h1 style="color: #fff; font-size: clamp(2rem, 3.5vw, 3rem); margin-bottom: 0.5rem;">
      Welcome to Adishiv
    </h1>
    <p style="color: rgba(255,255,255,0.7); font-size: 1rem;">
      Access your private reservations, room preferences, and concierge services.
    </p>
  </div>
</div>

<div class="container section-spacing" style="max-width: 520px; padding-top: 3rem;">
  <div style="background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 2.5rem; box-shadow: var(--shadow-card);">
    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="resident@domain.com" required autofocus>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-gold btn-lg" style="width: 100%; margin-top: 1rem; margin-bottom: 1.5rem;">
        Sign In to Portal
      </button>

      <div style="text-align: center; font-size: 0.88rem; color: var(--color-text-muted);">
        Don't have an account? <a href="register.php" style="color: var(--color-gold); font-weight: 600;">Create Resident Profile</a>
      </div>

      <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--color-border-hairline); font-size: 0.78rem; color: var(--color-text-muted); text-align: center;">
        Default Staff Demo Account: <code>admin@adishivhotel.com</code> / <code>Admin@Adishiv2026</code>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
