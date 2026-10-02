<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Hardened Booking Subsystem, Concurrency Row Locks, and Reservation Service
 */

require_once __DIR__ . '/functions.php';

/**
 * Strict date range validation module (WP2.3)
 * Rejects rollover dates (e.g. 2027-02-30), validates timezone (Asia/Kolkata), advance days, and maximum stay.
 *
 * @param string $checkIn  YYYY-MM-DD
 * @param string $checkOut YYYY-MM-DD
 * @return array [bool $isValid, string $errorMessage, int $nights, DateTime $inDate, DateTime $outDate]
 */
function validate_date_range(string $checkIn, string $checkOut): array {
    $tz = new DateTimeZone('Asia/Kolkata');
    $today = new DateTime('today', $tz);

    $in = DateTime::createFromFormat('!Y-m-d', trim($checkIn), $tz);
    $out = DateTime::createFromFormat('!Y-m-d', trim($checkOut), $tz);

    // Reject format failure or date rollover (e.g. Feb 30 becoming Mar 2)
    if (!$in || $in->format('Y-m-d') !== trim($checkIn)) {
        return [false, 'Invalid check-in date provided. Please enter a valid date in YYYY-MM-DD format.', 0, null, null];
    }
    if (!$out || $out->format('Y-m-d') !== trim($checkOut)) {
        return [false, 'Invalid check-out date provided. Please enter a valid date in YYYY-MM-DD format.', 0, null, null];
    }

    if ($in < $today) {
        return [false, 'Check-in date cannot be in the past.', 0, $in, $out];
    }

    if ($out <= $in) {
        return [false, 'Check-out date must be at least one night after check-in.', 0, $in, $out];
    }

    $diff = $in->diff($out);
    $nights = (int)$diff->days;

    $maxAdvance = (int)get_setting('max_advance_days', '365');
    $advanceDiff = $today->diff($in);
    if ((int)$advanceDiff->days > $maxAdvance) {
        return [false, "Reservations can only be made up to {$maxAdvance} days in advance.", 0, $in, $out];
    }

    $maxStay = (int)get_setting('max_stay_nights', '30');
    if ($nights > $maxStay) {
        return [false, "Online reservations are limited to a maximum of {$maxStay} consecutive nights. For extended stays, please contact our concierge directly.", 0, $in, $out];
    }

    return [true, '', $nights, $in, $out];
}

/**
 * Calculate pricing and GST with integer paise precision and legal tax slab rules (WP2.5)
 * Per-night supply value:
 *   <= ₹7,500 (configurable) -> 5% GST (2.5% CGST + 2.5% SGST)
 *   >  ₹7,500                -> 18% GST (9% CGST + 9% SGST)
 *
 * @param int $roomTypeId
 * @param string $checkIn
 * @param string $checkOut
 * @return array
 */
