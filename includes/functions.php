<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Your request could not be verified. Reload the page and try again.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function current_user(PDO $pdo): ?array
{
    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) && !ctype_digit((string) $userId)) {
        return null;
    }

    $statement = $pdo->prepare(
        'SELECT u.id, u.role, u.matric_number, u.full_name, u.email, u.school_id,
                u.department_id, u.force_password_change, s.name AS school_name,
                d.name AS department_name
         FROM users u
         LEFT JOIN schools s ON s.id = u.school_id
         LEFT JOIN departments d ON d.id = u.department_id
         WHERE u.id = ?'
    );
    $statement->execute([(int) $userId]);
    $user = $statement->fetch();

    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }

    return $user;
}

function require_login(PDO $pdo): array
{
    $user = current_user($pdo);
    if ($user === null) {
        set_flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }

    if ((int) $user['force_password_change'] === 1 && basename($_SERVER['PHP_SELF']) !== 'change-password.php') {
        redirect('change-password.php');
    }

    return $user;
}

function require_role(array $user, array $roles): void
{
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this page.');
    }
}

function status_class(string $status): string
{
    return match ($status) {
        'Submitted' => 'status-submitted',
        'Assigned' => 'status-assigned',
        'In Progress' => 'status-progress',
        'Waiting on Student' => 'status-waiting',
        'Resolved', 'Closed' => 'status-resolved',
        default => 'status-submitted',
    };
}

function ticket_statuses(): array
{
    return ['Assigned', 'In Progress', 'Waiting on Student', 'Resolved', 'Closed'];
}
