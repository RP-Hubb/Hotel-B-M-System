<?php
/**
 * Adishiv Luxury Hotel & Suites
 * Admin Panel Header
 */

require_once __DIR__ . '/../includes/auth.php';

// Protect admin access
require_admin();

$adminNav = $adminNav ?? 'dashboard';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($adminTitle ?? 'Admin Dashboard') ?> | Adishiv Luxury Hotel</title>
  
  <link rel="stylesheet" href="<?= asset_url('assets/css/variables.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/base.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/components.css') ?>">
  <link rel="stylesheet" href="<?= asset_url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">

<header class="admin-header">
  <div class="container">
    <div class="admin-brand">
      <a href="<?= asset_url('admin/index.php') ?>" class="brand-logo" style="text-align: left; align-items: flex-start;">
        <span class="brand-title" style="font-size: 1.3rem;">Adishiv</span>
      </a>
      <span class="admin-badge">Imperial Admin</span>
    </div>

    <nav class="admin-nav">
      <a href="<?= asset_url('admin/index.php') ?>" class="<?= $adminNav === 'dashboard' ? 'active' : '' ?>">Overview</a>
      <a href="<?= asset_url('admin/bookings.php') ?>" class="<?= $adminNav === 'bookings' ? 'active' : '' ?>">Bookings</a>
      <a href="<?= asset_url('admin/rooms.php') ?>" class="<?= $adminNav === 'rooms' ? 'active' : '' ?>">Rooms & Rates</a>
      <a href="<?= asset_url('admin/customers.php') ?>" class="<?= $adminNav === 'customers' ? 'active' : '' ?>">Residents</a>
      <a href="<?= asset_url('admin/messages.php') ?>" class="<?= $adminNav === 'messages' ? 'active' : '' ?>">Inquiries</a>
      <a href="<?= asset_url('admin/settings.php') ?>" class="<?= $adminNav === 'settings' ? 'active' : '' ?>">Hotel Config</a>
    </nav>

    <div style="display: flex; align-items: center; gap: 1rem; font-size: 0.82rem;">
      <a href="<?= asset_url('index.php') ?>" target="_blank" style="color: var(--color-gold); text-decoration: underline;">
        Public Site ↗
      </a>
      <span style="color: rgba(255,255,255,0.4);">|</span>
      <span style="color: #fff; font-weight: 500;"><?= e($user['name']) ?></span>
      <a href="<?= asset_url('admin/logout.php') ?>" style="color: #F87171; text-decoration: none;">Sign Out</a>
    </div>
  </div>
</header>

<main class="admin-main">
  <div class="container">
    <?php
    $flashes = get_flash_messages();
    if (!empty($flashes)): ?>
      <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
          <?= e($flash['message']) ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
