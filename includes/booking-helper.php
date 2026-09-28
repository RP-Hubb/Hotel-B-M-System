<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Booking Subsystem & Concurrency Logic
 */

require_once __DIR__ . '/functions.php';

/**
 * Validate booking date range
 * Returns [bool $isValid, string $errorMessage, int $totalNights]
 */
function validate_date_range(string $checkIn, string $checkOut): array {
    $today = new DateTime('today');
    $in = DateTime::createFromFormat('Y-m-d', $checkIn);
    $out = DateTime::createFromFormat('Y-m-d', $checkOut);

    if (!$in || !$out) {
        return [false, 'Invalid date format provided. Please use YYYY-MM-DD.', 0];
    }

    // Reset time components
    $in->setTime(0, 0, 0);
    $out->setTime(0, 0, 0);

    if ($in < $today) {
        return [false, 'Check-in date cannot be in the past.', 0];
    }

    if ($out <= $in) {
        return [false, 'Check-out date must be at least one day after check-in.', 0];
    }

    $diff = $in->diff($out);
    $nights = (int)$diff->days;

    if ($nights > 365) {
        return [false, 'Reservations cannot exceed 365 consecutive nights.', 0];
    }

    return [true, '', $nights];
}

/**
 * Calculate pricing for a room type and date range
 */
function calculate_pricing(int $roomTypeId, string $checkIn, string $checkOut): array {
    [$valid, $error, $nights] = validate_date_range($checkIn, $checkOut);
    if (!$valid) {
        return ['success' => false, 'error' => $error];
    }

    $roomType = get_room_type_by_id($roomTypeId);
    if (!$roomType) {
        return ['success' => false, 'error' => 'Selected suite category does not exist.'];
    }

    $pricePerNight = (float)$roomType['price_per_night'];
    $subtotal = $pricePerNight * $nights;
    
    // 18% GST (Standard Indian luxury hospitality tax)
    $taxRate = (float)get_setting('tax_rate_percent', '18.00');
    $taxAmount = round(($subtotal * $taxRate) / 100, 2);
    $totalAmount = round($subtotal + $taxAmount, 2);

    return [
        'success' => true,
        'room_type' => $roomType,
        'nights' => $nights,
        'price_per_night' => $pricePerNight,
        'price_per_night_formatted' => format_inr($pricePerNight),
        'subtotal' => $subtotal,
        'subtotal_formatted' => format_inr($subtotal),
        'tax_rate' => $taxRate,
        'tax_amount' => $taxAmount,
        'tax_amount_formatted' => format_inr($taxAmount),
        'total_amount' => $totalAmount,
        'total_amount_formatted' => format_inr($totalAmount),
    ];
}

/**
 * Get available room types with inventory count for given dates and guests
 */
function get_available_room_types(string $checkIn, string $checkOut, int $guests = 1): array {
    [$valid, $error, $nights] = validate_date_range($checkIn, $checkOut);
    if (!$valid) {
        return [];
    }

    $db = get_db();

    // Query room types that accommodate the guest count and have at least 1 free physical room
    $sql = "
        SELECT rt.*, 
            (
                SELECT COUNT(r.id) 
                FROM rooms r
                WHERE r.room_type_id = rt.id
                  AND r.status = 'available'
                  AND r.id NOT IN (
                      SELECT b.room_id 
                      FROM bookings b 
                      WHERE b.room_id = r.id
                        AND b.status IN ('confirmed', 'checked_in')
                        AND NOT (b.check_out <= :check_in OR b.check_in >= :check_out)
                  )
            ) AS available_rooms_count
        FROM room_types rt
        WHERE rt.is_active = 1
          AND rt.max_guests >= :guests
        ORDER BY rt.sort_order ASC, rt.price_per_night ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':check_in' => $checkIn,
        ':check_out' => $checkOut,
        ':guests' => $guests
    ]);

    $results = $stmt->fetchAll();

    // Add pricing details to each
    foreach ($results as &$rt) {
        $rt['available_rooms_count'] = (int)$rt['available_rooms_count'];
        $rt['price_per_night_formatted'] = format_inr($rt['price_per_night']);
        $pricing = calculate_pricing((int)$rt['id'], $checkIn, $checkOut);
        $rt['total_nights'] = $nights;
        $rt['calculated_total'] = $pricing['total_amount'] ?? 0;
        $rt['calculated_total_formatted'] = $pricing['total_amount_formatted'] ?? '';
    }

    return $results;
}

/**
 * Allocate first unbooked physical room ID for given room type and date range
 * Must be called inside a transaction with lock!
 */
