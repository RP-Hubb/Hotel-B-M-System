<?php
/**
 * Adishiv Luxury Hotel & Suites
 * CLI Tool: Create or Update Administrator Account
 *
 * Usage:
 *   Interactive: php bin/create-admin.php
 *   Automated:   php bin/create-admin.php --email=admin@adishivhotel.com --password=YourPassword123 --name="Head Concierge"
 */

// 1. Strictly enforce CLI execution
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Error: This command can only be executed via the command line interface.\n";
    exit(1);
}

require_once __DIR__ . '/../config/database.php';

echo "====================================================================\n";
echo "  Adishiv Luxury Hotel - Administrator Account Generator\n";
echo "====================================================================\n\n";

// Parse CLI options if provided
$opts = getopt('', ['email:', 'password:', 'name:', 'phone:']);

// Helper for CLI prompts
function prompt(string $question, string $default = ''): string {
    $prompt = $default !== '' ? "{$question} [{$default}]: " : "{$question}: ";
    echo $prompt;
    $input = trim(fgets(STDIN));
    return $input !== '' ? $input : $default;
}

$email = $opts['email'] ?? prompt("Admin Email Address");
while (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "  [!] Invalid email address format. Please enter a valid email.\n";
    $email = prompt("Admin Email Address");
}

$name = $opts['name'] ?? prompt("Full Name", "Adishiv Concierge");

$password = $opts['password'] ?? '';
if ($password === '') {
    // Interactive prompt with length check
    while (true) {
        $password = prompt("Password (minimum 12 characters)");
        if (strlen($password) < 12) {
            echo "  [!] Password must be at least 12 characters in length for administrative security.\n";
            continue;
        }
        break;
    }
} else {
    if (strlen($password) < 12) {
        echo "Error: Provided password must be at least 12 characters in length.\n";
        exit(1);
    }
}

$phone = $opts['phone'] ?? '+91 11 4982 7700';

// Generate bcrypt hash with cost >= 12
$cost = 12;
$passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);

try {
    $db = get_db();

    // Check if user already exists
    $stmt = $db->prepare("SELECT id, role FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $update = $db->prepare("
            UPDATE users 
            SET name = :name, 
                password_hash = :hash, 
                phone = :phone, 
                role = 'admin', 
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $update->execute([
            ':name' => $name,
            ':hash' => $passwordHash,
            ':phone' => $phone,
            ':id' => $existing['id']
        ]);
        echo "\n[OK] Administrator account for '{$email}' successfully updated (Role: admin, Hash Cost: {$cost}).\n";
    } else {
        $insert = $db->prepare("
            INSERT INTO users (name, email, password_hash, phone, role, status)
            VALUES (:name, :email, :hash, :phone, 'admin', 'active')
        ");
        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':phone' => $phone
        ]);
        echo "\n[OK] Administrator account for '{$email}' successfully created (Role: admin, Hash Cost: {$cost}).\n";
    }

    echo "Login URL: " . APP_URL . "/admin/login.php\n";
    exit(0);

} catch (PDOException $e) {
    echo "\n[!] Database error: " . $e->getMessage() . "\n";
    exit(1);
}
