<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login($pdo);
$ticketId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$ticketId) {
    http_response_code(404);
    exit('Complaint not found.');
}

$ticketQuery = $pdo->prepare(
    'SELECT t.*, student.full_name AS student_name, student.matric_number,
            student.email AS student_email, school.name AS school_name,
            department.name AS department_name
     FROM tickets t
     JOIN users student ON student.id = t.student_id
     JOIN schools school ON school.id = t.school_id
     LEFT JOIN departments department ON department.id = t.department_id
     WHERE t.id = ?'
);
$ticketQuery->execute([$ticketId]);
$ticket = $ticketQuery->fetch();
if (!$ticket) {
    http_response_code(404);
    exit('Complaint not found.');
}

$canView = $user['role'] === 'admin'
    || ($user['role'] === 'student' && (int) $ticket['student_id'] === (int) $user['id'])
    || ($user['role'] === 'department'
        && $ticket['department_id'] !== null
        && (int) $ticket['department_id'] === (int) $user['department_id']);
if (!$canView) {
    http_response_code(403);
    exit('You do not have permission to view this complaint.');
}

$departments = $user['role'] === 'admin'
    ? $pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll()
    : [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!in_array($user['role'], ['admin', 'department'], true)) {
        http_response_code(403);
        exit('You do not have permission to update this complaint.');
    }

    $action = (string) ($_POST['action'] ?? '');
    $message = trim((string) ($_POST['message'] ?? ''));
    if (strlen($message) > 4000) {
        $error = 'Keep the update under 4,000 characters.';
    } elseif ($action === 'route' && $user['role'] === 'admin') {
        $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
        $departmentQuery = $pdo->prepare('SELECT id, name FROM departments WHERE id = ?');
        $departmentQuery->execute([$departmentId]);
        $department = $departmentQuery->fetch();
        if (!$department) {
            $error = 'Select a valid department.';
        } else {
            $pdo->beginTransaction();
            $newStatus = $ticket['status'] === 'Submitted' ? 'Assigned' : $ticket['status'];
            $route = $pdo->prepare(
                'UPDATE tickets SET department_id = ?, assigned_by = ?, assigned_at = NOW(), status = ? WHERE id = ?'
            );
            $route->execute([$department['id'], $user['id'], $newStatus, $ticketId]);
            $routeMessage = 'Complaint routed to ' . $department['name'] . '.';
            if ($message !== '') {
                $routeMessage .= "\n" . $message;
            }
            $log = $pdo->prepare('INSERT INTO ticket_updates (ticket_id, user_id, status, message) VALUES (?, ?, ?, ?)');
            $log->execute([$ticketId, $user['id'], $newStatus, $routeMessage]);
            $pdo->commit();
            set_flash('success', 'Complaint routed to ' . $department['name'] . '.');
            redirect('ticket.php?id=' . $ticketId);
        }
    } elseif ($action === 'update' && in_array($user['role'], ['admin', 'department'], true)) {
        $newStatus = trim((string) ($_POST['status'] ?? $ticket['status']));
        if (!in_array($newStatus, ticket_statuses(), true)) {
            $error = 'Choose a valid complaint status.';
        } elseif ($message === '' && $newStatus === $ticket['status']) {
            $error = 'Add a message or choose a different status.';
        } else {
            $pdo->beginTransaction();
            $statusUpdate = $pdo->prepare('UPDATE tickets SET status = ? WHERE id = ?');
            $statusUpdate->execute([$newStatus, $ticketId]);
            $logMessage = $message !== '' ? $message : 'Complaint status updated.';
            $log = $pdo->prepare('INSERT INTO ticket_updates (ticket_id, user_id, status, message) VALUES (?, ?, ?, ?)');
            $log->execute([$ticketId, $user['id'], $newStatus, $logMessage]);
            $pdo->commit();
            set_flash('success', 'Complaint update saved.');
            redirect('ticket.php?id=' . $ticketId);
        }
    } else {
        $error = 'Choose a valid complaint action.';
    }
}

$updatesQuery = $pdo->prepare(
    'SELECT tu.status, tu.message, tu.created_at, u.full_name, u.role
     FROM ticket_updates tu LEFT JOIN users u ON u.id = tu.user_id
     WHERE tu.ticket_id = ? ORDER BY tu.created_at DESC, tu.id DESC'
);
$updatesQuery->execute([$ticketId]);
$updates = $updatesQuery->fetchAll();
$pageTitle = 'Complaint details';
$active = $user['role'] === 'student' ? 'tickets' : ($user['role'] === 'admin' ? 'tickets' : 'tickets');
require __DIR__ . '/includes/header.php';
?>
<?php if (isset($_GET['created'])): ?>
    <div class="alert alert-success app-alert">Your complaint is now in the support queue. Keep this reference number for tracking.</div>
