<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Admin Logout
 */

require_once __DIR__ . '/../includes/auth.php';

logout_user();
session_start();
flash_message('info', 'Staff session safely closed.');
header('Location: login.php');
exit;
