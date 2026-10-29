<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = current_user($pdo);
if ($user === null) {
    set_flash('warning', 'Please sign in first.');
    redirect('login.php');
}
$pageTitle = 'Change password';
$active = 'dashboard';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $passwordQuery = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $passwordQuery->execute([$user['id']]);
    $passwordHash = $passwordQuery->fetchColumn();

    if (!is_string($passwordHash) || !password_verify($currentPassword, $passwordHash)) {
        $error = 'Your current password is incorrect.';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Choose a new password with at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The new password and confirmation do not match.';
    } elseif (password_verify($newPassword, $passwordHash)) {
        $error = 'Choose a password different from your current password.';
    } else {
        $change = $pdo->prepare('UPDATE users SET password_hash = ?, force_password_change = 0 WHERE id = ?');
        $change->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
        set_flash('success', 'Your password has been updated.');
        redirect('dashboard.php');
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="guest-layout">
    <section class="app-card">
        <div class="card-heading"><div><h2>Secure your account</h2><p><?= (int) $user['force_password_change'] === 1 ? 'Set a new password before continuing to the support system.' : 'Update the password used to sign in.' ?></p></div></div>
        <div class="card-body">
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post">
                <?= csrf_field() ?>
                <div class="form-group"><label class="form-label" for="current_password">Current password</label><input class="form-control" id="current_password" name="current_password" type="password" autocomplete="current-password" required></div>
                <div class="form-group"><label class="form-label" for="new_password">New password</label><input class="form-control" id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" required></div>
                <div class="form-group"><label class="form-label" for="confirm_password">Confirm new password</label><input class="form-control" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" required></div>
                <button class="btn btn-primary" type="submit">Update password</button>
                <?php if ((int) $user['force_password_change'] !== 1): ?><a href="dashboard.php" class="btn btn-light ml-2">Cancel</a><?php endif; ?>
            </form>
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
