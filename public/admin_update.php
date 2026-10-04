<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin_dashboard.php');
}

$id = (int) ($_POST['appointment_id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');

if ($id > 0 && in_array($status, valid_statuses(), true)) {
    $stmt = db()->prepare('UPDATE appointments SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);
    flash('success', 'Appointment updated.');
} else {
    flash('error', 'Could not update that appointment.');
}

redirect('/admin_dashboard.php');
