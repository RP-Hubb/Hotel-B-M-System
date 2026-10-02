<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    $db = get_db();
    $newAddress = 'G-59, Connaught Circus, Connaught Place, New Delhi, Delhi 110001';
    
    $stmt = $db->prepare("
        INSERT INTO site_settings (setting_key, setting_value, setting_group, description, updated_at)
        VALUES ('hotel_address', :address, 'general', 'Physical address', NOW())
        ON DUPLICATE KEY UPDATE setting_value = :address_update, updated_at = NOW()
    ");
    $stmt->execute([
        ':address' => $newAddress,
        ':address_update' => $newAddress
    ]);
    
    echo "SUCCESS: hotel_address updated to: " . $newAddress . "\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