<?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-danger app-alert"><?= e($error) ?></div><?php endif; ?>
<div class="detail-grid">
    <div>
        <section class="app-card mb-3">
            <div class="card-heading">
                <div><span class="ticket-ref"><?= e($ticket['reference_number']) ?></span><h2 class="mt-2"><?= e($ticket['subject']) ?></h2><p><?= e($ticket['category']) ?> &middot; submitted <?= e(date('d M Y, H:i', strtotime($ticket['created_at']))) ?></p></div>
                <span class="status-pill <?= e(status_class($ticket['status'])) ?>"><?= e($ticket['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="reference-panel mb-4"><span>Save this reference number to track your complaint</span><strong><?= e($ticket['reference_number']) ?></strong></div>
                <h3 class="section-title mb-2">Complaint details</h3>
                <div class="detail-description"><?= e($ticket['description']) ?></div>
            </div>
        </section>
        <section class="app-card">
            <div class="card-heading"><div><h2>Progress history</h2><p>Status changes and replies from support.</p></div></div>
            <div class="card-body">
                <?php if ($updates): ?><div class="timeline">
                    <?php foreach ($updates as $update): ?><div class="timeline-item">
                        <strong><?= e($update['status'] ?: 'Support update') ?></strong>
                        <p><?= e($update['message']) ?></p>
                        <small><?= e($update['full_name'] ?: 'Support team') ?> &middot; <?= e(date('d M Y, H:i', strtotime($update['created_at']))) ?></small>
                    </div><?php endforeach; ?>
                </div><?php else: ?><div class="empty-state"><i class="feather icon-clock"></i><strong>No progress updates yet</strong></div><?php endif; ?>
            </div>
        </section>
    </div>
    <div>
        <section class="app-card mb-3">
            <div class="card-heading"><div><h2>Student & routing</h2><p>Case ownership and contact details.</p></div></div>
            <div class="card-body">
                <div class="ticket-meta">
                    <div><small>Student</small><strong><?= e($ticket['student_name']) ?></strong></div>
                    <div><small>Matric number</small><strong><?= e($ticket['matric_number']) ?></strong></div>
                    <div><small>School</small><strong><?= e($ticket['school_name']) ?></strong></div>
                    <div><small>Department</small><strong><?= e($ticket['department_name'] ?? 'Not assigned') ?></strong></div>
                    <div><small>Email</small><strong><?= e($ticket['student_email']) ?></strong></div>
                    <div><small>Last updated</small><strong><?= e(date('d M Y, H:i', strtotime($ticket['updated_at']))) ?></strong></div>
                </div>
            </div>
        </section>

        <?php if ($user['role'] === 'admin'): ?>
            <section class="app-card mb-3">
                <div class="card-heading"><div><h2>Route to department</h2><p>Assign this complaint to the team that can resolve it.</p></div></div>
                <div class="card-body">
                    <?php if (!$departments): ?><p class="text-muted small">Add a department in Departments &amp; staff before routing complaints.</p>
                    <?php else: ?>
                        <form method="post">
                            <?= csrf_field() ?><input type="hidden" name="action" value="route">
                            <div class="form-group"><label class="form-label" for="department_id">Department</label>
                                <select class="custom-select" id="department_id" name="department_id" required>
                                    <option value="">Select a department</option>
                                    <?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>" <?= (string) $ticket['department_id'] === (string) $department['id'] ? 'selected' : '' ?>><?= e($department['name']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group"><label class="form-label" for="route_message">Note (optional)</label><textarea class="form-control" id="route_message" name="message" rows="3" maxlength="4000" placeholder="Add context for the department"></textarea></div>
                            <button class="btn btn-primary btn-sm" type="submit">Route complaint</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (in_array($user['role'], ['admin', 'department'], true)): ?>
            <section class="app-card">
                <div class="card-heading"><div><h2>Post an update</h2><p>The student can see status changes and replies.</p></div></div>
                <div class="card-body">
                    <form method="post">
                        <?= csrf_field() ?><input type="hidden" name="action" value="update">
                        <div class="form-group"><label class="form-label" for="status">Status</label>
                            <select class="custom-select" id="status" name="status">
                                <?php foreach (ticket_statuses() as $status): ?><option value="<?= e($status) ?>" <?= $ticket['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label class="form-label" for="message">Update for student</label><textarea class="form-control" id="message" name="message" rows="4" maxlength="4000" placeholder="Explain what has been done or what is needed next."></textarea></div>
                        <button class="btn btn-primary btn-sm" type="submit">Save update</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
