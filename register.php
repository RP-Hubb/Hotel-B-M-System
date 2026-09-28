<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Resident Registration
 */

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $error = 'Security session expired. Please reload and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters in length.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Password confirmation does not match.';
        } else {
            $db = get_db();
            // Check if email already registered
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email address already exists. Please log in.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmtInsert = $db->prepare("
                    INSERT INTO users (name, email, phone, password_hash, role, status) 
                    VALUES (:name, :email, :phone, :hash, 'guest', 'active')
                ");
                $stmtInsert->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':hash' => $hash
                ]);

                // Auto login
                login_user($email, $password);
                flash_message('success', 'Your Adishiv resident profile has been created.');
                header('Location: index.php');
                exit;
            }
        }
    }
}

$pageTitle = 'Create Resident Profile | Adishiv Luxury Hotel New Delhi';
require_once __DIR__ . '/includes/header.php';
?>

<div style="background-color: var(--color-obsidian); color: #fff; padding: 7.5rem 0 3.5rem; text-align: center;">
  <div class="container-narrow">
    <div class="eyebrow center" style="color: var(--color-gold-light);">Membership & Reservations</div>
    <h1 style="color: #fff; font-size: clamp(2rem, 3.5vw, 3rem); margin-bottom: 0.5rem;">
      Create Resident Profile
    </h1>
    <p style="color: rgba(255,255,255,0.7); font-size: 1rem;">
      Join Adishiv for privileged suite reservation rates and tailored hospitality.
    </p>
  </div>
</div>

<div class="container section-spacing" style="max-width: 540px; padding-top: 3rem;">
  <div style="background: #FFFFFF; border: 1px solid var(--color-border-subtle); border-radius: var(--radius-sm); padding: 2.5rem; box-shadow: var(--shadow-card);">
    <?php if ($error): ?>
      <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="name" class="form-label">Full Name *</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Your full name" required>
      </div>

      <div class="form-group">
        <label for="email" class="form-label">Email Address *</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="resident@domain.com" required>
      </div>

      <div class="form-group">
        <label for="phone" class="form-label">Contact Mobile</label>
        <input type="tel" id="phone" name="phone" class="form-control" placeholder="+91 / International">
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password * (Min 8 characters)</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label for="password_confirm" class="form-label">Confirm Password *</label>
        <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-gold btn-lg" style="width: 100%; margin-top: 1rem; margin-bottom: 1.5rem;">
        Complete Registration
      </button>

      <div style="text-align: center; font-size: 0.88rem; color: var(--color-text-muted);">
        Already registered? <a href="login.php" style="color: var(--color-gold); font-weight: 600;">Sign in to your profile</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
