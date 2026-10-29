<?php
$role = $user['role'] ?? 'guest';
$navigation = match ($role) {
    'admin' => [
        ['dashboard.php', 'Dashboard', 'icon-grid', 'dashboard'],
        ['tickets.php', 'All complaints', 'icon-inbox', 'tickets'],
        ['manage.php', 'Departments & staff', 'icon-users', 'manage'],
        ['track.php', 'Track a complaint', 'icon-search', 'track'],
    ],
    'department' => [
        ['dashboard.php', 'Department overview', 'icon-grid', 'dashboard'],
        ['tickets.php', 'Assigned complaints', 'icon-inbox', 'tickets'],
        ['track.php', 'Track a complaint', 'icon-search', 'track'],
    ],
    'student' => [
        ['dashboard.php', 'My dashboard', 'icon-grid', 'dashboard'],
        ['new-ticket.php', 'Submit a complaint', 'icon-plus-circle', 'new-ticket'],
        ['tickets.php', 'My complaints', 'icon-inbox', 'tickets'],
        ['track.php', 'Track by reference', 'icon-search', 'track'],
    ],
    default => [
        ['login.php', 'Sign in', 'icon-log-in', 'login'],
        ['register.php', 'Create student account', 'icon-user-plus', 'register'],
        ['track.php', 'Track a complaint', 'icon-search', 'track'],
    ],
};
?>
<aside class="app-sidebar">
    <a class="brand-lockup" href="<?= $role === 'guest' ? 'login.php' : 'dashboard.php' ?>">
        <span class="brand-mark"><i class="feather icon-life-buoy"></i></span>
        <span class="brand-copy"><strong>FCWM</strong><small>Student Support</small></span>
    </a>
    <div class="sidebar-caption"><?= $role === 'admin' ? 'WORKSPACE' : ($role === 'department' ? 'SERVICE DESK' : 'STUDENT SERVICES') ?></div>
    <nav class="sidebar-nav" aria-label="Main navigation">
        <?php foreach ($navigation as [$href, $label, $icon, $key]): ?>
            <a class="sidebar-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($href) ?>">
                <i class="feather <?= e($icon) ?>"></i><span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom">
        <div class="support-note">
            <span class="support-note-icon"><i class="feather icon-message-circle"></i></span>
            <strong><?= $role === 'department' ? 'Department helpdesk' : ($role === 'admin' ? 'Support operations' : 'Need assistance?') ?></strong>
            <p><?= $role === 'student' || $role === 'guest' ? 'Submit a complaint and follow every update with your reference number.' : 'Keep student complaints moving with clear updates and timely routing.' ?></p>
        </div>
        <div class="sidebar-footer">
            <span>Federal College of</span><strong>Wildlife Management</strong>
        </div>
    </div>
</aside>
