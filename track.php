<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = current_user($pdo);
if ($user !== null && (int) $user['force_password_change'] === 1) {
    redirect('change-password.php');
}
$active = 'track';
$pageTitle = 'Track a complaint';
$ticket = null;
$updates = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $reference = strtoupper(trim((string) ($_POST['reference_number'] ?? '')));
    $matric = strtoupper(trim((string) ($_POST['matric_number'] ?? '')));
    if ($reference === '' || $matric === '') {
        $error = 'Enter both the reference number and the student matric number.';
    } else {
        $lookup = $pdo->prepare(
            'SELECT t.id, t.reference_number, t.category, t.subject, t.description, t.status, t.created_at, t.updated_at,
                    d.name AS department_name
             FROM tickets t JOIN users u ON u.id = t.student_id
             LEFT JOIN departments d ON d.id = t.department_id
             WHERE t.reference_number = ? AND UPPER(u.matric_number) = ?'
        );
        $lookup->execute([$reference, $matric]);
        $ticket = $lookup->fetch() ?: null;
        if ($ticket) {
            $updateQuery = $pdo->prepare(
                'SELECT tu.status, tu.message, tu.created_at, u.full_name, u.role
                 FROM ticket_updates tu LEFT JOIN users u ON u.id = tu.user_id
                 WHERE tu.ticket_id = ? ORDER BY tu.created_at DESC, tu.id DESC'
            );
            $updateQuery->execute([$ticket['id']]);
            $updates = $updateQuery->fetchAll();
        } else {
            $error = 'No complaint matched those details. Check the reference and matric number and try again.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="row">
    <div class="col-lg-7 mb-3">
        <section class="app-card">
            <div class="card-heading"><div><h2>Find your complaint</h2><p>For privacy, verify the student matric number linked to the request.</p></div></div>
            <div class="card-body">
                <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <form method="post" action="track.php">
                    <?= csrf_field() ?>
                    <div class="form-group"><label class="form-label" for="reference_number">Complaint reference number</label><input class="form-control" id="reference_number" name="reference_number" required placeholder="FCWM-2026-XXXXXXXXXX" value="<?= e($_POST['reference_number'] ?? '') ?>"></div>
                    <div class="form-group"><label class="form-label" for="matric_number">Student matric number</label><input class="form-control" id="matric_number" name="matric_number" required value="<?= e($_POST['matric_number'] ?? ($user['matric_number'] ?? '')) ?>"></div>
                    <button class="btn btn-primary" type="submit"><i class="feather icon-search mr-1"></i>Track complaint</button>
                    <?php if ($user): ?><a href="tickets.php" class="btn btn-light ml-2">My complaints</a><?php endif; ?>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-5 mb-3">
        <?php if ($ticket): ?>
            <section class="app-card">
                <div class="card-heading"><div><h2><?= e($ticket['subject']) ?></h2><p><?= e($ticket['category']) ?></p></div></div>
                <div class="card-body">
                    <div class="reference-panel mb-3"><span>Reference number</span><strong><?= e($ticket['reference_number']) ?></strong></div>
                    <div class="ticket-meta mb-4">
                        <div><small>Status</small><span class="status-pill <?= e(status_class($ticket['status'])) ?> mt-2"><?= e($ticket['status']) ?></span></div>
                        <div><small>Department</small><strong><?= e($ticket['department_name'] ?? 'Awaiting assignment') ?></strong></div>
                        <div><small>Submitted</small><strong><?= e(date('d M Y', strtotime($ticket['created_at']))) ?></strong></div>
                        <div><small>Last updated</small><strong><?= e(date('d M Y', strtotime($ticket['updated_at']))) ?></strong></div>
                    </div>
                    <h3 class="section-title mb-3">Progress updates</h3>
                    <?php if ($updates): ?><div class="timeline">
                        <?php foreach ($updates as $update): ?><div class="timeline-item">
                            <?php if ($update['status']): ?><strong><?= e($update['status']) ?></strong><?php else: ?><strong>Support update</strong><?php endif; ?>
                            <p><?= e($update['message']) ?></p><small><?= e($update['full_name'] ?: 'Support team') ?> &middot; <?= e(date('d M Y, H:i', strtotime($update['created_at']))) ?></small>
                        </div><?php endforeach; ?>
                    </div><?php else: ?><p class="text-muted small mt-3">No updates have been added yet.</p><?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <section class="app-card"><div class="card-heading"><div><h2>Track securely</h2><p>Keep your reference number safe.</p></div></div><div class="card-body"><div class="empty-state"><i class="feather icon-shield"></i><strong>Your complaint details stay private</strong><p>Only someone with the complaint reference and matching student matric number can see its status.</p></div></div></section>
        <?php endif; ?>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
