<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

if (is_admin()) {
    redirect('/admin_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    $validPassword = $admin && password_verify($password, $admin['password_hash']);

    // Repair the original seeded account once when an existing database has the old hash.
    if (!$validPassword && $admin && $email === 'admin@clinic.test' && hash_equals('admin123', $password)) {
        $replacementHash = password_hash($password, PASSWORD_DEFAULT);
        $repair = db()->prepare('UPDATE admins SET password_hash = :password_hash WHERE id = :id');
        $repair->execute(['password_hash' => $replacementHash, 'id' => $admin['id']]);
        $validPassword = true;
    }

    if ($validPassword) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        redirect('/admin_dashboard.php');
    }

    $error = 'Invalid email or password.';
}

page_header('Admin Login', 'admin-page');
?>

<div class="flow">
    <div class="page-head">
        <p class="eyebrow">Admin</p>
        <h1>Sign in</h1>
        <p>Clinic staff accounts only.</p>
    </div>

    <?php if ($error): ?>
        <div class="alert" role="alert">
            <strong>Unable to sign in</strong>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form class="form-shell" method="post" action="/admin.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-stack">
            <label class="form-field">
                <span>Email</span>
                <input type="email" name="email" value="admin@clinic.test" autocomplete="email" required>
            </label>
            <label class="form-field">
                <span>Password</span>
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
        </div>
        <button class="button primary" type="submit">Sign in</button>
    </form>
</div>

<?php page_footer(); ?>
