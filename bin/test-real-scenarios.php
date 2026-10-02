<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Deep Real-World Integration & Scenario Testing
 * Covers Sections 6, 7, 8, 9, 10, 11, 12 of the Verification Spec
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking-helper.php';

$db = get_db();
$passed = 0;
$failed = 0;

function verify_case(string $label, bool $condition, string $detail = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [PASS] {$label}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$label} " . ($detail ? "- {$detail}" : "") . "\n";
    }
}

echo "=======================================================\n";
echo " ADISHIV — REAL-WORLD SCENARIO VERIFICATION ENGINE\n";
echo "=======================================================\n\n";

// Clear transient rate limits for verification run
$db->exec("DELETE FROM rate_limits WHERE action_key IN ('booking_create', 'confirm_lookup', 'login_fail')");

// ----------------------------------------------------------------------------
// SECTION 7: REAL AVAILABILITY LOGIC (Cases A, B, C, D, E)
// ----------------------------------------------------------------------------
echo "7. REAL AVAILABILITY LOGIC MATRIX (Cases A, B, C, D, E)...\n";

// Target the Presidential Residence (only 1 physical room in hotel inventory)
$presidentialType = $db->query("SELECT id FROM room_types WHERE slug LIKE '%presidential%' LIMIT 1")->fetch();
$presidentialTypeId = (int)$presidentialType['id'];
$presRoom = $db->query("SELECT id FROM rooms WHERE room_type_id = {$presidentialTypeId} LIMIT 1")->fetch();
$presRoomId = (int)$presRoom['id'];

// Default room type
$defaultTypeId = (int)$db->query("SELECT id FROM room_types ORDER BY id ASC LIMIT 1")->fetchColumn();

// Define test date range: 2027-01-10 to 2027-01-15
$bIn = '2027-01-10';
$bOut = '2027-01-15';

// Ensure no conflicting test bookings exist
$db->exec("DELETE FROM booking_nights WHERE room_id = {$presRoomId} AND stay_date BETWEEN '2027-01-01' AND '2027-01-31'");
$db->exec("DELETE FROM bookings WHERE room_id = {$presRoomId} AND check_in >= '2027-01-01' AND check_out <= '2027-01-31'");

// Create base booking on Presidential Residence for Jan 10 -> Jan 15
$baseBookingResult = create_booking_reservation([
    'room_type_id' => $presidentialTypeId,
    'check_in' => $bIn,
    'check_out' => $bOut,
    'guests_count' => 2,
    'name' => 'Diplomatic Residence Guest',
    'email' => 'ambassador@embassy.gov.in',
    'phone' => '+911124198000',
    'special_requests' => 'Protocol VIP arrival',
    'payment_method' => 'pay_at_hotel'
], null);

verify_case("Base booking created on Presidential Residence (Jan 10-15)", $baseBookingResult['success'] === true);
$baseBookingId = (int)($baseBookingResult['booking_id'] ?? 0);

// Helper to query availability of Presidential Residence
function check_presidential_avail(string $cin, string $cout, int $typeId): int {
    $types = get_available_room_types($cin, $cout, 1);
    foreach ($types as $t) {
        if ((int)$t['id'] === $typeId) {
            return (int)$t['available_rooms_count'];
        }
    }
    return 0;
}

// Case A: Exact search Jan 10 -> Jan 15
$availA = check_presidential_avail('2027-01-10', '2027-01-15', $presidentialTypeId);
verify_case("Case A (Exact Jan 10-15): Unavailable", $availA === 0, "Got: {$availA} available");

// Case B: Enclosed search Jan 12 -> Jan 14
$availB = check_presidential_avail('2027-01-12', '2027-01-14', $presidentialTypeId);
verify_case("Case B (Enclosed Jan 12-14): Unavailable", $availB === 0, "Got: {$availB} available");

// Case C: Back-to-back following search Jan 15 -> Jan 18
$availC = check_presidential_avail('2027-01-15', '2027-01-18', $presidentialTypeId);
verify_case("Case C (Following Jan 15-18): Available", $availC === 1, "Got: {$availC} available");

// Case D: Back-to-back preceding search Jan 5 -> Jan 10
$availD = check_presidential_avail('2027-01-05', '2027-01-10', $presidentialTypeId);
verify_case("Case D (Preceding Jan 5-10): Available", $availD === 1, "Got: {$availD} available");

