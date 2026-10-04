<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
$email = trim((string) ($_GET['email'] ?? ''));

$stmt = db()->prepare(
    'SELECT a.*, d.name AS doctor_name, d.specialty, d.room, s.name AS service_name, s.duration_minutes, s.price
    FROM appointments a
    JOIN doctors d ON d.id = a.doctor_id
    JOIN services s ON s.id = a.service_id
    WHERE a.id = :id AND a.patient_email = :email'
);
$stmt->execute(['id' => $id, 'email' => $email]);
$appointment = $stmt->fetch();

page_header('Appointment Confirmation');
?>

<div class="flow">
    <div class="page-head">
        <p class="eyebrow">Confirmation</p>
        <h1><?= $appointment ? 'Appointment request received' : 'Appointment not found' ?></h1>
        <?php if ($appointment): ?>
            <p>The clinic will confirm this request. Keep the reference number to check its status later.</p>
        <?php endif; ?>
    </div>

    <?php if ($appointment): ?>
        <article class="detail-card">
            <div class="detail-head">
                <span class="status status-<?= e($appointment['status']) ?>">
                    <?php if ($appointment['status'] === 'cancelled'): ?><?php else: ?><span class="dot" aria-hidden="true"></span><?php endif; ?>
                    <?= e(ucfirst($appointment['status'])) ?>
                </span>
                <span class="detail-ref">Reference #<?= (int) $appointment['id'] ?></span>
            </div>
            <dl class="details">
                <div><dt><?= icon('users', 'icon-sm') ?> Patient</dt><dd><?= e($appointment['patient_name']) ?></dd></div>
                <div><dt><?= icon('verified', 'icon-sm') ?> Doctor</dt><dd><?= e($appointment['doctor_name']) ?></dd></div>
                <div><dt><?= icon('building', 'icon-sm') ?> Specialty</dt><dd><?= e($appointment['specialty']) ?></dd></div>
                <div><dt><?= icon('pin', 'icon-sm') ?> Room</dt><dd><?= e($appointment['room']) ?></dd></div>
                <div><dt><?= icon('calendar', 'icon-sm') ?> Date</dt><dd><?= e(date('F j, Y', strtotime($appointment['appointment_date']))) ?></dd></div>
                <div><dt><?= icon('clock', 'icon-sm') ?> Time</dt><dd><?= e(format_time((string) $appointment['appointment_time'])) ?></dd></div>
                <div><dt><?= icon('ticket', 'icon-sm') ?> Visit</dt><dd><?= e($appointment['service_name']) ?></dd></div>
                <div><dt><?= icon('tag', 'icon-sm') ?> Fee</dt><dd>$<?= e(number_format((float) $appointment['price'], 2)) ?> &middot; <?= (int) $appointment['duration_minutes'] ?> min</dd></div>
            </dl>
            <div class="detail-actions">
                <a class="button primary" href="/lookup.php">Look up another appointment</a>
                <a class="button secondary" href="/book.php">Book a new appointment</a>
            </div>
        </article>
    <?php else: ?>
        <div class="panel">
            <div class="empty-state">
                <p>No appointment matches that reference and email</p>
                <p class="hint">Check both values and try again. The reference number is on your booking confirmation.</p>
                <a class="button primary" href="/lookup.php">Try again</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php page_footer(); ?>