function find_and_lock_available_room(PDO $db, int $roomTypeId, string $checkIn, string $checkOut): ?int {
    $sql = "
        SELECT r.id 
        FROM rooms r
        WHERE r.room_type_id = :room_type_id
          AND r.status = 'available'
          AND r.id NOT IN (
              SELECT b.room_id 
              FROM bookings b 
              WHERE b.room_id = r.id
                AND b.status IN ('confirmed', 'checked_in')
                AND NOT (b.check_out <= :check_in OR b.check_in >= :check_out)
          )
        LIMIT 1
        FOR UPDATE
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':room_type_id' => $roomTypeId,
        ':check_in' => $checkIn,
        ':check_out' => $checkOut
    ]);

    $row = $stmt->fetch();
    return $row ? (int)$row['id'] : null;
}

/**
 * Generate a unique, professional reservation reference code
 * Format: ADI-YYYY-XXXX (e.g., ADI-2026-7842)
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
 * Create a new booking transactionally
 */
function create_booking_reservation(array $data): array {
    $db = get_db();

    $roomTypeId = (int)($data['room_type_id'] ?? 0);
    $checkIn = trim($data['check_in'] ?? '');
    $checkOut = trim($data['check_out'] ?? '');
    $guestsCount = max(1, (int)($data['guests_count'] ?? 1));
    $guestName = trim($data['name'] ?? '');
    $guestEmail = trim($data['email'] ?? '');
    $guestPhone = trim($data['phone'] ?? '');
    $specialRequests = trim($data['special_requests'] ?? '');
    $paymentMethod = $data['payment_method'] ?? 'pay_at_hotel';
    $userId = !empty($data['user_id']) ? (int)$data['user_id'] : null;

    // 1. Validate inputs
    if (empty($guestName) || empty($guestEmail) || empty($guestPhone)) {
        return ['success' => false, 'error' => 'Please provide guest full name, email, and phone number.'];
    }

    if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please provide a valid email address.'];
    }

    [$validDates, $dateError, $totalNights] = validate_date_range($checkIn, $checkOut);
    if (!$validDates) {
        return ['success' => false, 'error' => $dateError];
    }

    $pricing = calculate_pricing($roomTypeId, $checkIn, $checkOut);
    if (!$pricing['success']) {
        return ['success' => false, 'error' => $pricing['error']];
    }

    $roomType = $pricing['room_type'];
    if ($guestsCount > (int)$roomType['max_guests']) {
        return ['success' => false, 'error' => "This suite accommodates up to {$roomType['max_guests']} guests."];
    }

    // 2. Begin Atomic Database Transaction
    $db->beginTransaction();

    try {
        // Lock and find physical room
        $physicalRoomId = find_and_lock_available_room($db, $roomTypeId, $checkIn, $checkOut);
        if (!$physicalRoomId) {
            $db->rollBack();
            return ['success' => false, 'error' => 'Unfortunately, this suite category was just reserved by another guest for these dates. Please choose another date or category.'];
        }

        // Generate unique reference
        do {
            $reference = generate_booking_reference();
            $stmtCheck = $db->prepare("SELECT id FROM bookings WHERE booking_reference = :ref LIMIT 1");
            $stmtCheck->execute([':ref' => $reference]);
        } while ($stmtCheck->fetch());

        // Insert booking record
        $stmtBooking = $db->prepare("
            INSERT INTO bookings (
                booking_reference, user_id, room_id, room_type_id, 
                check_in, check_out, total_nights, guests_count, 
                price_per_night, subtotal_amount, tax_amount, total_amount, 
                special_requests, status
            ) VALUES (
                :reference, :user_id, :room_id, :room_type_id, 
                :check_in, :check_out, :total_nights, :guests_count, 
                :price_per_night, :subtotal, :tax_amount, :total_amount, 
                :special_requests, 'confirmed'
            )
        ");

        $stmtBooking->execute([
            ':reference' => $reference,
            ':user_id' => $userId,
            ':room_id' => $physicalRoomId,
            ':room_type_id' => $roomTypeId,
            ':check_in' => $checkIn,
            ':check_out' => $checkOut,
            ':total_nights' => $totalNights,
            ':guests_count' => $guestsCount,
            ':price_per_night' => $pricing['price_per_night'],
            ':subtotal' => $pricing['subtotal'],
            ':tax_amount' => $pricing['tax_amount'],
            ':total_amount' => $pricing['total_amount'],
            ':special_requests' => $specialRequests
        ]);

        $bookingId = (int)$db->lastInsertId();

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

        // Commit transaction!
        $db->commit();

        return [
            'success' => true,
            'booking_id' => $bookingId,
            'booking_reference' => $reference,
            'total_amount' => $pricing['total_amount'],
            'total_amount_formatted' => $pricing['total_amount_formatted'],
            'suite_name' => $roomType['name']
        ];

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Booking transaction failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'A reservation processing error occurred. Please try again.'];
    }
}
