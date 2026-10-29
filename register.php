<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$schools = $pdo->query(
    'SELECT s.id, s.name, d.id AS department_id, d.name AS department_name
     FROM schools s JOIN departments d ON d.school_id = s.id
     ORDER BY s.name, d.name'
)->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $matric = strtoupper(trim((string) ($_POST['matric_number'] ?? '')));
    $name = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $schoolId = filter_var($_POST['school_id'] ?? null, FILTER_VALIDATE_INT);
    $departmentId = filter_var($_POST['department_id'] ?? null, FILTER_VALIDATE_INT);
    $password = (string) ($_POST['password'] ?? '');

    if ($matric === '' || strlen($matric) > 50 || $name === '' || strlen($name) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid matric number, full name, and email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Choose a password with at least 8 characters.';
    } elseif (!$schoolId || !$departmentId) {
        $error = 'Select your school and department.';
    } else {
        $department = $pdo->prepare('SELECT id FROM departments WHERE id = ? AND school_id = ?');
        $department->execute([$departmentId, $schoolId]);
        if (!$department->fetch()) {
            $error = 'The selected department does not belong to that school.';
        } else {
            try {
                $insert = $pdo->prepare(
                    "INSERT INTO users (role, matric_number, full_name, email, password_hash, school_id, department_id)
                     VALUES ('student', ?, ?, ?, ?, ?, ?)"
                );
                $insert->execute([$matric, $name, $email, password_hash($password, PASSWORD_DEFAULT), $schoolId, $departmentId]);
                set_flash('success', 'Your student account has been created. You can now sign in.');
                redirect('login.php');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    $error = 'That matric number or email address is already registered.';
                } else {
                    throw $exception;
                }
            }
        }
    }
}

$user = null;
$pageTitle = 'Create student account';
$active = 'register';
require __DIR__ . '/includes/header.php';
?>
<div class="guest-layout">
    <div class="app-card">
        <div class="card-heading">
            <div><h2>Student registration</h2><p>Use your official matric number and college details.</p></div>
        </div>
        <div class="card-body">
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <form method="post" action="register.php">
                <?= csrf_field() ?>
                <div class="form-group"><label class="form-label" for="matric_number">Matric number</label><input class="form-control" id="matric_number" name="matric_number" required maxlength="50" value="<?= e($_POST['matric_number'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label" for="full_name">Full name</label><input class="form-control" id="full_name" name="full_name" autocomplete="name" required maxlength="150" value="<?= e($_POST['full_name'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label" for="school_id">School</label>
                    <select class="custom-select" id="school_id" name="school_id" required><option value="">Select your school</option>
                        <?php foreach ($schools as $school): ?>
                            <?php if (!isset($schoolSeen) || !isset($schoolSeen[$school['id']])): $schoolSeen[$school['id']] = true; ?>
                                <option value="<?= (int) $school['id'] ?>" <?= (string) ($_POST['school_id'] ?? '') === (string) $school['id'] ? 'selected' : '' ?>><?= e($school['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label" for="department_id">Department</label>
                    <select class="custom-select" id="department_id" name="department_id" required><option value="">Select your department</option>
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= (int) $school['department_id'] ?>" data-school="<?= (int) $school['id'] ?>" <?= (string) ($_POST['department_id'] ?? '') === (string) $school['department_id'] ? 'selected' : '' ?>><?= e($school['department_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label" for="password">Password</label><input class="form-control" id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><div class="form-hint">Use at least 8 characters.</div></div>
                <button class="btn btn-primary btn-block mt-3" type="submit">Create student account</button>
            </form>
            <p class="text-center mt-4 mb-0 small">Already registered? <a class="small-link" href="login.php">Sign in</a></p>
        </div>
    </div>
</div>
<script>
    const schoolSelect = document.getElementById('school_id');
    const departmentSelect = document.getElementById('department_id');
    function filterDepartments(reset) {
        if (reset) {
            departmentSelect.value = '';
        }
        Array.from(departmentSelect.options).forEach(function (option) {
            option.hidden = option.value !== '' && schoolSelect.value !== '' && option.dataset.school !== schoolSelect.value;
        });
    }
    schoolSelect.addEventListener('change', function () { filterDepartments(true); });
    filterDepartments(false);
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
