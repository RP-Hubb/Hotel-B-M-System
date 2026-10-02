<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Comprehensive Page & Endpoint Health Check
 */

declare(strict_types=1);

$baseUrl = 'http://127.0.0.1:8000';

$publicPages = [
    '/' => 'Homepage',
    '/rooms.php' => 'Suites & Residences Catalog',
    '/room-details.php?slug=deluxe-verandah-suite' => 'Deluxe Verandah Suite Details',
    '/room-details.php?slug=imperial-heritage-suite' => 'Imperial Heritage Suite Details',
    '/room-details.php?slug=presidential-residence' => 'Presidential Residence Details',
    '/booking.php' => 'Multi-step Booking Wizard',
    '/dining.php' => 'Aura Fine Dining & Peacock Bar',
    '/experiences.php' => 'Courtyard Pool & Spa Experiences',
    '/gallery.php' => 'Architectural Gallery',
    '/about.php' => 'Heritage & Architectural Story',
    '/contact.php' => 'Concierge Inquiries & Map',
    '/login.php' => 'Guest Sign-In Portal',
    '/register.php' => 'Guest Registration',
    '/admin/login.php' => 'Staff Login Portal'
];

$errorsFound = 0;
echo "=======================================================\n";
echo " ADISHIV — COMPREHENSIVE ENDPOINT AUDIT\n";
echo "=======================================================\n\n";

foreach ($publicPages as $path => $name) {
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    $hasPhpError = preg_match('/(Fatal error|Parse error|Warning:|Notice:|Deprecated:)/i', $body, $matches);

    if ($httpCode === 200 && !$hasPhpError) {
        echo "  [OK] HTTP {$httpCode} - {$name} ({$path})\n";
    } else {
        $errorsFound++;
        echo "  [FAIL] HTTP {$httpCode} - {$name} ({$path})\n";
        if ($hasPhpError) {
            echo "         PHP Error detected: {$matches[0]}\n";
            $lines = explode("\n", strip_tags($body));
            foreach ($lines as $line) {
                if (stripos($line, 'error') !== false || stripos($line, 'warning') !== false) {
                    echo "         > " . trim($line) . "\n";
                }
            }
        }
    }
}

echo "\n=======================================================\n";
echo " SUMMARY: " . (count($publicPages) - $errorsFound) . "/" . count($publicPages) . " Pages Clean\n";
echo "=======================================================\n";

exit($errorsFound > 0 ? 1 : 0);