// Case E: Concurrent / Second booking attempt for overlapping dates (Jan 12 -> Jan 16)
$secondBookingResult = create_booking_reservation([
    'room_type_id' => $presidentialTypeId,
    'check_in' => '2027-01-12',
    'check_out' => '2027-01-16',
    'guests_count' => 2,
    'name' => 'Concurrent Attempt Guest',
    'email' => 'guest2@example.com',
    'phone' => '+919811122233',
    'payment_method' => 'pay_at_hotel'
], null);
verify_case(
    "Case E (Overlapping booking attempt): Cleanly rejected",
    $secondBookingResult['success'] === false && strpos($secondBookingResult['error'], 'no longer available') !== false,
    "Got: " . ($secondBookingResult['error'] ?? 'Success unexpectedly')
);

// Clean up Case test booking
if ($baseBookingId > 0) {
    $db->exec("DELETE FROM booking_nights WHERE booking_id = {$baseBookingId}");
    $db->exec("DELETE FROM payments WHERE booking_id = {$baseBookingId}");
    $db->exec("DELETE FROM booking_guests WHERE booking_id = {$baseBookingId}");
    $db->exec("DELETE FROM booking_events WHERE booking_id = {$baseBookingId}");
    $db->exec("DELETE FROM bookings WHERE id = {$baseBookingId}");
}

// ----------------------------------------------------------------------------
// SECTION 6: STRICT INPUT VALIDATION MATRIX
// ----------------------------------------------------------------------------
echo "\n6. STRICT INPUT VALIDATION CHECKS...\n";

// Invalid calendar date rollover
$invRes1 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2027-02-30', 'check_out' => '2027-03-05',
    'guests_count' => 1, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '+919876543210'
], null);
verify_case("Rejects 2027-02-30 rollover date", $invRes1['success'] === false);

// 0 guests
$invRes2 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2026-11-20', 'check_out' => '2026-11-23',
    'guests_count' => 0, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '+919876543210'
], null);
verify_case("Rejects 0 guests count explicitly", $invRes2['success'] === false);

// Negative guests
$invRes3 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2026-11-20', 'check_out' => '2026-11-23',
    'guests_count' => -3, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '+919876543210'
], null);
verify_case("Rejects negative guests count", $invRes3['success'] === false);

// Excessive guests (> room max_guests = 2)
$invRes4 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2026-11-20', 'check_out' => '2026-11-23',
    'guests_count' => 8, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '+919876543210'
], null);
verify_case("Rejects party size exceeding room capacity", $invRes4['success'] === false);

// Invalid email
$invRes5 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2026-11-20', 'check_out' => '2026-11-23',
    'guests_count' => 1, 'name' => 'Valid Name', 'email' => 'not-an-email', 'phone' => '+919876543210'
], null);
verify_case("Rejects invalid email format", $invRes5['success'] === false);

// Invalid phone (too short)
$invRes6 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2026-11-20', 'check_out' => '2026-11-23',
    'guests_count' => 1, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '123'
], null);
verify_case("Rejects phone number < 7 digits", $invRes6['success'] === false);

// Excessive stay length (>30 nights)
$invRes7 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2026-11-20', 'check_out' => '2027-01-10',
    'guests_count' => 1, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '+919876543210'
], null);
verify_case("Rejects online stay > 30 nights", $invRes7['success'] === false);

// Booking > 365 days advance
$invRes8 = create_booking_reservation([
    'room_type_id' => 1, 'check_in' => '2028-05-01', 'check_out' => '2028-05-04',
    'guests_count' => 1, 'name' => 'Valid Name', 'email' => 'valid@test.com', 'phone' => '+919876543210'
], null);
verify_case("Rejects booking > 365 days in advance", $invRes8['success'] === false);

// ----------------------------------------------------------------------------
// SECTION 8: IDEMPOTENCY REPLAY VERIFICATION
// ----------------------------------------------------------------------------
echo "\n8. IDEMPOTENCY KEY REPLAY VERIFICATION...\n";
$db->exec("DELETE FROM rate_limits WHERE action_key = 'booking_create'");

$idemKey = 'e2e_idem_' . bin2hex(random_bytes(8));
$idemPayload = [
    'room_type_id' => $defaultTypeId,
    'check_in' => '2027-03-10',
    'check_out' => '2027-03-13',
    'guests_count' => 2,
    'name' => 'Idempotent Test Guest',
    'email' => 'idem@test.com',
    'phone' => '+919876543210',
    'payment_method' => 'pay_at_hotel',
    'idempotency_key' => $idemKey
];

$idemRes1 = create_booking_reservation($idemPayload, null);
verify_case("First idempotency submission creates booking", $idemRes1['success'] === true, $idemRes1['error'] ?? '');

