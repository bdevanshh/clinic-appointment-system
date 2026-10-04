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

<section class="page-title">
    <p class="eyebrow">Confirmation</p>
    <h1><?= $appointment ? 'Appointment request received' : 'Appointment not found' ?></h1>
</section>

<?php if ($appointment): ?>
    <article class="detail-card">
        <div class="status-row">
            <span class="status status-<?= e($appointment['status']) ?>"><?= e(ucfirst($appointment['status'])) ?></span>
            <span>Reference #<?= (int) $appointment['id'] ?></span>
        </div>
        <dl class="details">
            <div><dt>Patient</dt><dd><?= e($appointment['patient_name']) ?></dd></div>
            <div><dt>Doctor</dt><dd><?= e($appointment['doctor_name']) ?>, <?= e($appointment['specialty']) ?></dd></div>
            <div><dt>Visit</dt><dd><?= e($appointment['service_name']) ?></dd></div>
            <div><dt>Date</dt><dd><?= e(date('F j, Y', strtotime($appointment['appointment_date']))) ?></dd></div>
            <div><dt>Time</dt><dd><?= e(substr($appointment['appointment_time'], 0, 5)) ?></dd></div>
            <div><dt>Room</dt><dd><?= e($appointment['room']) ?></dd></div>
        </dl>
        <a class="button secondary" href="/lookup.php">Look up another appointment</a>
    </article>
<?php else: ?>
    <div class="empty-state">
        <p>We could not find an appointment for that reference and email.</p>
        <a class="button primary" href="/lookup.php">Try lookup</a>
    </div>
<?php endif; ?>

<?php page_footer(); ?>
