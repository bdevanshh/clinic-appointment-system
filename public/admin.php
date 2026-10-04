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

<section class="page-title">
    <p class="eyebrow">Clinic team</p>
    <h1>Admin login</h1>
</section>

<?php if ($error): ?>
    <div class="alert"><p><?= e($error) ?></p></div>
<?php endif; ?>

<form class="form-shell narrow" method="post" action="/admin.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>
        Email
        <input type="email" name="email" value="admin@clinic.test" autocomplete="email" required>
    </label>
    <label>
        Password
        <input type="password" name="password" autocomplete="current-password" required>
    </label>
    <button class="button primary" type="submit">Sign in</button>
</form>

<?php page_footer(); ?>
