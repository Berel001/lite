<?php
$flash = take_flash();
$roleLabel = match ($user['role'] ?? 'guest') {
    'admin' => 'Administration',
    'department' => 'Department desk',
    'student' => 'Student portal',
    default => 'Support portal',
};
?>
<!DOCTYPE html>
<html lang="en" class="material-style">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Student support and complaint tracking for the Federal College of Wildlife Management.">
    <title><?= e($pageTitle) ?> | FCWM Student Support</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/fonts/fontawesome.css">
    <link rel="stylesheet" href="assets/fonts/feather.css">
    <link rel="stylesheet" href="assets/css/bootstrap-material.css">
    <link rel="stylesheet" href="assets/css/shreerang-material.css">
    <link rel="stylesheet" href="assets/css/uikit.css">
    <link rel="stylesheet" href="assets/css/support.css">
</head>
<body class="role-<?= e($user['role'] ?? 'guest') ?>">
<div class="app-shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="app-main">
        <header class="app-header">
            <button type="button" class="mobile-menu" aria-label="Toggle navigation" data-menu-toggle><i class="feather icon-menu"></i></button>
            <div class="header-heading">
                <span class="eyebrow"><?= e($roleLabel) ?></span>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="header-account">
                <span class="account-avatar"><?= e(strtoupper(substr($user['full_name'] ?? 'G', 0, 1))) ?></span>
                <div>
                    <strong><?= e($user['full_name'] ?? 'Guest') ?></strong>
                    <small><?= e(ucfirst($user['role'] ?? 'visitor')) ?></small>
                </div>
                <?php if (!empty($user)): ?>
                    <a class="header-logout" href="logout.php" aria-label="Sign out"><i class="feather icon-log-out"></i></a>
                <?php endif; ?>
            </div>
        </header>
        <main class="app-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> app-alert" role="alert"><?= e($flash['message']) ?></div>
            <?php endif; ?>
