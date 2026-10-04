<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

<div class="flow">
    <div class="page-head">
        <p class="eyebrow">Lookup</p>
        <h1>Find your appointment</h1>
        <p>Enter the reference number from your booking confirmation and the email address you used.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert" role="alert">
            <strong>Check these before continuing:</strong>
            <?php foreach ($errors as $error): ?><span><?= e($error) ?></span><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form class="form-shell" method="post" action="/lookup.php">
        <div class="form-grid">
            <label class="form-field">
                <span>Appointment reference</span>
                <input type="number" min="1" inputmode="numeric" name="appointment_id" placeholder="e.g. 42" required>
            </label>
            <label class="form-field">
                <span>Email</span>
                <input type="email" name="email" autocomplete="email" required>
            </label>
        </div>
        <p class="hint"><?= icon('ticket', 'icon-sm') ?> The reference number is shown on your confirmation page.</p>
        <button class="button primary" type="submit"><?= icon('search', 'icon-sm') ?> Find appointment</button>
    </form>
</div>

<?php page_footer(); ?>