function calculate_pricing(int $roomTypeId, string $checkIn, string $checkOut): array {
    [$valid, $error, $nights, $inDate, $outDate] = validate_date_range($checkIn, $checkOut);
    if (!$valid) {
        return ['success' => false, 'error' => $error];
    }

    $roomType = get_room_type_by_id($roomTypeId);
    if (!$roomType) {
        return ['success' => false, 'error' => 'The selected suite category was not found.'];
    }

    $pricePerNightRupees = (float)$roomType['price_per_night'];
    $pricePerNightPaise = (int)round($pricePerNightRupees * 100);
    $subtotalPaise = $pricePerNightPaise * $nights;

    // GST Slab Configuration (Notification 22 Sep 2025)
    $thresholdRupees = (float)get_setting('tax_slab_threshold', '7500.00');
    $thresholdPaise = (int)round($thresholdRupees * 100);
    $lowRate = (float)get_setting('tax_low_rate', '5.00');
    $highRate = (float)get_setting('tax_high_rate', '18.00');
    $operator = get_setting('tax_threshold_operator', 'lte');

    $isLowerSlab = ($operator === 'lt')
        ? ($pricePerNightPaise < $thresholdPaise)
        : ($pricePerNightPaise <= $thresholdPaise);

    $effectiveTaxRate = $isLowerSlab ? $lowRate : $highRate;
    $cgstRate = $effectiveTaxRate / 2;
    $sgstRate = $effectiveTaxRate / 2;

    // Calculate tax in integer paise
    $taxPaise = (int)round(($subtotalPaise * $effectiveTaxRate) / 100);
    $cgstPaise = (int)round($taxPaise / 2);
    $sgstPaise = $taxPaise - $cgstPaise;
    $totalPaise = $subtotalPaise + $taxPaise;

    $subtotal = $subtotalPaise / 100;
    $taxAmount = $taxPaise / 100;
    $cgstAmount = $cgstPaise / 100;
    $sgstAmount = $sgstPaise / 100;
    $totalAmount = $totalPaise / 100;

    return [
        'success' => true,
        'room_type' => [
            'id' => (int)$roomType['id'],
            'name' => $roomType['name'],
            'slug' => $roomType['slug'],
            'max_guests' => (int)$roomType['max_guests'],
            'view_type' => $roomType['view_type'],
            'bed_type' => $roomType['bed_type'],
            'featured_image' => $roomType['featured_image'],
            'short_description' => $roomType['short_description'],
            'price_per_night' => $pricePerNightRupees
        ],
        'nights' => $nights,
        'price_per_night' => $pricePerNightRupees,
        'price_per_night_formatted' => format_inr($pricePerNightRupees),
        'subtotal' => $subtotal,
        'subtotal_formatted' => format_inr($subtotal),
        'tax_rate' => $effectiveTaxRate,
        'cgst_rate' => $cgstRate,
        'sgst_rate' => $sgstRate,
        'cgst_amount' => $cgstAmount,
        'cgst_amount_formatted' => format_inr($cgstAmount),
        'sgst_amount' => $sgstAmount,
        'sgst_amount_formatted' => format_inr($sgstAmount),
        'tax_amount' => $taxAmount,
        'tax_amount_formatted' => format_inr($taxAmount),
        'total_amount' => $totalAmount,
        'total_amount_formatted' => format_inr($totalAmount),
        'currency' => 'INR',
    ];
}

/**
 * Get available room types for given dates and guests count
 * Operational status of rooms TODAY does not block FUTURE availability (WP1.6)
 *
 * @param string $checkIn
 * @param string $checkOut
 * @param int $guests
 * @return array
 */
function get_available_room_types(string $checkIn, string $checkOut, int $guests = 1): array {
    [$valid, $error, $nights] = validate_date_range($checkIn, $checkOut);
    if (!$valid) {
        return [];
    }

    $db = get_db();

    // Query room types where available inventory > 0
    // Uses NOT EXISTS instead of NOT IN, and considers room_blocks table
    // Distinct parameter names required for PDO when EMULATE_PREPARES is false
    $sql = "
        SELECT rt.*, 
            (
                SELECT COUNT(r.id) 
                FROM rooms r
                WHERE r.room_type_id = rt.id
                  AND NOT EXISTS (
                      SELECT 1 FROM room_blocks rb
                      WHERE rb.room_id = r.id
                        AND NOT (rb.end_date <= :cin_block OR rb.start_date >= :cout_block)
                  )
                  AND NOT EXISTS (
                      SELECT 1 FROM bookings b
                      WHERE b.room_id = r.id
                        AND (
                            b.status IN ('confirmed', 'checked_in') 
                            OR (b.status = 'pending' AND b.hold_expires_at > NOW())
                        )
                        AND NOT (b.check_out <= :cin_book OR b.check_in >= :cout_book)
                  )
            ) AS available_rooms_count
        FROM room_types rt
        WHERE rt.is_active = 1
          AND rt.max_guests >= :guests
        ORDER BY rt.sort_order ASC, rt.price_per_night ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':cin_block' => $checkIn,
        ':cout_block' => $checkOut,
        ':cin_book' => $checkIn,
        ':cout_book' => $checkOut,
        ':guests' => max(1, $guests)
    ]);

    $results = $stmt->fetchAll();

    foreach ($results as &$rt) {
        $pricing = calculate_pricing((int)$rt['id'], $checkIn, $checkOut);
        $rt['pricing'] = $pricing;
        $rt['calculated_total'] = $pricing['total_amount'] ?? 0;
        $rt['calculated_total_formatted'] = $pricing['total_amount_formatted'] ?? '';
    }

    return $results;
}

