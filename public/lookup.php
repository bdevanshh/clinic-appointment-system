<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['appointment_id'] ?? 0);
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($id <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid appointment reference and email address.';
    } else {
        redirect('/confirmation.php?id=' . $id . '&email=' . urlencode($email));
    }
}

page_header('Lookup Appointment');
?>

<section class="page-title">
    <p class="eyebrow">Lookup</p>
    <h1>Find your appointment</h1>
    <p>Use the reference number from your booking confirmation.</p>
</section>

<?php if ($errors): ?>
    <div class="alert">
        <?php foreach ($errors as $error): ?>
            <p><?= e($error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="form-shell narrow" method="post" action="/lookup.php">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <label>
        Appointment reference
        <input type="number" min="1" name="appointment_id" required>
    </label>
    <label>
        Email
        <input type="email" name="email" autocomplete="email" required>
    </label>
    <button class="button primary" type="submit">Find appointment</button>
</form>

<?php page_footer(); ?>
