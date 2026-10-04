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
    <div>
        <p class="eyebrow">Welcome, <?= e($_SESSION['admin_name'] ?? 'Admin') ?></p>
        <h1>Appointment dashboard</h1>
    </div>
    <div class="dashboard-actions">
        <a class="button secondary" href="/admin_doctors.php">Manage doctors</a>
        <a class="button secondary" href="/logout.php">Sign out</a>
    </div>
</section>

<section class="stats">
    <?php foreach (valid_statuses() as $item): ?>
        <a class="stat <?= $status === $item ? 'active' : '' ?>" href="/admin_dashboard.php?status=<?= e($item) ?>">
            <span><?= e(ucfirst($item)) ?></span>
            <strong><?= (int) ($counts[$item] ?? 0) ?></strong>
        </a>
    <?php endforeach; ?>
    <a class="stat <?= $status === '' ? 'active' : '' ?>" href="/admin_dashboard.php">
        <span>All</span>
        <strong><?= $totalAppointments ?></strong>
    </a>
</section>

<section class="table-shell">
    <?php if ($appointments): ?>
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Visit</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td>
                            <strong><?= e($appointment['patient_name']) ?></strong>
                            <span><?= e($appointment['patient_email']) ?></span>
                            <span><?= e($appointment['patient_phone']) ?></span>
                        </td>
                        <td>
                            <?= e($appointment['doctor_name']) ?>
                            <span><?= e($appointment['specialty']) ?></span>
                        </td>
                        <td><?= e($appointment['service_name']) ?></td>
                        <td>
                            <?= e(date('M j, Y', strtotime($appointment['appointment_date']))) ?>
                            <span><?= e(substr($appointment['appointment_time'], 0, 5)) ?></span>
                        </td>
                        <td><span class="status status-<?= e($appointment['status']) ?>"><?= e(ucfirst($appointment['status'])) ?></span></td>
                        <td>
                            <form class="inline-form" method="post" action="/admin_update.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="appointment_id" value="<?= (int) $appointment['id'] ?>">
                                <select name="status" aria-label="Appointment status">
                                    <?php foreach (valid_statuses() as $item): ?>
                                        <option value="<?= e($item) ?>" <?= $appointment['status'] === $item ? 'selected' : '' ?>><?= e(ucfirst($item)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="button compact" type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <p>No appointments match this view yet.</p>
        </div>
    <?php endif; ?>
</section>

<?php page_footer(); ?>
