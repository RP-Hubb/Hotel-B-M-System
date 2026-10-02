<?php
require_once __DIR__ . '/../config/database.php';
$db = get_db();
$stmt = $db->query('SELECT id, booking_reference, room_id, room_type_id, check_in, check_out, status FROM bookings ORDER BY id DESC LIMIT 5');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($r) . "\n";
}
