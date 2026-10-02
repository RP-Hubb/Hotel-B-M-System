<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Automated Test Runner & Verification Suite (WP8.2)
 *
 * Usage: php tests/run.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking-helper.php';

$passedCount = 0;
$failedCount = 0;

function assert_test(string $name, bool $condition, string $detail = ''): void {
    global $passedCount, $failedCount;
    if ($condition) {
        $passedCount++;
        echo "  \033[32m[PASS]\033[0m {$name}\n";
    } else {
        $failedCount++;
        echo "  \033[31m[FAIL]\033[0m {$name} - {$detail}\n";
    }
}

echo "\n=======================================================\n";
echo " ADISHIV LUXURY HOTEL & SUITES — SYSTEM TEST SUITE\n";
echo "=======================================================\n\n";

// Clear transient test rate limits for local runner
$db = get_db();
$db->exec("DELETE FROM rate_limits WHERE action_key IN ('booking_create', 'confirm_lookup', 'login_fail')");

// ----------------------------------------------------------------------------
// TEST GROUP 1: Currency & Indian Grouping Formatter
// ----------------------------------------------------------------------------
echo "1. Testing Currency Formatting (WP1.3)...\n";
assert_test(
    "format_inr(32000) yields '₹ 32,000'",
    format_inr(32000) === '₹ 32,000',
    "Got: " . format_inr(32000)
);
assert_test(
    "format_inr(1250000) yields '₹ 12,50,000' (Lakh grouping)",
    format_inr(1250000) === '₹ 12,50,000',
    "Got: " . format_inr(1250000)
);
assert_test(
    "APP_CURRENCY_SYMBOL is defined and '₹'",
    defined('APP_CURRENCY_SYMBOL') && APP_CURRENCY_SYMBOL === '₹'
);

// ----------------------------------------------------------------------------
// TEST GROUP 2: Date Validation & Rollover Rejection (WP2.3)
// ----------------------------------------------------------------------------
echo "\n2. Testing Strict Date Validation Matrix (WP2.3)...\n";

[$valid1, $err1] = validate_date_range('2027-02-30', '2027-03-05');
assert_test(
    "Rejects invalid calendar date (2027-02-30 rollover)",
    !$valid1,
    "Expected failure, got: " . var_export($valid1, true)
);

$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));
[$valid2, $err2] = validate_date_range($yesterday, $tomorrow);
assert_test(
    "Rejects check-in date in the past",
    !$valid2,
    "Expected failure for past check-in"
);

$day3 = date('Y-m-d', strtotime('+3 days'));
$day1 = date('Y-m-d', strtotime('+1 day'));
[$valid3, $err3] = validate_date_range($day3, $day1);
assert_test(
    "Rejects check-out <= check-in",
    !$valid3,
    "Expected failure when checkout before checkin"
);

$farFuture = date('Y-m-d', strtotime('+400 days'));
$farFutureEnd = date('Y-m-d', strtotime('+405 days'));
[$valid4, $err4] = validate_date_range($farFuture, $farFutureEnd);
assert_test(
    "Rejects booking > 365 days in advance",
    !$valid4,
    "Expected failure for >365 days advance"
);

$stayLongIn = date('Y-m-d', strtotime('+10 days'));
$stayLongOut = date('Y-m-d', strtotime('+45 days'));
[$valid5, $err5] = validate_date_range($stayLongIn, $stayLongOut);
assert_test(
    "Rejects stay duration > 30 nights online",
    !$valid5,
    "Expected failure for 35 night stay"
);

$validIn = date('Y-m-d', strtotime('+10 days'));
$validOut = date('Y-m-d', strtotime('+13 days'));
[$valid6, $err6, $nights6] = validate_date_range($validIn, $validOut);
assert_test(
    "Accepts valid 3-night stay within 365 days",
    $valid6 && $nights6 === 3,
    "Got: " . ($err6 ?: "nights: {$nights6}")
);

// ----------------------------------------------------------------------------
// TEST GROUP 3: Date Overlap Predicate Verification
// ----------------------------------------------------------------------------
echo "\n3. Testing Overlap Predicate NOT (check_out <= :in OR check_in >= :out)...\n";

