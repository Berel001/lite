<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$user = require_login($pdo);
require_role($user, ['admin']);
$pageTitle = 'Departments & staff';
$active = 'manage';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'add_school') {
        $schoolName = trim((string) ($_POST['school_name'] ?? ''));
        if ($schoolName === '' || strlen($schoolName) > 150) {
            $error = 'Enter a school name of no more than 150 characters.';
        } else {
            try {
                $insert = $pdo->prepare('INSERT INTO schools (name) VALUES (?)');
                $insert->execute([$schoolName]);
                set_flash('success', 'School added.');
                redirect('manage.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That school already exists.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'add_department') {
        $schoolId = filter_var($_POST['school_id'] ?? null, FILTER_VALIDATE_INT);
        $departmentName = trim((string) ($_POST['department_name'] ?? ''));
        $schoolCheck = $pdo->prepare('SELECT id FROM schools WHERE id = ?');
        $schoolCheck->execute([$schoolId]);
        if (!$schoolCheck->fetch() || $departmentName === '' || strlen($departmentName) > 150) {
            $error = 'Choose a school and enter a department name of no more than 150 characters.';
        } else {
            try {
                $insert = $pdo->prepare('INSERT INTO departments (school_id, name) VALUES (?, ?)');
                $insert->execute([$schoolId, $departmentName]);
                set_flash('success', 'Department added.');
                redirect('manage.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That department already exists in the selected school.';
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($action === 'add_staff') {
        $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string) ($_POST['full_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $departmentCheck = $pdo->prepare('SELECT id FROM departments WHERE id = ?');
        $departmentCheck->execute([$departmentId]);
        if (!$departmentCheck->fetch() || $name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Choose a department and enter a valid staff name and email address.';
        } elseif (strlen($password) < 8) {
            $error = 'The initial password must contain at least 8 characters.';
        } else {
            try {
                $insert = $pdo->prepare(
                    "INSERT INTO users (role, full_name, email, password_hash, department_id, force_password_change)
                     VALUES ('department', ?, ?, ?, ?, 1)"
                );
                $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $departmentId]);
                set_flash('success', 'Department staff account created. The staff member must change the initial password at sign-in.');
                redirect('manage.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That email address is already associated with an account.';
                } else {
                    throw $exception;
                }
            }
        }
    } else {
        $error = 'Choose a valid administration action.';
    }
}

$schools = $pdo->query('SELECT id, name FROM schools ORDER BY name')->fetchAll();
$departments = $pdo->query(
    'SELECT d.id, d.name, s.name AS school_name FROM departments d JOIN schools s ON s.id = d.school_id ORDER BY s.name, d.name'
)->fetchAll();
$staff = $pdo->query(
    "SELECT u.full_name, u.email, u.force_password_change, d.name AS department_name
     FROM users u LEFT JOIN departments d ON d.id = u.department_id
     WHERE u.role = 'department' ORDER BY d.name, u.full_name"
)->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<?php if ($error !== ''): ?><div class="alert alert-danger app-alert"><?= e($error) ?></div><?php endif; ?>
<div class="row">
    <div class="col-lg-6 mb-3">
        <section class="app-card mb-3">
            <div class="card-heading"><div><h2>Add a school</h2><p>Schools are shown to students during registration.</p></div></div>
            <div class="card-body"><form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="add_school">
                <div class="form-group"><label class="form-label" for="school_name">School name</label><input class="form-control" id="school_name" name="school_name" maxlength="150" required></div>
                <button class="btn btn-primary btn-sm" type="submit">Add school</button>
            </form></div>
        </section>
        <section class="app-card">
            <div class="card-heading"><div><h2>Add a department</h2><p>Department staff can only view complaints routed to their team.</p></div></div>
            <div class="card-body"><form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="add_department">
                <div class="form-group"><label class="form-label" for="department_school">School</label><select class="custom-select" id="department_school" name="school_id" required><option value="">Select a school</option><?php foreach ($schools as $school): ?><option value="<?= (int) $school['id'] ?>"><?= e($school['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label" for="department_name">Department name</label><input class="form-control" id="department_name" name="department_name" maxlength="150" required></div>
                <button class="btn btn-primary btn-sm" type="submit">Add department</button>
            </form></div>
        </section>
    </div>
    <div class="col-lg-6 mb-3">
        <section class="app-card mb-3">
            <div class="card-heading"><div><h2>Create department staff account</h2><p>Staff accounts have department-scoped access.</p></div></div>
            <div class="card-body"><form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="add_staff">
                <div class="form-group"><label class="form-label" for="staff_department">Department</label><select class="custom-select" id="staff_department" name="department_id" required><option value="">Select a department</option><?php foreach ($departments as $department): ?><option value="<?= (int) $department['id'] ?>"><?= e($department['school_name'] . ' / ' . $department['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label" for="staff_name">Staff full name</label><input class="form-control" id="staff_name" name="full_name" maxlength="150" required></div>
                <div class="form-group"><label class="form-label" for="staff_email">Staff email</label><input class="form-control" id="staff_email" name="email" type="email" maxlength="190" required></div>
                <div class="form-group"><label class="form-label" for="staff_password">Temporary password</label><input class="form-control" id="staff_password" name="password" type="password" minlength="8" required><div class="form-hint">The staff member is required to change this password at first sign-in.</div></div>
                <button class="btn btn-primary btn-sm" type="submit">Create staff account</button>
            </form></div>
        </section>
    </div>
</div>

<section class="app-card mb-3">
    <div class="card-heading"><div><h2>Configured departments</h2><p>Departments available for complaint routing and student selection.</p></div></div>
    <?php if (!$departments): ?><div class="empty-state"><strong>No departments configured</strong></div>
    <?php else: ?><div class="table-responsive"><table class="table app-table"><thead><tr><th>School</th><th>Department</th></tr></thead><tbody><?php foreach ($departments as $department): ?><tr><td><?= e($department['school_name']) ?></td><td><?= e($department['name']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<section class="app-card">
    <div class="card-heading"><div><h2>Department staff accounts</h2><p>Accounts and assigned departments.</p></div></div>
    <?php if (!$staff): ?><div class="empty-state"><strong>No department staff accounts yet</strong><p>Create a staff account above to enable department access.</p></div>
    <?php else: ?><div class="table-responsive"><table class="table app-table"><thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Account setup</th></tr></thead><tbody><?php foreach ($staff as $member): ?><tr><td><?= e($member['full_name']) ?></td><td><?= e($member['email']) ?></td><td><?= e($member['department_name'] ?? 'Unassigned') ?></td><td><?= (int) $member['force_password_change'] === 1 ? 'Password change required' : 'Active' ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