/**
 * Allocate and lock available physical room deterministically (WP1.6)
 * Must be executed within an active database transaction.
 *
 * @param PDO $db
 * @param int $roomTypeId
 * @param string $checkIn
 * @param string $checkOut
 * @return int|null Physical room id or null if none available
 */
function find_and_lock_available_room(PDO $db, int $roomTypeId, string $checkIn, string $checkOut): ?int {
    $sql = "
        SELECT r.id 
        FROM rooms r
        WHERE r.room_type_id = :room_type_id
          AND NOT EXISTS (
              SELECT 1 FROM room_blocks rb
              WHERE rb.room_id = r.id
                AND NOT (rb.end_date <= :cin_block OR rb.start_date >= :cout_block)
          )
          AND NOT EXISTS (
              SELECT 1 FROM bookings b
              WHERE b.room_id = r.id
                AND (
                    b.status IN ('confirmed', 'checked_in')
                    OR (b.status = 'pending' AND b.hold_expires_at > NOW())
                )
                AND NOT (b.check_out <= :cin_book OR b.check_in >= :cout_book)
          )
        ORDER BY r.id ASC
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':room_type_id' => $roomTypeId,
        ':cin_block' => $checkIn,
        ':cout_block' => $checkOut,
        ':cin_book' => $checkIn,
        ':cout_book' => $checkOut
    ]);

    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

/**
 * Generate a unique reservation reference code
 * Format: ADI-YYYY-XXXX (e.g. ADI-2026-7842)
 */
function generate_booking_reference(): string {
    $year = date('Y');
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 4; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return "ADI-{$year}-{$code}";
}

/**
 * Create a new booking reservation atomically (WP1.4, WP2.1, WP2.2, WP2.3, WP2.6)
 *
 * @param array $data Raw input array
 * @param int|null $authUserId Authenticated user ID (never read from input!)
 * @return array
 */
