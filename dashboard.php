<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login($pdo);
$active = 'dashboard';
$pageTitle = match ($user['role']) {
    'admin' => 'Administration dashboard',
    'department' => 'Department overview',
    default => 'Student dashboard',
};

if ($user['role'] === 'student') {
    $countStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                SUM(status IN ('Submitted', 'Assigned', 'In Progress', 'Waiting on Student')) AS open_count,
                SUM(status = 'Resolved' OR status = 'Closed') AS resolved_count
         FROM tickets WHERE student_id = ?"
    );
    $countStatement->execute([$user['id']]);
    $counts = $countStatement->fetch();
    $ticketStatement = $pdo->prepare(
        'SELECT t.id, t.reference_number, t.category, t.subject, t.status, t.created_at,
                d.name AS department_name
         FROM tickets t LEFT JOIN departments d ON d.id = t.department_id
         WHERE t.student_id = ? ORDER BY t.created_at DESC LIMIT 6'
    );
    $ticketStatement->execute([$user['id']]);
    $recentTickets = $ticketStatement->fetchAll();
} else {
    $scope = $user['role'] === 'department' ? ' WHERE department_id = ?' : '';
    $countSql = "SELECT COUNT(*) AS total,
                        SUM(status IN ('Submitted', 'Assigned', 'In Progress', 'Waiting on Student')) AS open_count,
                        SUM(status = 'Submitted') AS new_count,
                        SUM(status = 'Resolved' OR status = 'Closed') AS resolved_count
                 FROM tickets{$scope}";
    $countStatement = $pdo->prepare($countSql);
    $countStatement->execute($user['role'] === 'department' ? [$user['department_id']] : []);
    $counts = $countStatement->fetch();
    $ticketSql = 'SELECT t.id, t.reference_number, t.category, t.subject, t.status, t.created_at,
                         s.full_name AS student_name, d.name AS department_name
                  FROM tickets t
                  JOIN users s ON s.id = t.student_id
                  LEFT JOIN departments d ON d.id = t.department_id'
        . ($user['role'] === 'department' ? ' WHERE t.department_id = ?' : '')
        . ' ORDER BY t.updated_at DESC LIMIT 8';
    $ticketStatement = $pdo->prepare($ticketSql);
    $ticketStatement->execute($user['role'] === 'department' ? [$user['department_id']] : []);
    $recentTickets = $ticketStatement->fetchAll();
}

$total = (int) ($counts['total'] ?? 0);
$open = (int) ($counts['open_count'] ?? 0);
$third = $user['role'] === 'admin' ? (int) ($counts['new_count'] ?? 0) : (int) ($counts['resolved_count'] ?? 0);
$fourth = $user['role'] === 'admin' ? (int) ($counts['resolved_count'] ?? 0) : $total;
require __DIR__ . '/includes/header.php';
?>
<section class="welcome-panel">
    <div>
        <h2><?= $user['role'] === 'student' ? 'Welcome, ' . e($user['full_name']) : ($user['role'] === 'admin' ? 'Support operations at a glance' : 'Your department case queue') ?></h2>
        <p>
            <?php if ($user['role'] === 'student'): ?>
                <?= e($user['department_name'] ?? 'Student') ?> &middot; <?= e($user['matric_number']) ?>. View updates or start a new support request.
            <?php elseif ($user['role'] === 'department'): ?>
                <?= e($user['department_name'] ?? 'Department') ?> &middot; complaints assigned to your department.
            <?php else: ?>
                Review incoming complaints, assign departments and follow resolution progress.
            <?php endif; ?>
        </p>
    </div>
    <span class="welcome-mark"><i class="feather <?= $user['role'] === 'student' ? 'icon-feather' : 'icon-life-buoy' ?>"></i></span>
</section>

<section class="stat-grid" aria-label="Complaint summary">
    <div class="app-card stat-card"><span class="stat-icon"><i class="feather icon-inbox"></i></span><div><strong><?= $total ?></strong><span><?= $user['role'] === 'student' ? 'Total complaints' : 'Complaints in view' ?></span></div></div>
    <div class="app-card stat-card"><span class="stat-icon"><i class="feather icon-activity"></i></span><div><strong><?= $open ?></strong><span>Open complaints</span></div></div>
    <div class="app-card stat-card"><span class="stat-icon"><i class="feather <?= $user['role'] === 'admin' ? 'icon-alert-circle' : 'icon-check-circle' ?>"></i></span><div><strong><?= $third ?></strong><span><?= $user['role'] === 'admin' ? 'Awaiting routing' : 'Resolved complaints' ?></span></div></div>
    <div class="app-card stat-card"><span class="stat-icon"><i class="feather icon-clock"></i></span><div><strong><?= $fourth ?></strong><span><?= $user['role'] === 'admin' ? 'Resolved complaints' : 'All-time requests' ?></span></div></div>
</section>

<section class="app-card">
    <div class="card-heading">
        <div><h2><?= $user['role'] === 'student' ? 'Recent complaints' : 'Latest cases' ?></h2><p><?= $user['role'] === 'student' ? 'The latest activity on your support requests.' : 'Recent complaints requiring attention.' ?></p></div>
        <a class="btn btn-sm btn-outline-primary" href="tickets.php"><?= $user['role'] === 'student' ? 'View my complaints' : 'View all cases' ?></a>
    </div>
    <?php if ($user['role'] === 'student' && $recentTickets === []): ?>
        <div class="empty-state"><i class="feather icon-inbox"></i><strong>No complaints submitted yet</strong><p>When you need help, create a complaint and track it here.</p><a class="btn btn-sm btn-primary" href="new-ticket.php">Submit a complaint</a></div>
    <?php elseif ($recentTickets === []): ?>
        <div class="empty-state"><i class="feather icon-check-circle"></i><strong>No complaints to show</strong><p>New cases will appear here when students submit a complaint.</p></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table app-table">
                <thead><tr><th>Reference</th><th>Complaint</th><?php if ($user['role'] !== 'student'): ?><th>Student</th><?php endif; ?><th>Department</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recentTickets as $ticket): ?>
                    <tr>
                        <td><span class="ticket-ref"><?= e($ticket['reference_number']) ?></span></td>
                        <td><span class="ticket-subject"><?= e($ticket['subject']) ?></span><br><span class="text-muted small"><?= e($ticket['category']) ?></span></td>
                        <?php if ($user['role'] !== 'student'): ?><td><?= e($ticket['student_name']) ?></td><?php endif; ?>
                        <td><?= e($ticket['department_name'] ?? 'Not assigned') ?></td>
                        <td><span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></td>
                        <td><a class="small-link" href="ticket.php?id=<?= (int) $ticket['id'] ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