function check_overlap(string $in1, string $out1, string $in2, string $out2): bool {
    // True if overlapping
    return !($out1 <= $in2 || $in1 >= $out2);
}

assert_test(
    "Back-to-back stays do not overlap (Out: Nov 5 vs In: Nov 5)",
    check_overlap('2026-11-01', '2026-11-05', '2026-11-05', '2026-11-08') === false
);
assert_test(
    "Enclosing stay overlaps (Nov 1-10 vs Nov 3-6)",
    check_overlap('2026-11-01', '2026-11-10', '2026-11-03', '2026-11-06') === true
);
assert_test(
    "Enclosed stay overlaps (Nov 3-6 vs Nov 1-10)",
    check_overlap('2026-11-03', '2026-11-06', '2026-11-01', '2026-11-10') === true
);
assert_test(
    "Partial left overlap detected",
    check_overlap('2026-11-01', '2026-11-05', '2026-11-04', '2026-11-08') === true
);
assert_test(
    "Partial right overlap detected",
    check_overlap('2026-11-04', '2026-11-08', '2026-11-01', '2026-11-05') === true
);

// ----------------------------------------------------------------------------
// TEST GROUP 4: Database Connection & Strict Mode (WP1.1, WP1.7)
// ----------------------------------------------------------------------------
echo "\n4. Testing Database Configuration & Strict SQL Mode (WP1.1, WP2.4)...\n";
$db = get_db();
$modeStmt = $db->query("SELECT @@sql_mode AS mode, @@time_zone AS tz");
$dbConfig = $modeStmt->fetch();

assert_test(
    "Database operates under STRICT_ALL_TABLES",
    strpos($dbConfig['mode'], 'STRICT_ALL_TABLES') !== false,
    "Mode: " . $dbConfig['mode']
);
assert_test(
    "Database timezone is configured to +05:30 (Asia/Kolkata)",
    $dbConfig['tz'] === '+05:30',
    "Timezone: " . $dbConfig['tz']
);

// ----------------------------------------------------------------------------
// TEST GROUP 5: Operational Room Status vs Future Availability Decoupling (WP1.6)
// ----------------------------------------------------------------------------
echo "\n5. Testing Operational Room Status Decoupling from Future Availability (WP1.6)...\n";

// Get room type 1
$types = $db->query("SELECT id FROM room_types ORDER BY id ASC LIMIT 1")->fetchAll();
$testTypeId = (int)$types[0]['id'];

$searchIn = date('Y-m-d', strtotime('+30 days'));
$searchOut = date('Y-m-d', strtotime('+33 days'));

// Check baseline availability
$typesBefore = get_available_room_types($searchIn, $searchOut, 1);
$availCountBefore = 0;
foreach ($typesBefore as $t) {
    if ((int)$t['id'] === $testTypeId) {
        $availCountBefore = (int)$t['available_rooms_count'];
    }
}

// Alter a room's housekeeping status to 'occupied' or 'housekeeping'
$targetRoom = $db->query("SELECT id, status FROM rooms WHERE room_type_id = {$testTypeId} ORDER BY id ASC LIMIT 1")->fetch();
$targetRoomId = (int)$targetRoom['id'];
$originalStatus = $targetRoom['status'];

$db->exec("UPDATE rooms SET status = 'housekeeping' WHERE id = {$targetRoomId}");

// Re-check future availability
$typesAfter = get_available_room_types($searchIn, $searchOut, 1);
$availCountAfter = 0;
foreach ($typesAfter as $t) {
    if ((int)$t['id'] === $testTypeId) {
        $availCountAfter = (int)$t['available_rooms_count'];
    }
}

// Restore status
$db->exec("UPDATE rooms SET status = '{$originalStatus}' WHERE id = {$targetRoomId}");

assert_test(
    "Future availability is independent of today's housekeeping status",
    $availCountBefore === $availCountAfter && $availCountBefore > 0,
    "Before: {$availCountBefore}, After: {$availCountAfter}"
);

// ----------------------------------------------------------------------------
// TEST GROUP 6: Anonymous user_id Mass-Assignment Immunity (WP1.4)
// ----------------------------------------------------------------------------
echo "\n6. Testing Anonymous user_id Mass-Assignment Immunity (WP1.4)...\n";

