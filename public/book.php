<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$step = (int) ($_POST['step'] ?? $_GET['step'] ?? 1);
$step = $step === 2 ? 2 : 1;
$errors = [];
$old = [
    'patient_name' => '', 'patient_email' => '', 'patient_phone' => '',
    'doctor_id' => '', 'service_id' => '',
    'appointment_date' => trim((string) ($_GET['date'] ?? '')),
    'appointment_time' => trim((string) ($_GET['time'] ?? '')), 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old = array_merge($old, array_intersect_key($_POST, $old));
    $step = (int) ($_POST['step'] ?? 1) === 2 ? 2 : 1;
}

$date = trim((string) $old['appointment_date']);
$time = trim((string) $old['appointment_time']);
$parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
$dateIsValid = $parsedDate instanceof DateTime && $parsedDate->format('Y-m-d') === $date;
$parsedTime = DateTime::createFromFormat('!H:i', $time);
$timeIsValid = $parsedTime instanceof DateTime && $parsedTime->format('H:i') === $time;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 1) {
    if (!$dateIsValid || $date < date('Y-m-d')) $errors[] = 'Please choose today or a future appointment date.';
    if (!$timeIsValid) $errors[] = 'Please choose an appointment time.';
    if ($dateIsValid && $timeIsValid && $date === date('Y-m-d') && $time <= date('H:i')) $errors[] = 'Please choose a future time for today.';
    if (!$errors) redirect('/book.php?step=2&date=' . urlencode($date) . '&time=' . urlencode($time));
}

$availableDoctors = [];
if ($step === 2 && $dateIsValid && $timeIsValid) {
    $doctorStmt = db()->prepare(
        "SELECT d.*
        FROM doctors d
        WHERE d.active = 1
        AND :schedule_time_start >= TIME_FORMAT(d.starts_at, '%H:%i')
        AND :schedule_time_end < TIME_FORMAT(d.ends_at, '%H:%i')
        AND NOT EXISTS (
            SELECT 1 FROM appointments a
            WHERE a.doctor_id = d.id
            AND a.appointment_date = :appointment_date
            AND a.appointment_time = :appointment_time_slot
            AND a.status <> 'cancelled'
        )
        ORDER BY d.name"
    );
    $doctorStmt->execute([
        'schedule_time_start' => $time,
        'schedule_time_end' => $time,
        'appointment_date' => $date,
        'appointment_time_slot' => $time,
    ]);
    $availableDoctors = $doctorStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 2) {
    $name = trim((string) $old['patient_name']);
    $email = trim((string) $old['patient_email']);
    $phone = trim((string) $old['patient_phone']);
    $doctorId = (int) $old['doctor_id'];
    $serviceId = (int) $old['service_id'];
    $notes = trim((string) $old['notes']);

    if (!$dateIsValid || $date < date('Y-m-d')) $errors[] = 'Please choose today or a future appointment date.';
    if (!$timeIsValid) $errors[] = 'Please choose a valid appointment time.';
    if ($dateIsValid && $timeIsValid && $date === date('Y-m-d') && $time <= date('H:i')) $errors[] = 'Please choose a future time for today.';
    if ($name === '' || strlen($name) < 2) $errors[] = 'Please enter the patient name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone === '' || strlen($phone) < 7) $errors[] = 'Please enter a reachable phone number.';
    if ($serviceId <= 0) $errors[] = 'Please choose a visit type.';

    $selectedDoctor = null;
    foreach ($availableDoctors as $doctor) {
        if ((int) $doctor['id'] === $doctorId) {
            $selectedDoctor = $doctor;
            break;
        }
    }
    if (!$selectedDoctor) $errors[] = 'That doctor is no longer available at the selected time. Please choose another doctor or time.';

    $serviceStmt = db()->prepare('SELECT id FROM services WHERE id = :id AND active = 1');
    $serviceStmt->execute(['id' => $serviceId]);
    if (!$serviceStmt->fetch()) $errors[] = 'Please choose a valid visit type.';

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO appointments
                (patient_name, patient_email, patient_phone, doctor_id, service_id, appointment_date, appointment_time, notes)
                VALUES (:name, :email, :phone, :doctor_id, :service_id, :appointment_date, :appointment_time, :notes)'
            );
            $stmt->execute([
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'doctor_id' => $doctorId, 'service_id' => $serviceId,
                'appointment_date' => $date, 'appointment_time' => $time,
                'notes' => $notes === '' ? null : $notes,
            ]);
            $id = (int) db()->lastInsertId();
            flash('success', 'Your appointment request has been received.');
            redirect('/confirmation.php?id=' . $id . '&email=' . urlencode($email));
        } catch (PDOException $exception) {
            $errors[] = $exception->getCode() === '23000'
                ? 'That doctor was just booked. Please choose another available doctor.'
                : 'We could not save the appointment. Please try again.';
        }
    }
}