function create_booking_reservation(array $data, ?int $authUserId = null): array {
    $db = get_db();

    // 1. Allow-list accepted keys only (WP1.4)
    $allowedKeys = [
        'room_type_id', 'check_in', 'check_out', 'guests_count',
        'name', 'email', 'phone', 'special_requests',
        'payment_method', 'idempotency_key'
    ];
    $clean = [];
    foreach ($allowedKeys as $key) {
        $clean[$key] = $data[$key] ?? null;
    }

    // 2. Idempotency Check (WP2.2)
    $idempotencyKey = !empty($clean['idempotency_key']) ? trim((string)$clean['idempotency_key']) : null;
    if ($idempotencyKey !== null && strlen($idempotencyKey) > 0) {
        $stmtIdem = $db->prepare("
            SELECT b.id, b.booking_reference, b.total_amount, b.status, b.access_token, rt.name AS suite_name
            FROM bookings b
            JOIN room_types rt ON rt.id = b.room_type_id
            WHERE b.idempotency_key = :idem LIMIT 1
        ");
        $stmtIdem->execute([':idem' => $idempotencyKey]);
        $existing = $stmtIdem->fetch();
        if ($existing) {
            return [
                'success' => true,
                'booking_id' => (int)$existing['id'],
                'booking_reference' => $existing['booking_reference'],
                'access_token' => $existing['access_token'],
                'total_amount' => (float)$existing['total_amount'],
                'total_amount_formatted' => format_inr($existing['total_amount']),
                'suite_name' => $existing['suite_name'],
                'idempotent_replay' => true
            ];
        }
    }

    // 3. Strict Input Validation (WP2.3)
    $guestName = trim((string)($clean['name'] ?? ''));
    $guestEmail = strtolower(trim((string)($clean['email'] ?? '')));
    $guestPhone = trim((string)($clean['phone'] ?? ''));
    $specialRequests = trim((string)($clean['special_requests'] ?? ''));
    $paymentMethod = trim((string)($clean['payment_method'] ?? 'pay_at_hotel'));
    $roomTypeId = (int)($clean['room_type_id'] ?? 0);
    $checkIn = trim((string)($clean['check_in'] ?? ''));
    $checkOut = trim((string)($clean['check_out'] ?? ''));

    // Reject guests < 1 explicitly instead of silently coercing (WP2.3)
    $rawGuests = $clean['guests_count'];
    if ($rawGuests === null || (int)$rawGuests < 1) {
        return ['success' => false, 'error' => 'Guest count must be at least 1 person.'];
    }
    $guestsCount = (int)$rawGuests;

    if (mb_strlen($guestName) < 2 || mb_strlen($guestName) > 120) {
        return ['success' => false, 'error' => 'Please provide a valid full name (between 2 and 120 characters).'];
    }

    if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL) || strlen($guestEmail) > 150) {
        return ['success' => false, 'error' => 'Please provide a valid email address (up to 150 characters).'];
    }

    // Phone: 7 to 15 digits allowing optional leading '+'
    $cleanPhoneDigits = preg_replace('/[^\d]/', '', $guestPhone);
    if (strlen($cleanPhoneDigits) < 7 || strlen($cleanPhoneDigits) > 15) {
        return ['success' => false, 'error' => 'Please provide a valid contact phone number with 7 to 15 digits.'];
    }

    if (mb_strlen($specialRequests) > 1000) {
        return ['success' => false, 'error' => 'Special requests cannot exceed 1000 characters.'];
    }

    $allowedPaymentMethods = ['pay_at_hotel', 'upi', 'credit_card', 'netbanking', 'cash'];
    if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
        return ['success' => false, 'error' => 'Invalid payment method selected.'];
    }

    // Anti-Abuse Rate Limit: 5 bookings per hour per IP (WP2.6)
    if (!check_rate_limit('booking_create', 5, 3600)) {
        return ['success' => false, 'error' => 'Too many booking attempts from this network. Please wait a while before trying again.'];
    }

    // Check active future bookings per email (max 3 allowed)
    $stmtFuture = $db->prepare("
        SELECT COUNT(*) FROM bookings b
        JOIN booking_guests bg ON bg.booking_id = b.id
        WHERE bg.email = :email
          AND b.status IN ('confirmed', 'pending')
          AND b.check_out >= CURDATE()
    ");
    $stmtFuture->execute([':email' => $guestEmail]);
    if ((int)$stmtFuture->fetchColumn() >= 3) {
        return ['success' => false, 'error' => 'Maximum active online reservations limit reached for this email address. Please contact concierge.'];
    }

    // Date validation
    [$validDates, $dateError, $totalNights, $inDate, $outDate] = validate_date_range($checkIn, $checkOut);
    if (!$validDates) {
        return ['success' => false, 'error' => $dateError];
    }

    $pricing = calculate_pricing($roomTypeId, $checkIn, $checkOut);
    if (!$pricing['success']) {
        return ['success' => false, 'error' => $pricing['error']];
    }

    $roomType = $pricing['room_type'];
    if ($guestsCount > (int)$roomType['max_guests']) {
        return ['success' => false, 'error' => "This suite category accommodates up to {$roomType['max_guests']} guests."];
    }

    // Generate 128-bit secure access token (WP2.7)
    $accessToken = bin2hex(random_bytes(16));

    // 4. Begin Atomic Database Transaction
    $db->beginTransaction();

    try {
        // Lock and find physical room deterministically
        $physicalRoomId = find_and_lock_available_room($db, $roomTypeId, $checkIn, $checkOut);
        if (!$physicalRoomId) {
            $db->rollBack();
            return [
                'success' => false, 
                'error' => 'Unfortunately, this suite category is no longer available for the requested dates. Please choose another category or date.'
            ];
        }

        // Generate unique booking reference
        do {
            $reference = generate_booking_reference();
            $stmtCheck = $db->prepare("SELECT id FROM bookings WHERE booking_reference = :ref LIMIT 1");
            $stmtCheck->execute([':ref' => $reference]);
        } while ($stmtCheck->fetch());

        // Insert master booking
        $stmtBooking = $db->prepare("
            INSERT INTO bookings (
                booking_reference, idempotency_key, access_token, user_id, room_id, room_type_id, 
                check_in, check_out, total_nights, guests_count, 
                price_per_night, subtotal_amount, tax_rate, tax_amount, total_amount, 
                currency, special_requests, status
            ) VALUES (
                :reference, :idempotency_key, :access_token, :user_id, :room_id, :room_type_id, 
                :check_in, :check_out, :total_nights, :guests_count, 
                :price_per_night, :subtotal, :tax_rate, :tax_amount, :total_amount, 
                'INR', :special_requests, 'confirmed'
            )
        ");

        $stmtBooking->execute([
            ':reference' => $reference,
            ':idempotency_key' => $idempotencyKey,
            ':access_token' => $accessToken,
            ':user_id' => $authUserId, // Enforces authUserId only (WP1.4)
            ':room_id' => $physicalRoomId,
            ':room_type_id' => $roomTypeId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut,
            ':total_nights' => $totalNights,
            ':guests_count' => $guestsCount,
            ':price_per_night' => $pricing['price_per_night'],
            ':subtotal' => $pricing['subtotal'],
            ':tax_rate' => $pricing['tax_rate'],
            ':tax_amount' => $pricing['tax_amount'],
            ':total_amount' => $pricing['total_amount'],
            ':special_requests' => $specialRequests
        ]);

        $bookingId = (int)$db->lastInsertId();

        // 5. DB-Level Double-Booking Backstop: Write to booking_nights (WP2.1)
        // With UNIQUE(room_id, stay_date), duplicate booking on any night will raise SQLSTATE 23000
        $stmtNight = $db->prepare("INSERT INTO booking_nights (booking_id, room_id, stay_date) VALUES (:bid, :rid, :stay_date)");
        
        $currentDate = clone $inDate;
        while ($currentDate < $outDate) {
            $stayDateStr = $currentDate->format('Y-m-d');
            $stmtNight->execute([
                ':bid' => $bookingId,
                ':rid' => $physicalRoomId,
                ':stay_date' => $stayDateStr
            ]);
            $currentDate->modify('+1 day');
        }

        // Insert primary guest details
        $stmtGuest = $db->prepare("
            INSERT INTO booking_guests (booking_id, name, email, phone, is_primary) 
            VALUES (:booking_id, :name, :email, :phone, 1)
        ");
        $stmtGuest->execute([
            ':booking_id' => $bookingId,
            ':name' => $guestName,
            ':email' => $guestEmail,
            ':phone' => $guestPhone
        ]);

        // Insert payment ledger record
        $stmtPayment = $db->prepare("
            INSERT INTO payments (booking_id, amount, currency, payment_method, payment_status) 
            VALUES (:booking_id, :amount, 'INR', :method, 'pending')
        ");
        $stmtPayment->execute([
            ':booking_id' => $bookingId,
            ':amount' => $pricing['total_amount'],
            ':method' => $paymentMethod
        ]);

        // Record initial status event (WP2.8)
        $stmtEvent = $db->prepare("
            INSERT INTO booking_events (booking_id, from_status, to_status, actor, note) 
            VALUES (:bid, NULL, 'confirmed', 'guest_checkout', 'Reservation created online')
        ");
        $stmtEvent->execute([':bid' => $bookingId]);

        // Commit transaction!
        $db->commit();

        return [
            'success' => true,
            'booking_id' => $bookingId,
            'booking_reference' => $reference,
            'access_token' => $accessToken,
            'total_amount' => $pricing['total_amount'],
            'total_amount_formatted' => $pricing['total_amount_formatted'],
            'suite_name' => $roomType['name']
        ];

    } catch (PDOException $e) {
        $db->rollBack();
        // Check for SQLSTATE 23000 or error 1062 (Duplicate entry on booking_nights)
        if ($e->getCode() === '23000' || strpos($e->getMessage(), '1062') !== false) {
            error_log("Concurrency duplicate blocked by booking_nights backstop: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Unfortunately, this suite category was just reserved by another guest for these dates. Please choose another date or category.'
            ];
        }
        error_log("Booking transaction PDO error: " . $e->getMessage());
        return ['success' => false, 'error' => 'A reservation processing error occurred. Please try again.'];
    } catch (Throwable $e) {
        $db->rollBack();
        error_log("Booking transaction error: " . $e->getMessage());
        return ['success' => false, 'error' => 'A reservation processing error occurred. Please try again.'];
    }
}

/**
 * State machine for booking status transitions (WP2.8)
 *
 * @param int $bookingId
 * @param string $toStatus
 * @param string $actor
 * @param string|null $reason
 * @return array ['success' => bool, 'error' => string|null]
 */
function transition_booking_status(int $bookingId, string $toStatus, string $actor = 'system', ?string $reason = null): array {
    $db = get_db();

    $validTransitions = [
        'pending' => ['confirmed', 'expired', 'cancelled'],
        'confirmed' => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['checked_out'],
        'cancelled' => [],
        'checked_out' => [],
        'no_show' => [],
        'expired' => []
    ];

    $db->beginTransaction();
    try {
        $stmt = $db->prepare("SELECT id, status, room_id FROM bookings WHERE id = :id FOR UPDATE");
        $stmt->execute([':id' => $bookingId]);
        $booking = $stmt->fetch();

        if (!$booking) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Reservation not found.'];
        }

        $currentStatus = $booking['status'];
        $allowed = $validTransitions[$currentStatus] ?? [];

        if (!in_array($toStatus, $allowed, true)) {
            $db->rollBack();
            return ['success' => false, 'error' => "Cannot transition status from '{$currentStatus}' to '{$toStatus}'."];
        }

        // Update status on booking
        $update = $db->prepare("UPDATE bookings SET status = :st, cancellation_reason = :reason, updated_at = NOW() WHERE id = :id");
        $update->execute([
            ':st' => $toStatus,
            ':reason' => $reason,
            ':id' => $bookingId
        ]);

        // If cancelled or expired, release inventory backstop from booking_nights
        if (in_array($toStatus, ['cancelled', 'expired'], true)) {
            $delNights = $db->prepare("DELETE FROM booking_nights WHERE booking_id = :bid");
            $delNights->execute([':bid' => $bookingId]);
        }

        // Record in audit log
        $log = $db->prepare("INSERT INTO booking_events (booking_id, from_status, to_status, actor, note) VALUES (:bid, :from_s, :to_s, :actor, :note)");
        $log->execute([
            ':bid' => $bookingId,
            ':from_s' => $currentStatus,
            ':to_s' => $toStatus,
            ':actor' => $actor,
            ':note' => $reason
        ]);

        $db->commit();
        return ['success' => true, 'error' => null];
    } catch (Throwable $e) {
        $db->rollBack();
        error_log("transition_booking_status error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to update booking status.'];
    }
}