$idemRes2 = create_booking_reservation($idemPayload, null);
verify_case("Second submission returns idempotent replay flag", !empty($idemRes2['idempotent_replay']), $idemRes2['error'] ?? '');
verify_case("Replayed reference matches original reference", ($idemRes1['booking_reference'] ?? 'A') === ($idemRes2['booking_reference'] ?? 'B'));

// Verify only 1 booking record was written
$countStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE idempotency_key = :k");
$countStmt->execute([':k' => $idemKey]);
verify_case("Exactly 1 database booking record created for key", (int)$countStmt->fetchColumn() === 1);

// Cleanup
if (!empty($idemRes1['booking_id'])) {
    $bId = (int)$idemRes1['booking_id'];
    $db->exec("DELETE FROM booking_nights WHERE booking_id = {$bId}");
    $db->exec("DELETE FROM payments WHERE booking_id = {$bId}");
    $db->exec("DELETE FROM booking_guests WHERE booking_id = {$bId}");
    $db->exec("DELETE FROM booking_events WHERE booking_id = {$bId}");
    $db->exec("DELETE FROM bookings WHERE id = {$bId}");
}

// ----------------------------------------------------------------------------
// SECTION 10: ADMIN STATUS MACHINE TRANSITIONS & AUDIT
// ----------------------------------------------------------------------------
echo "\n10. ADMIN STATUS MACHINE TRANSITIONS & AUDIT TRAIL...\n";
$db->exec("DELETE FROM rate_limits WHERE action_key = 'booking_create'");

// Create a confirmed booking to test transitions
$tBooking = create_booking_reservation([
    'room_type_id' => $defaultTypeId,
    'check_in' => '2027-04-01',
    'check_out' => '2027-04-04',
    'guests_count' => 1,
    'name' => 'State Machine Test Resident',
    'email' => 'statemachine@test.com',
    'phone' => '+919876543210',
    'payment_method' => 'pay_at_hotel'
], null);

$tBookingId = (int)($tBooking['booking_id'] ?? 0);
verify_case("Test reservation created in confirmed state", $tBookingId > 0, $tBooking['error'] ?? '');

// Transition: confirmed -> checked_in (Valid)
$resTrans1 = transition_booking_status($tBookingId, 'checked_in', 'frontdesk_manager', 'Resident checked in at reception');
verify_case("Transition confirmed -> checked_in succeeds", $resTrans1['success'] === true);

// Transition: checked_in -> checked_out (Valid)
$resTrans2 = transition_booking_status($tBookingId, 'checked_out', 'frontdesk_manager', 'Room keys returned and bill settled');
verify_case("Transition checked_in -> checked_out succeeds", $resTrans2['success'] === true);

// Attempt INVALID transition: checked_out -> confirmed (Forbidden)
$resTrans3 = transition_booking_status($tBookingId, 'confirmed', 'rogue_actor', 'Attempt to reset checked out booking');
verify_case("Invalid transition checked_out -> confirmed is rejected", $resTrans3['success'] === false);

// Attempt INVALID transition: checked_out -> checked_in (Forbidden)
$resTrans4 = transition_booking_status($tBookingId, 'checked_in', 'rogue_actor', 'Attempt to re-checkin');
verify_case("Invalid transition checked_out -> checked_in is rejected", $resTrans4['success'] === false);

// Verify audit events logged in booking_events
$auditStmt = $db->prepare("SELECT COUNT(*) FROM booking_events WHERE booking_id = :bid");
$auditStmt->execute([':bid' => $tBookingId]);
$eventCount = (int)$auditStmt->fetchColumn();
verify_case("Booking audit events recorded in booking_events (expected >= 3)", $eventCount >= 3, "Count: {$eventCount}");

// Cleanup
$db->exec("DELETE FROM booking_nights WHERE booking_id = {$tBookingId}");
$db->exec("DELETE FROM payments WHERE booking_id = {$tBookingId}");
$db->exec("DELETE FROM booking_guests WHERE booking_id = {$tBookingId}");
$db->exec("DELETE FROM booking_events WHERE booking_id = {$tBookingId}");
$db->exec("DELETE FROM bookings WHERE id = {$tBookingId}");

// ----------------------------------------------------------------------------
// SECTION 11: CONFIDENTIALITY & ACCESS TOKEN AUTHORIZATION
// ----------------------------------------------------------------------------
echo "\n11. CONFIDENTIALITY GATE & ACCESS TOKEN LOOKUP...\n";
$db->exec("DELETE FROM rate_limits WHERE action_key = 'booking_create'");

