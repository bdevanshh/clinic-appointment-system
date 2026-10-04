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

<div class="flow wide">
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
                <span class="status status-<?= e($appointment['status']) ?>"><?= e(ucfirst($appointment['status'])) ?></span>
                <span class="detail-ref">Reference #<?= (int) $appointment['id'] ?></span>
            </div>
            <dl class="details">
                <div><dt>Patient</dt><dd><?= e($appointment['patient_name']) ?></dd></div>
                <div><dt>Doctor</dt><dd><?= e($appointment['doctor_name']) ?></dd></div>
                <div><dt>Specialty</dt><dd><?= e($appointment['specialty']) ?></dd></div>
                <div><dt>Room</dt><dd><?= e($appointment['room']) ?></dd></div>
                <div><dt>Date</dt><dd><?= e(date('F j, Y', strtotime($appointment['appointment_date']))) ?></dd></div>
                <div><dt>Time</dt><dd><?= e(substr($appointment['appointment_time'], 0, 5)) ?></dd></div>
                <div><dt>Visit</dt><dd><?= e($appointment['service_name']) ?></dd></div>
                <div><dt>Fee</dt><dd>$<?= e(number_format((float) $appointment['price'], 2)) ?> &middot; <?= (int) $appointment['duration_minutes'] ?> min</dd></div>
            </dl>
            <div class="detail-actions">
                <a class="button primary" href="/lookup.php">Look up another appointment</a>
                <a class="button secondary" href="/book.php">Book a new appointment</a>
            </div>
        </article>
    <?php else: ?>
        <div class="empty-state">
            <p>No appointment matches that reference and email</p>
            <p class="hint">Check both values and try again. The reference number is on your booking confirmation.</p>
            <a class="button primary" href="/lookup.php">Try again</a>
        </div>
    <?php endif; ?>
</div>

<?php page_footer(); ?>
