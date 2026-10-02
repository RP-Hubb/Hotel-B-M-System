<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = get_db();

$stmt = $pdo->query("SELECT id, booking_reference, guest_name, check_in, check_out, status FROM bookings ORDER BY id DESC LIMIT 10");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent bookings in DB:\n";
foreach ($bookings as $b) {
    echo "ID: {$b['id']} | Ref: {$b['booking_reference']} | Guest: {$b['guest_name']} | Dates: {$b['check_in']} -> {$b['check_out']} | Status: {$b['status']}\n";
}
