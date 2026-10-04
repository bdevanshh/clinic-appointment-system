<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

require_admin();

$status = $_GET['status'] ?? '';
$allowedStatuses = valid_statuses();
$params = [];
$where = '';

if (is_string($status) && in_array($status, $allowedStatuses, true)) {
    $where = 'WHERE a.status = :status';
    $params['status'] = $status;
}

$stmt = db()->prepare(
    "SELECT a.*, d.name AS doctor_name, d.specialty, s.name AS service_name
    FROM appointments a
    JOIN doctors d ON d.id = a.doctor_id
    JOIN services s ON s.id = a.service_id
    {$where}
    ORDER BY a.appointment_date ASC, a.appointment_time ASC"
);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$counts = db()->query(
    "SELECT status, COUNT(*) AS total
    FROM appointments
    GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$totalAppointments = array_sum(array_map('intval', $counts));

page_header('Admin Dashboard', 'admin-page');
?>

<section class="dashboard-head">
    <div class="page-head">
        <h1>Appointments</h1>
        <p>Signed in as <?= e($_SESSION['admin_name'] ?? 'Admin') ?>. Confirm, complete, or cancel each request below.</p>
    </div>
    <div class="dashboard-actions">
        <a class="button secondary" href="/admin_doctors.php">Manage doctors</a>
        <a class="button secondary" href="/logout.php">Sign out</a>
    </div>
</section>

<section class="stats" aria-label="Filter by status">
    <?php foreach (valid_statuses() as $item): ?>
        <a class="stat" href="/admin_dashboard.php?status=<?= e($item) ?>"<?= $status === $item ? ' aria-current="true"' : '' ?>>
            <span><?= e(ucfirst($item)) ?></span>
            <strong><?= (int) ($counts[$item] ?? 0) ?></strong>
        </a>
    <?php endforeach; ?>
    <a class="stat" href="/admin_dashboard.php"<?= $status === '' ? ' aria-current="true"' : '' ?>>
        <span>All</span>
        <strong><?= $totalAppointments ?></strong>
    </a>
</section>

<section class="table-shell" data-scrollable="false">
    <p class="scroll-hint">Scroll the table sideways to see every column.</p>
    <div class="table-scroll">
        <?php if ($appointments): ?>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Patient</th>
                        <th scope="col">Doctor</th>
                        <th scope="col">Visit</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col">Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <strong><?= e($appointment['patient_name']) ?></strong>
                                <span class="sub"><?= e($appointment['patient_email']) ?></span>
                                <span class="sub"><?= e($appointment['patient_phone']) ?></span>
                            </td>
                            <td>
                                <strong><?= e($appointment['doctor_name']) ?></strong>
                                <span class="sub"><?= e($appointment['specialty']) ?></span>
                            </td>
                            <td><?= e($appointment['service_name']) ?></td>
                            <td class="when">
                                <?= e(date('M j, Y', strtotime($appointment['appointment_date']))) ?>
                                <span class="sub"><?= e(format_time((string) $appointment['appointment_time'])) ?></span>
                            </td>
                            <td><span class="status status-<?= e($appointment['status']) ?>"><?= e(ucfirst($appointment['status'])) ?></span></td>
                            <td>
                                <form class="inline-form" method="post" action="/admin_update.php">
                                    <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                    <label class="sr-only" for="status-<?= (int) $appointment['id'] ?>">Status for <?= e($appointment['patient_name']) ?></label>
                                    <select id="status-<?= (int) $appointment['id'] ?>" name="status">
                                        <?php foreach (valid_statuses() as $item): ?>
                                            <option value="<?= e($item) ?>" <?= $appointment['status'] === $item ? 'selected' : '' ?>><?= e(ucfirst($item)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="button secondary" type="submit">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No appointments <?= $status === '' ? 'yet' : 'with that status' ?></p>
                <p class="hint"><?= $status === ''
                    ? 'New requests appear here as soon as a patient books.'
                    : 'Try the All filter to see every request.' ?></p>
                <?php if ($status !== ''): ?>
                    <a class="button secondary" href="/admin_dashboard.php">Show all appointments</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php page_footer(); ?>
