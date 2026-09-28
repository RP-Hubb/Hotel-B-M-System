<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Session Termination
 */

require_once __DIR__ . '/includes/auth.php';

logout_user();
session_start();
flash_message('info', 'You have been safely signed out. We hope to see you again soon.');
header('Location: index.php');
exit;