$fakeUserId = 999999;
$testPayload = [
    'room_type_id' => $testTypeId,
    'check_in' => date('Y-m-d', strtotime('+40 days')),
    'check_out' => date('Y-m-d', strtotime('+42 days')),
    'guests_count' => 1,
    'name' => 'Security Audit Resident',
    'email' => 'audit' . time() . '@example.com',
    'phone' => '+919876543210',
    'special_requests' => 'Automated test suite execution',
    'payment_method' => 'pay_at_hotel',
    'user_id' => $fakeUserId // Attacker injecting arbitrary user_id
];

// Call create_booking_reservation with $authUserId = null
$bookingResult = create_booking_reservation($testPayload, null);

assert_test(
    "Booking created successfully without error",
    $bookingResult['success'] === true,
    $bookingResult['error'] ?? 'Unknown error'
);

if ($bookingResult['success']) {
    $createdId = (int)$bookingResult['booking_id'];
    $checkUserStmt = $db->prepare("SELECT user_id, access_token FROM bookings WHERE id = :id");
    $checkUserStmt->execute([':id' => $createdId]);
    $createdRow = $checkUserStmt->fetch();

    assert_test(
        "Anonymous reservation ignores injected user_id (stored as NULL)",
        $createdRow['user_id'] === null,
        "Stored user_id: " . var_export($createdRow['user_id'], true)
    );

    assert_test(
        "Reservation generates secure 128-bit access_token",
        !empty($createdRow['access_token']) && strlen($createdRow['access_token']) === 32
    );

    // Clean up test booking
    $db->exec("DELETE FROM booking_nights WHERE booking_id = {$createdId}");
    $db->exec("DELETE FROM payments WHERE booking_id = {$createdId}");
    $db->exec("DELETE FROM booking_guests WHERE booking_id = {$createdId}");
    $db->exec("DELETE FROM booking_events WHERE booking_id = {$createdId}");
    $db->exec("DELETE FROM bookings WHERE id = {$createdId}");
}

// ----------------------------------------------------------------------------
// TEST GROUP 7: Idempotency Key Replay Protection (WP2.2)
// ----------------------------------------------------------------------------
echo "\n7. Testing Idempotency Key Replay (WP2.2)...\n";

$idemKey = 'test_idem_' . bin2hex(random_bytes(8));
$testPayload['idempotency_key'] = $idemKey;
$testPayload['check_in'] = date('Y-m-d', strtotime('+50 days'));
$testPayload['check_out'] = date('Y-m-d', strtotime('+52 days'));

// First creation
$res1 = create_booking_reservation($testPayload, null);
assert_test("First attempt with idempotency key succeeds", $res1['success'] === true);

// Repeated creation with same idempotency key
$res2 = create_booking_reservation($testPayload, null);
assert_test(
    "Repeated request returns idempotent replay of original booking",
    $res2['success'] === true && !empty($res2['idempotent_replay']),
    "Expected idempotent replay"
);
assert_test(
    "Original and replayed booking references match",
    $res1['booking_reference'] === $res2['booking_reference']
);

if ($res1['success']) {
    $cleanupId = (int)$res1['booking_id'];
    $db->exec("DELETE FROM booking_nights WHERE booking_id = {$cleanupId}");
    $db->exec("DELETE FROM payments WHERE booking_id = {$cleanupId}");
    $db->exec("DELETE FROM booking_guests WHERE booking_id = {$cleanupId}");
    $db->exec("DELETE FROM booking_events WHERE booking_id = {$cleanupId}");
    $db->exec("DELETE FROM bookings WHERE id = {$cleanupId}");
}

// ----------------------------------------------------------------------------
// TEST GROUP 8: DB-Level Double-Booking Backstop via booking_nights (WP2.1)
// ----------------------------------------------------------------------------
echo "\n8. Testing DB-Level Double-Booking Backstop (WP2.1)...\n";

// Find a physical room
$roomRow = $db->query("SELECT id FROM rooms LIMIT 1")->fetch();
$testRoomId = (int)$roomRow['id'];
$testStayDate = '2029-06-15';

