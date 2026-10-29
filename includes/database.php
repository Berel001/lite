<?php
declare(strict_types=1);

$host = getenv('FCWM_DB_HOST') ?: '127.0.0.1';
$port = getenv('FCWM_DB_PORT') ?: '3306';
$database = getenv('FCWM_DB_NAME') ?: 'fcwm_student_support';
$username = getenv('FCWM_DB_USER') ?: 'root';
$password = getenv('FCWM_DB_PASSWORD') ?: '';

if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $database)) {
    throw new RuntimeException('FCWM_DB_NAME must contain only letters, numbers, and underscores.');
}

try {
    $server = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    error_log('FCWM database connection failed: ' . $exception->getMessage());
    throw new RuntimeException(
        'The support database could not be reached. Start MySQL in the XAMPP Control Panel and refresh this page.',
        0,
        $exception
    );
}

$schema = [
    "CREATE TABLE IF NOT EXISTS schools (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL UNIQUE,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS departments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id INT UNSIGNED NOT NULL,
        name VARCHAR(150) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_department_school (school_id, name),
        CONSTRAINT fk_departments_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role ENUM('student', 'admin', 'department') NOT NULL,
        matric_number VARCHAR(50) NULL UNIQUE,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        school_id INT UNSIGNED NULL,
        department_id INT UNSIGNED NULL,
        force_password_change TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_users_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL,
        CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
        KEY idx_users_role_department (role, department_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS tickets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reference_number VARCHAR(40) NOT NULL UNIQUE,
        student_id INT UNSIGNED NOT NULL,
        school_id INT UNSIGNED NOT NULL,
        department_id INT UNSIGNED NULL,
        category VARCHAR(100) NOT NULL,
        subject VARCHAR(180) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('Submitted', 'Assigned', 'In Progress', 'Waiting on Student', 'Resolved', 'Closed') NOT NULL DEFAULT 'Submitted',
        assigned_by INT UNSIGNED NULL,
        assigned_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_tickets_student FOREIGN KEY (student_id) REFERENCES users(id),
        CONSTRAINT fk_tickets_school FOREIGN KEY (school_id) REFERENCES schools(id),
        CONSTRAINT fk_tickets_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
        CONSTRAINT fk_tickets_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
        KEY idx_tickets_department_status (department_id, status),
        KEY idx_tickets_student_created (student_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS ticket_updates (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_id BIGINT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NULL,
        status VARCHAR(40) NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_updates_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        CONSTRAINT fk_updates_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        KEY idx_updates_ticket_created (ticket_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
];

foreach ($schema as $statement) {
    $pdo->exec($statement);
}

$schoolCount = (int) $pdo->query('SELECT COUNT(*) FROM schools')->fetchColumn();
if ($schoolCount === 0) {
    $pdo->beginTransaction();
    $schoolInsert = $pdo->prepare('INSERT INTO schools (name) VALUES (?)');
    $departmentInsert = $pdo->prepare('INSERT INTO departments (school_id, name) VALUES (?, ?)');
    $departments = [
        'Wildlife Management',
        'Fisheries and Aquaculture',
        'Forestry and Environmental Management',
        'Ecotourism and Hospitality Management',
    ];
    $schoolInsert->execute(['School of Wildlife and Environmental Sciences']);
    $schoolId = (int) $pdo->lastInsertId();
    foreach ($departments as $department) {
        $departmentInsert->execute([$schoolId, $department]);
    }
    $pdo->commit();
}

$adminCount = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminCount === 0) {
    $adminInsert = $pdo->prepare(
        "INSERT INTO users (role, full_name, email, password_hash, force_password_change)
         VALUES ('admin', ?, ?, ?, 1)"
    );
    $adminInsert->execute([
        'System Administrator',
        'admin@fcwm.edu.ng',
        password_hash('Admin@123', PASSWORD_DEFAULT),
    ]);
}

return $pdo;
