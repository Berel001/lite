<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login($pdo);
$active = 'tickets';
$pageTitle = $user['role'] === 'student' ? 'My complaints' : ($user['role'] === 'admin' ? 'All complaints' : 'Assigned complaints');

$sql = 'SELECT t.id, t.reference_number, t.category, t.subject, t.status, t.created_at,
               s.full_name AS student_name, s.matric_number, d.name AS department_name
        FROM tickets t
        JOIN users s ON s.id = t.student_id
        LEFT JOIN departments d ON d.id = t.department_id';
$parameters = [];
if ($user['role'] === 'student') {
    $sql .= ' WHERE t.student_id = ?';
    $parameters[] = $user['id'];
} elseif ($user['role'] === 'department') {
    $sql .= ' WHERE t.department_id = ?';
    $parameters[] = $user['department_id'];
}
$sql .= ' ORDER BY t.updated_at DESC';
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$tickets = $statement->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<section class="app-card">
    <div class="card-heading">
        <div><h2><?= $user['role'] === 'student' ? 'Your support requests' : 'Complaint register' ?></h2><p><?= $user['role'] === 'student' ? 'Every complaint has a unique reference number for tracking.' : 'Open a case to add updates, change its status or route it.' ?></p></div>
        <?php if ($user['role'] === 'student'): ?><a href="new-ticket.php" class="btn btn-primary btn-sm"><i class="feather icon-plus mr-1"></i>New complaint</a><?php endif; ?>
    </div>
    <?php if ($tickets === []): ?>
        <div class="empty-state"><i class="feather icon-inbox"></i><strong>No complaints found</strong><p><?= $user['role'] === 'department' ? 'Complaints routed to your department will appear here.' : 'Your submitted complaints will appear here.' ?></p><?php if ($user['role'] === 'student'): ?><a class="btn btn-sm btn-primary" href="new-ticket.php">Submit a complaint</a><?php endif; ?></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table app-table">
                <thead><tr><th>Reference</th><th>Subject</th><?php if ($user['role'] !== 'student'): ?><th>Student</th><th>Matric number</th><?php endif; ?><th>Department</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><span class="ticket-ref"><?= e($ticket['reference_number']) ?></span></td>
                        <td><span class="ticket-subject"><?= e($ticket['subject']) ?></span><br><span class="text-muted small"><?= e($ticket['category']) ?></span></td>
                        <?php if ($user['role'] !== 'student'): ?><td><?= e($ticket['student_name']) ?></td><td><?= e($ticket['matric_number']) ?></td><?php endif; ?>
                        <td><?= e($ticket['department_name'] ?? 'Unassigned') ?></td>
                        <td><?= e(date('d M Y', strtotime($ticket['created_at']))) ?></td>
                        <td><span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span></td>
                        <td><a class="small-link" href="ticket.php?id=<?= (int) $ticket['id'] ?>">View case</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