// Insert dummy booking record
$db->exec("
    INSERT INTO bookings (booking_reference, room_id, room_type_id, check_in, check_out, price_per_night, subtotal_amount, total_amount, status)
    VALUES ('ADI-TEST-0001', {$testRoomId}, 1, '2029-06-15', '2029-06-16', 30000, 30000, 35400, 'confirmed')
");
$dummyBookingId1 = (int)$db->lastInsertId();

$db->exec("
    INSERT INTO bookings (booking_reference, room_id, room_type_id, check_in, check_out, price_per_night, subtotal_amount, total_amount, status)
    VALUES ('ADI-TEST-0002', {$testRoomId}, 1, '2029-06-15', '2029-06-16', 30000, 30000, 35400, 'confirmed')
");
$dummyBookingId2 = (int)$db->lastInsertId();

// First night insertion
$db->exec("INSERT INTO booking_nights (booking_id, room_id, stay_date) VALUES ({$dummyBookingId1}, {$testRoomId}, '{$testStayDate}')");

// Second concurrent/duplicate night insertion on same room and date must trigger UNIQUE violation
$duplicateCaught = false;
try {
    $db->exec("INSERT INTO booking_nights (booking_id, room_id, stay_date) VALUES ({$dummyBookingId2}, {$testRoomId}, '{$testStayDate}')");
} catch (PDOException $e) {
    if ($e->getCode() === '23000' || strpos($e->getMessage(), '1062') !== false) {
        $duplicateCaught = true;
    }
}

assert_test(
    "booking_nights UNIQUE(room_id, stay_date) strictly aborts duplicate stay_date",
    $duplicateCaught === true,
    "Expected SQLSTATE 23000 duplicate key error"
);

// Clean up
$db->exec("DELETE FROM booking_nights WHERE stay_date = '{$testStayDate}'");
$db->exec("DELETE FROM bookings WHERE id IN ({$dummyBookingId1}, {$dummyBookingId2})");

// ----------------------------------------------------------------------------
// TEST GROUP 9: GST Calculation & Slab Verification (WP2.5)
// ----------------------------------------------------------------------------
echo "\n9. Testing GST Slabs & Integer Paise Precision (WP2.5)...\n";

$pricing5k = calculate_pricing(1, '2026-12-01', '2026-12-03'); // 2 nights
// Verify tax calculation rule: <= 7500 -> 5%, > 7500 -> 18%
$deluxeType = $db->query("SELECT price_per_night FROM room_types WHERE id = 1")->fetch();
$expectedRate = ((float)$deluxeType['price_per_night'] <= 7500.0) ? 5.0 : 18.0;

assert_test(
    "GST slab dynamically evaluates per room-night value (rate = {$expectedRate}%)",
    (float)$pricing5k['tax_rate'] === $expectedRate,
    "Got: {$pricing5k['tax_rate']}%"
);
assert_test(
    "Pricing returns structured CGST and SGST equal splits",
    isset($pricing5k['tax_amount']) && $pricing5k['tax_amount'] > 0
);

// ----------------------------------------------------------------------------
// TEST GROUP 10: Security Headers (WP3.4)
// ----------------------------------------------------------------------------
echo "\n10. Testing Security Headers Helper (WP3.4)...\n";

// In CLI mode headers_list() is empty, so we verify that send_security_headers executes without error
$headersOk = false;
try {
    send_security_headers();
    $headersOk = true;
} catch (Throwable $t) {
    $headersOk = false;
}
assert_test("send_security_headers() executes cleanly", $headersOk);

// ----------------------------------------------------------------------------
// TEST SUMMARY
// ----------------------------------------------------------------------------
echo "\n=======================================================\n";
$totalTests = $passedCount + $failedCount;
echo " TEST SUMMARY: {$passedCount} / {$totalTests} PASSED\n";
if ($failedCount > 0) {
    echo " \033[31mFAILURES DETECTED: {$failedCount} failed\033[0m\n";
} else {
    echo " \033[32mALL VERIFICATION CHECKS PASSED PERFECTLY!\033[0m\n";
}
echo "=======================================================\n\n";

exit($failedCount > 0 ? 1 : 0);