$services = all_services();
page_header('Book Appointment');
?>

<section class="page-title">
    <p class="eyebrow">Appointments</p>
    <h1><?= $step === 1 ? 'Choose a time' : 'Complete your booking' ?></h1>
    <p><?= $step === 1 ? 'Start with a date and time. We will show only doctors available for that slot.' : 'Select an available doctor, then add the patient details.' ?></p>
</section>

<div class="booking-steps" aria-label="Booking progress">
    <span class="booking-step <?= $step === 1 ? 'active' : 'complete' ?>"><strong>1</strong> Date and time</span>
    <span class="booking-step <?= $step === 2 ? 'active' : '' ?>"><strong>2</strong> Patient and doctor</span>
</div>

<?php if ($errors): ?>
    <div class="alert">
        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($step === 1): ?>
    <form class="form-shell narrow" method="post" action="/book.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="step" value="1">
        <label>Date<input type="date" name="appointment_date" value="<?= e($date) ?>" data-min-today required></label>
        <label>Time
            <select name="appointment_time" required>
                <option value="">Choose a time</option>
                <?php for ($hour = 9; $hour <= 17; $hour++): ?>
                    <?php foreach (['00', '30'] as $minute): ?>
                        <?php $value = sprintf('%02d:%s', $hour, $minute); ?>
                        <option value="<?= e($value) ?>" <?= $time === $value ? 'selected' : '' ?>><?= e($value) ?></option>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </select>
        </label>
        <button class="button primary" type="submit">Find available doctors</button>
    </form>
<?php else: ?>
    <form class="form-shell" method="post" action="/book.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="step" value="2">
        <input type="hidden" name="appointment_date" value="<?= e($date) ?>">
        <input type="hidden" name="appointment_time" value="<?= e($time) ?>">
        <div class="selection-summary">
            <div><span>Date</span><strong><?= e($date) ?></strong></div>
            <div><span>Time</span><strong><?= e($time) ?></strong></div>
            <a href="/book.php">Change</a>
        </div>
        <?php if (!$availableDoctors): ?>
            <div class="empty-state"><p>No doctors are available for this time. Choose another time to continue.</p><a class="button secondary" href="/book.php">Choose another time</a></div>
        <?php else: ?>
            <div class="form-grid">
                <label class="full">Available doctor
                    <select name="doctor_id" required>
                        <option value="">Choose a doctor</option>
                        <?php foreach ($availableDoctors as $doctor): ?>
                            <option value="<?= (int) $doctor['id'] ?>" <?= (int) $old['doctor_id'] === (int) $doctor['id'] ? 'selected' : '' ?>><?= e($doctor['name']) ?> - <?= e($doctor['specialty']) ?>, Room <?= e($doctor['room']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Patient name<input name="patient_name" value="<?= e($old['patient_name']) ?>" autocomplete="name" required></label>
                <label>Email<input type="email" name="patient_email" value="<?= e($old['patient_email']) ?>" autocomplete="email" required></label>
                <label>Phone<input name="patient_phone" value="<?= e($old['patient_phone']) ?>" autocomplete="tel" required></label>
                <label>Visit type
                    <select name="service_id" required>
                        <option value="">Choose a visit type</option>
                        <?php foreach ($services as $service): ?>
                            <option value="<?= (int) $service['id'] ?>" <?= (int) $old['service_id'] === (int) $service['id'] ? 'selected' : '' ?>><?= e($service['name']) ?> ($<?= e(number_format((float) $service['price'], 2)) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="full">Notes<textarea name="notes" rows="4" placeholder="Symptoms, accessibility needs, or anything the clinic should know."><?= e($old['notes']) ?></textarea></label>
            </div>
            <button class="button primary" type="submit">Request appointment</button>
        <?php endif; ?>
    </form>
<?php endif; ?>

<?php page_footer(); ?>
