<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user($pdo)) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identity = trim((string) ($_POST['identity'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $statement = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ? OR matric_number = ? LIMIT 1');
    $statement->execute([strtolower($identity), $identity]);
    $account = $statement->fetch();

    if ($account && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $account['id'];
        redirect('dashboard.php');
    }

    $error = 'The sign-in details were not recognized. Check your email or matric number and password.';
}

$user = null;
$pageTitle = 'Sign in';
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f3f8f4">
    <meta name="description" content="Sign in to the Federal College of Wildlife Management student support portal.">
    <title>Sign in | FCWM Student Support</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <main class="login-layout mx-auto">
        <section class="login-card grid" aria-label="Student support sign in">
            <aside class="login-story">
                <a class="login-brand" href="login.php" aria-label="FCWM Student Support home">
                    <span class="login-brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M20 4c-7.5 0-13 3.2-13 9a5 5 0 0 0 5 5c5.8 0 8-6.5 8-14Z"/><path d="M4 21c1.8-5.8 5-9 10-12"/></svg>
                    </span>
                    <span class="login-brand-copy"><strong>FCWM</strong><small>Student Support</small></span>
                </a>

                <div class="story-content">
                    <span class="story-kicker"><span></span> HERE FOR YOUR JOURNEY</span>
                    <h1>Every concern<br>deserves a <em>solution.</em></h1>
                    <p>One place to ask for support, share what matters, and stay up to date from start to resolution.</p>

                    <div class="story-illustration" aria-hidden="true">
                        <div class="illustration-glow"></div>
                        <svg class="student-illustration" viewBox="0 0 520 390" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M46 329c68-10 117-6 170 2 72 11 163-4 257-2v61H46v-61Z" fill="#D7EBD9" fill-opacity=".12"/>
                            <path d="M51 326c24-39 24-84 7-127m13 86c-26-18-43-42-49-69m54 45c23-20 33-46 32-76" stroke="#A8D8B1" stroke-width="8" stroke-linecap="round"/>
                            <path d="M67 269c-25-3-39-22-45-42 23-1 40 10 45 42Zm10-24c-9-24-4-44 12-60 12 23 7 43-12 60Zm-4 42c17-17 37-20 58-10-14 21-33 25-58 10Z" fill="#A8D8B1"/>
                            <path d="M473 321c-18-31-20-67-9-107m-10 71c22-13 37-32 44-53m-49 32c-20-16-30-37-29-61" stroke="#A8D8B1" stroke-width="7" stroke-linecap="round"/>
                            <path d="M456 267c21-3 34-18 40-36-20 0-35 9-40 36Zm-8-20c8-20 3-37-10-51-11 20-7 37 10 51Zm4 37c-15-14-32-17-50-8 12 18 28 22 50 8Z" fill="#A8D8B1"/>
                            <rect x="125" y="106" width="270" height="209" rx="26" fill="#F6FAF3"/>
                            <rect x="146" y="128" width="228" height="166" rx="17" fill="#E5F1E5"/>
                            <circle cx="328" cy="158" r="15" fill="#E6B75E"/>
                            <path d="M148 237c34-43 59-42 87-9 20-31 45-52 72-33 19-16 42-16 66 8v74H148v-40Z" fill="#BED9BE"/>
                            <path d="M148 259c38-31 69-30 98-3 24-22 51-25 79-2 17-11 33-14 49-7v47H148v-35Z" fill="#9FC5A6"/>
                            <rect x="168" y="267" width="184" height="20" rx="10" fill="#D5E9D5"/>
                            <path d="M258 204c-24 0-42 19-42 43v49h84v-49c0-24-18-43-42-43Z" fill="#176B59"/>
                            <path d="M226 200c0-18 14-32 32-32s32 14 32 32v18h-64v-18Z" fill="#D89569"/>
                            <path d="M220 199c0-23 16-39 38-39 24 0 38 16 38 39v9h-10l-7-18c-9 8-25 12-47 11l-2 8h-10v-10Z" fill="#25423A"/>
                            <path d="m237 288 21-26 21 26" stroke="#F0C96A" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M258 262v28" stroke="#F0C96A" stroke-width="7" stroke-linecap="round"/>
                            <rect x="180" y="302" width="156" height="13" rx="6.5" fill="#D3E5D2"/>
                            <rect x="95" y="243" width="108" height="78" rx="11" transform="rotate(-8 95 243)" fill="#E7B960"/>
                            <path d="m118 259 76-11 10 61-76 11-10-61Z" fill="#FFF9E8"/>
                            <path d="m139 273 39-6m-36 18 44-7m-41 19 31-5" stroke="#C1D0BE" stroke-width="4" stroke-linecap="round"/>
                            <rect x="362" y="260" width="54" height="67" rx="7" transform="rotate(8 362 260)" fill="#C5E1C8"/>
                            <path d="m376 278 24 4m-26 7 23 3m-24 8 15 2" stroke="#7BAA85" stroke-width="4" stroke-linecap="round"/>
                            <circle cx="113" cy="126" r="5" fill="#E6B75E"/>
                            <path d="m412 148 5 10 10 5-10 5-5 10-5-10-10-5 10-5 5-10Z" fill="#E6B75E"/>
                            <path d="m420 210 4 7 8 4-8 4-4 8-4-8-8-4 8-4 4-7Z" fill="#F1DDA3"/>
                            <path d="m105 185 4 7 8 4-8 4-4 8-4-8-8-4 8-4 4-7Z" fill="#F1DDA3"/>
                            <path d="M202 320h114" stroke="#A5CCA9" stroke-width="6" stroke-linecap="round"/>
                        </svg>
                        <div class="illustration-note">
                            <span class="note-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6"/></svg>
                            </span>
                            <span><strong>We're listening</strong><small>Your support starts here</small></span>
                        </div>
                    </div>
                </div>
                <div class="story-footer"><span class="story-footer-dot"></span> A better way forward, together.</div>
            </aside>

            <section class="login-form-panel">
                <div class="form-panel-top">
                    <span class="mobile-brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M20 4c-7.5 0-13 3.2-13 9a5 5 0 0 0 5 5c5.8 0 8-6.5 8-14Z"/><path d="M4 21c1.8-5.8 5-9 10-12"/></svg>
                    </span>
                    <span class="secure-label">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>
                        SECURE STUDENT PORTAL
                    </span>
                </div>

                <div class="form-intro">
                    <span class="form-eyebrow">WELCOME BACK</span>
                    <h2>Sign in to your<br>support space.</h2>
                    <p>Pick up right where you left off. Your college community is here to help.</p>
                </div>

                <?php if ($flash): ?>
                    <div class="login-notice notice-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="login-notice notice-error" role="alert">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form class="login-form" method="post" action="login.php">
                    <?= csrf_field() ?>
                    <div class="login-field">
                        <label for="identity">Email address or matric number</label>
                        <div class="input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                            <input class="login-input" id="identity" name="identity" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="you@example.com or your matric no." required autofocus value="<?= e($_POST['identity'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="login-field">
                        <div class="password-label-row">
                            <label for="password">Password</label>
                        </div>
                        <div class="input-wrap">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                            <input class="login-input" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                            <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" data-password-toggle>
                                <svg class="eye-open" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-closed" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.4 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10.7 10.7 0 0 0 4-.8"/></svg>
                            </button>
                        </div>
                    </div>
                    <button class="login-submit" type="submit">
                        <span>Sign in securely</span>
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
                    </button>
                </form>

                <div class="signup-prompt">
                    <span>New to the student portal?</span>
                    <a href="register.php">Create an account <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg></a>
                </div>
                <a class="track-link" href="track.php">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    Just tracking a complaint? <strong>Look it up here</strong>
                </a>
                <div class="form-panel-footer">
                    <span>&copy; <?= date('Y') ?> Federal College of Wildlife Management</span>
                    <span class="footer-seal"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 2 8 4v6c0 5-3.4 8.5-8 10-4.6-1.5-8-5-8-10V6l8-4Z"/><path d="M8 12c3.5 0 5.5-2 8-5-1 6-3 9-8 10 1.5-2.2 3.2-3.7 6-5"/></svg> FCWM</span>
                </div>
            </section>
        </section>
        <p class="login-page-caption">FEDERAL COLLEGE OF WILDLIFE MANAGEMENT <span>&middot;</span> STUDENT SUPPORT SERVICES</p>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.querySelector('[data-password-toggle]');

        passwordToggle.addEventListener('click', function () {
            const showingPassword = passwordInput.type === 'password';
            passwordInput.type = showingPassword ? 'text' : 'password';
            passwordToggle.setAttribute('aria-pressed', String(showingPassword));
            passwordToggle.setAttribute('aria-label', showingPassword ? 'Hide password' : 'Show password');
            passwordToggle.classList.toggle('is-visible', showingPassword);
        });
    </script>
</body>
</html>
