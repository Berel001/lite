<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login($pdo);
require_role($user, ['student']);
$pageTitle = 'Submit a complaint';
$active = 'new-ticket';
$categories = [
    'Academic matters',
    'Registration and records',
    'Fees and payments',
    'Facilities and maintenance',
    'ICT and student portal',
    'Library services',
    'Student welfare',
    'Other',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $category = trim((string) ($_POST['category'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));

    if (!in_array($category, $categories, true)) {
        $error = 'Choose a valid complaint topic.';
    } elseif ($subject === '' || strlen($subject) > 180) {
        $error = 'Enter a short subject of no more than 180 characters.';
    } elseif (strlen($description) < 15 || strlen($description) > 12000) {
        $error = 'Describe your request in at least 15 characters and no more than 12,000 characters.';
    } else {
        $reference = 'FCWM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
        $pdo->beginTransaction();
        $insert = $pdo->prepare(
            "INSERT INTO tickets (reference_number, student_id, school_id, category, subject, description)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $insert->execute([$reference, $user['id'], $user['school_id'], $category, $subject, $description]);
        $ticketId = (int) $pdo->lastInsertId();
        $update = $pdo->prepare(
            "INSERT INTO ticket_updates (ticket_id, user_id, status, message)
             VALUES (?, ?, 'Submitted', 'Complaint received. Your request is waiting for review.')"
        );
        $update->execute([$ticketId, $user['id']]);
        $pdo->commit();
        set_flash('success', 'Your complaint has been submitted. Save the reference number to track its progress.');
        redirect('ticket.php?id=' . $ticketId . '&created=1');
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="row">
    <div class="col-lg-8 mb-3">
        <section class="app-card">
            <div class="card-heading"><div><h2>Tell us what you need help with</h2><p>Your complaint is private to the support team and relevant department.</p></div></div>
            <div class="card-body">
                <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <form method="post" action="new-ticket.php">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="form-label" for="category">Topic</label>
                        <select class="custom-select" id="category" name="category" required>
                            <option value="">Select the closest topic</option>
                            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>" <?= ($_POST['category'] ?? '') === $category ? 'selected' : '' ?>><?= e($category) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="subject">Request subject</label>
                        <input class="form-control" id="subject" name="subject" maxlength="180" required placeholder="A short summary of the issue" value="<?= e($_POST['subject'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Describe your issue or request</label>
                        <textarea class="form-control" id="description" name="description" rows="7" maxlength="12000" required placeholder="Include relevant dates, locations or steps already taken."><?= e($_POST['description'] ?? '') ?></textarea>
                        <div class="form-hint">Please do not include your password or other sensitive account information.</div>
                    </div>
                    <button class="btn btn-primary" type="submit"><i class="feather icon-send mr-1"></i>Submit complaint</button>
                    <a class="btn btn-light ml-2" href="tickets.php">Cancel</a>
                </form>
            </div>
        </section>
    </div>
    <div class="col-lg-4 mb-3">
        <aside class="app-card">
            <div class="card-heading"><div><h2>What happens next?</h2><p>Your complaint will be logged immediately.</p></div></div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item"><strong>1. Reference created</strong><p>A unique reference number is generated for your request.</p></div>
                    <div class="timeline-item"><strong>2. Support reviews it</strong><p>The administrator routes it to the relevant department.</p></div>
                    <div class="timeline-item"><strong>3. Follow progress</strong><p>Sign in or use Track a complaint to view updates.</p></div>
                </div>
            </div>
        </aside>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