// Create reservation to test voucher access
$voucherBooking = create_booking_reservation([
    'room_type_id' => $defaultTypeId,
    'check_in' => '2027-05-10',
    'check_out' => '2027-05-13',
    'guests_count' => 1,
    'name' => 'Confidential Resident',
    'email' => 'resident.secret@example.com',
    'phone' => '+919876543210',
    'payment_method' => 'pay_at_hotel'
], null);

$vRef = $voucherBooking['booking_reference'] ?? '';
$vToken = $voucherBooking['access_token'] ?? '';
$vBookingId = (int)($voucherBooking['booking_id'] ?? 0);

// 1. Request confirmation with reference ONLY (No token, no email) via cURL
$ch = curl_init("http://127.0.0.1:8000/confirmation.php?ref={$vRef}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$htmlNoAuth = curl_exec($ch);
curl_close($ch);

// Verify that the voucher content is BLOCKED and the email challenge form is displayed
$blockedProperly = (strpos($htmlNoAuth, 'Confidential Sanctuary Access') !== false) 
                && (strpos($htmlNoAuth, 'Official Booking Voucher') === false);
verify_case("Voucher lookup with reference alone triggers confidential email challenge", $blockedProperly);

// 2. Request confirmation with reference AND valid access token
$ch = curl_init("http://127.0.0.1:8000/confirmation.php?ref={$vRef}&token={$vToken}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$htmlToken = curl_exec($ch);
curl_close($ch);

$tokenSuccess = (strpos($htmlToken, 'Official Booking Voucher') !== false) 
             && (strpos($htmlToken, $vRef) !== false)
             && (strpos($htmlToken, 'Central GST (CGST') !== false);
verify_case("Voucher lookup with valid access_token renders complete voucher", $tokenSuccess);

// 3. Request confirmation with reference AND matching email
$ch = curl_init("http://127.0.0.1:8000/confirmation.php?ref={$vRef}&email=resident.secret@example.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$htmlEmail = curl_exec($ch);
curl_close($ch);

$emailSuccess = (strpos($htmlEmail, 'Official Booking Voucher') !== false) 
             && (strpos($htmlEmail, $vRef) !== false);
verify_case("Voucher lookup with verified resident email renders complete voucher", $emailSuccess);

// Cleanup
$db->exec("DELETE FROM booking_nights WHERE booking_id = {$vBookingId}");
$db->exec("DELETE FROM payments WHERE booking_id = {$vBookingId}");
$db->exec("DELETE FROM booking_guests WHERE booking_id = {$vBookingId}");
$db->exec("DELETE FROM booking_events WHERE booking_id = {$vBookingId}");
$db->exec("DELETE FROM bookings WHERE id = {$vBookingId}");

// ----------------------------------------------------------------------------
// SECTION 12: API SECURITY CHECKS
// ----------------------------------------------------------------------------
echo "\n12. DIRECT API ENDPOINTS SECURITY CHECKS...\n";

// 1. POST to create-booking.php without CSRF
$ch = curl_init("http://127.0.0.1:8000/api/create-booking.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['check_in' => '2026-11-01']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resCsrf = curl_exec($ch);
$httpCsrf = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

verify_case("API create-booking without CSRF token returns HTTP 403 Forbidden", $httpCsrf === 403);

// 2. GET check-availability.php valid query
$ch = curl_init("http://127.0.0.1:8000/api/check-availability.php?check_in=2026-11-10&check_out=2026-11-13&guests=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$availResp = curl_exec($ch);
$availCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$availJson = json_decode(substr($availResp, strpos($availResp, "\r\n\r\n") + 4), true);
verify_case("API check-availability returns HTTP 200 with structured JSON", $availCode === 200 && ($availJson['success'] ?? false) === true);

// 3. GET calculate-pricing.php whitelisted fields
$ch = curl_init("http://127.0.0.1:8000/api/calculate-pricing.php?room_type_id=1&check_in=2026-11-10&check_out=2026-11-13");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$priceResp = curl_exec($ch);
$priceJson = json_decode($priceResp, true);
$priceClean = isset($priceJson['subtotal']) && isset($priceJson['tax_amount']) && !isset($priceJson['room_type']['amenities_json']);
verify_case("API calculate-pricing returns whitelisted financial fields without raw schema dump", $priceClean);

echo "\n=======================================================\n";
$total = $passed + $failed;
echo " REAL-WORLD SCENARIOS SUMMARY: {$passed} / {$total} PASSED\n";
if ($failed > 0) {
    echo " FAILURES DETECTED: {$failed}\n";
} else {
    echo " ALL REAL-WORLD TEST SCENARIOS PASSED PERFECTLY!\n";
}
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
