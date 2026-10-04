<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

require_admin();

$errors = [];
$old = [
    'name' => '',
    'specialty' => '',
    'bio' => '',
    'room' => '',
    'starts_at' => '09:00',
    'ends_at' => '17:00',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_merge($old, array_intersect_key($_POST, $old));

    $name = trim((string) $old['name']);
    $specialty = trim((string) $old['specialty']);
    $bio = trim((string) $old['bio']);
    $room = trim((string) $old['room']);
    $startsAt = trim((string) $old['starts_at']);
    $endsAt = trim((string) $old['ends_at']);
    $start = DateTime::createFromFormat('!H:i', $startsAt);
    $end = DateTime::createFromFormat('!H:i', $endsAt);

    if ($name === '' || strlen($name) < 2) {
        $errors[] = 'Enter the doctor name.';
    }
    if ($specialty === '') {
        $errors[] = 'Enter a specialty.';
    }
    if ($bio === '') {
        $errors[] = 'Add a short doctor bio.';
    }
    if ($room === '') {
        $errors[] = 'Enter a room number.';
    }
    if (!$start || !$end || $start->format('H:i') !== $startsAt || $end->format('H:i') !== $endsAt || $startsAt >= $endsAt) {
        $errors[] = 'Choose a valid schedule where the start time is before the end time.';
    }

    if (!$errors) {
        try {
            $stmt = db()->prepare(
                'INSERT INTO doctors (name, specialty, bio, room, starts_at, ends_at)
                VALUES (:name, :specialty, :bio, :room, :starts_at, :ends_at)'
            );
            $stmt->execute([
                'name' => $name,
                'specialty' => $specialty,
                'bio' => $bio,
                'room' => $room,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);
            flash('success', 'Doctor added to the clinic directory.');
            redirect('/admin_doctors.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'A doctor with that name already exists.';
            } else {
                $errors[] = 'We could not add the doctor. Please try again.';
            }
        }
    }
}

$doctors = db()->query('SELECT * FROM doctors ORDER BY active DESC, name')->fetchAll();

page_header('Manage Doctors', 'admin-page');
?>

<section class="dashboard-head">
    <div class="page-head">
        <p class="eyebrow">Care team</p>
        <h1>Manage doctors</h1>
        <p>New doctors appear on the public booking form as soon as they are added.</p>
    </div>
    <a class="button secondary" href="/admin_dashboard.php">Back to appointments</a>
</section>

<?php if ($errors): ?>
    <div class="alert" role="alert">
        <strong>Check these before saving:</strong>
        <?php foreach ($errors as $error): ?><span><?= e($error) ?></span><?php endforeach; ?>
    </div>
<?php endif; ?>

<section class="admin-columns">
    <form class="form-shell" method="post" action="/admin_doctors.php">
        <h2>Add a doctor</h2>
        <p class="form-intro">Set the hours they are available for. Booking only offers slots inside this window.</p>
        <div class="form-grid">
            <label class="form-field">
                <span>Name</span>
                <input name="name" value="<?= e($old['name']) ?>" placeholder="Dr. Jordan Kim" required>
            </label>
            <label class="form-field">
                <span>Specialty</span>
                <input name="specialty" value="<?= e($old['specialty']) ?>" placeholder="Internal Medicine" required>
            </label>
            <label class="form-field">
                <span>Room</span>
                <input name="room" value="<?= e($old['room']) ?>" placeholder="D-301" required>
            </label>
            <label class="form-field">
                <span>Available from</span>
                <input type="time" name="starts_at" value="<?= e($old['starts_at']) ?>" required>
            </label>
            <label class="form-field">
                <span>Available until</span>
                <input type="time" name="ends_at" value="<?= e($old['ends_at']) ?>" required>
            </label>
            <label class="form-field full">
                <span>Bio</span>
                <textarea name="bio" rows="4" placeholder="What this doctor helps patients with." required><?= e($old['bio']) ?></textarea>
            </label>
        </div>
        <button class="button primary" type="submit">Add doctor</button>
    </form>

    <section class="table-shell" data-scrollable="false" aria-label="Doctor directory">
        <p class="scroll-hint">Scroll the table sideways to see every column.</p>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Doctor</th>
                        <th scope="col">Room</th>
                        <th scope="col">Hours</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td>
                                <strong><?= e($doctor['name']) ?></strong>
                                <span class="sub"><?= e($doctor['specialty']) ?></span>
                            </td>
                            <td><?= e($doctor['room']) ?></td>
                            <td class="when">
                                <?= e(format_time((string) $doctor['starts_at'])) ?>&ndash;<?= e(format_time((string) $doctor['ends_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<?php page_footer(); ?>
