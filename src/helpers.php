<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

migrate();

/**
 * @return array<string, string> version => absolute path
 */
function migration_files(): array
{
    $paths = glob(__DIR__ . '/../db/migrations/*.sql') ?: [];
    sort($paths);

    $files = [];
    foreach ($paths as $path) {
        $files[basename($path, '.sql')] = $path;
    }

    return $files;
}

function applied_migrations(PDO $pdo): array
{
    $versions = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

    return array_fill_keys(array_map('strval', $versions), true);
}

/**
 * Applies any migration not yet recorded in schema_migrations.
 *
 * Serverless platforms start containers concurrently, so the work is
 * serialised with a MySQL advisory lock and re-checked once held.
 */
function migrate(): void
{
    static $ran = false;

    if ($ran) {
        return;
    }
    $ran = true;

    $pdo = db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(190) NOT NULL PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $files = migration_files();
    if (array_diff_key($files, applied_migrations($pdo)) === []) {
        return;
    }

    $acquired = (int) $pdo->query("SELECT GET_LOCK('clinic_migrations', 10)")->fetchColumn();
    if ($acquired !== 1) {
        throw new RuntimeException('Could not acquire the migration lock.');
    }

    try {
        $applied = applied_migrations($pdo);

        foreach ($files as $version => $path) {
            if (isset($applied[$version])) {
                continue;
            }

            $sql = file_get_contents($path);
            if ($sql === false) {
                throw new RuntimeException("Could not read migration {$version}.");
            }

            $pdo->exec($sql);

            $insert = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
            $insert->execute([$version]);
        }
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('clinic_migrations')");
    }
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header("Location: {$path}");
    exit;
}

function current_path(): string
{
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('/admin.php');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * @return array<int, array{label: string, href: string, current: bool}>
 */
function nav_items(): array
{
    $path = current_path();

    return [
        ['label' => 'Home', 'href' => '/', 'current' => $path === '/'],
        ['label' => 'Book', 'href' => '/book.php', 'current' => $path === '/book.php'],
        ['label' => 'Lookup', 'href' => '/lookup.php', 'current' => $path === '/lookup.php'],
        ['label' => 'Admin', 'href' => '/admin.php', 'current' => str_starts_with($path, '/admin')],
    ];
}

/**
 * Inline SVG icon set. Kept local so pages carry no icon-font dependency.
 */
function icon(string $name, string $class = ''): string
{
    $paths = [
        'calendar' => '<rect x="3" y="4.5" width="18" height="17" rx="3"/><path d="M16 2.5v4M8 2.5v4M3 10h18"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 2"/>',
        'check' => '<path d="M20 6.5 9.5 17 4 11.5"/>',
        'shield' => '<path d="M12 22s8-3.8 8-9.6V5.4L12 2.4 4 5.4v7c0 5.8 8 9.6 8 9.6Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
        'verified' => '<path d="m12 2.8 2.3 2.2 3.1-.3.9 3 2.7 1.6-1.3 2.9 1.3 2.9-2.7 1.6-.9 3-3.1-.3L12 21.2l-2.3-2.2-3.1.3-.9-3L3 14.7l1.3-2.9L3 8.9l2.7-1.6.9-3 3.1.3Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20.5 20.5-4-4"/>',
        'arrow-right' => '<path d="M4.5 12h15M13.5 6l6 6-6 6"/>',
        'pin' => '<path d="M20 10.5c0 6-8 11.5-8 11.5s-8-5.5-8-11.5a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10.3" r="2.8"/>',
        'phone' => '<path d="M21.5 17v2.8a2 2 0 0 1-2.2 2 19.6 19.6 0 0 1-8.6-3 19.3 19.3 0 0 1-6-6 19.6 19.6 0 0 1-3-8.7A2 2 0 0 1 3.7 2H6.5a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7.5 9.8a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.9 2.2Z"/>',
        'star' => '<path d="m12 3.5 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 10l6.1-.9Z"/>',
        'users' => '<path d="M16 21v-1.8a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V21"/><circle cx="9" cy="7.5" r="4"/><path d="M22 21v-1.8a4 4 0 0 0-3-3.9M16.5 3.6a4 4 0 0 1 0 7.5"/>',
        'tag' => '<path d="M20.6 13.4 12.4 21.6a2 2 0 0 1-2.8 0l-7.2-7.2a2 2 0 0 1-.6-1.4V4a2 2 0 0 1 2-2h9a2 2 0 0 1 1.4.6l6.4 6.4a2 2 0 0 1 0 2.8Z"/><path d="M7.5 7.5h.01"/>',
        'mail' => '<rect x="2.5" y="5" width="19" height="14" rx="3"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        'building' => '<path d="M4 21V6.5A1.5 1.5 0 0 1 5.5 5h7A1.5 1.5 0 0 1 14 6.5V21M14 11h4.5A1.5 1.5 0 0 1 20 12.5V21M2.5 21h19M7.5 9h3M7.5 13h3M7.5 17h3M17 15h.01M17 18h.01"/>',
        'ticket' => '<path d="M4 8.5A2.5 2.5 0 0 1 6.5 6h11A2.5 2.5 0 0 1 20 8.5v1.2a2.3 2.3 0 0 0 0 4.6v1.2a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 15.5v-1.2a2.3 2.3 0 0 0 0-4.6Z"/><path d="M13 8v8"/>',
    ];

    $body = $paths[$name] ?? null;
    if ($body === null) {
        return '';
    }

    return sprintf(
        '<svg class="icon%s" viewBox="0 0 24 24" aria-hidden="true" focusable="false">%s</svg>',
        $class === '' ? '' : ' ' . $class,
        $body
    );
}

function page_header(string $title, string $variant = ''): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
        <link rel="preload" href="/assets/fonts/inter-latin.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/assets/fonts/plus-jakarta-sans-latin.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="stylesheet" href="/assets/css/styles.css">
    </head>
    <body class="<?= e($variant) ?>">
        <header class="site-header">
            <a class="brand" href="/">
                <span class="brand-mark" aria-hidden="true">+</span>
                <span><?= e(APP_NAME) ?></span>
            </a>
            <nav class="main-nav" aria-label="Primary">
                <?php foreach (nav_items() as $item): ?>
                    <a href="<?= e($item['href']) ?>"<?= $item['current'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        </header>
        <main>
            <?php foreach (get_flashes() as $message): ?>
                <p class="flash" role="status"><?= e($message['message']) ?></p>
            <?php endforeach; ?>
    <?php
}

function page_footer(): void
{
    ?>
        </main>
        <footer class="site-footer">
            <div class="footer-grid">
                <div class="footer-about">
                    <a class="brand" href="/">
                        <span class="brand-mark" aria-hidden="true">+</span>
                        <span><?= e(APP_NAME) ?></span>
                    </a>
                    <p>Same-week appointments with the doctors on duty. Choose a time, pick from whoever is free, and keep your reference number.</p>
                    <p class="kicker">Open Monday to Saturday</p>
                </div>
                <div>
                    <h3>Clinic hours</h3>
                    <ul class="footer-list">
                        <li>Monday &ndash; Saturday &middot; 9:00&nbsp;AM &ndash; 6:00&nbsp;PM</li>
                        <li>Sunday &middot; Closed</li>
                        <li>Appointment lookup &middot; Anytime</li>
                    </ul>
                </div>
                <div>
                    <h3>Navigate</h3>
                    <ul class="footer-list">
                        <?php foreach (nav_items() as $item): ?>
                            <li><a href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="footer-base">
                <span>&copy; <?= e(date('Y')) ?> <?= e(APP_NAME) ?></span>
                <span>Appointments are confirmed by the clinic.</span>
            </div>
        </footer>
        <script src="/assets/js/app.js"></script>
    </body>
    </html>
    <?php
}

function all_doctors(): array
{
    return db()->query('SELECT * FROM doctors WHERE active = 1 ORDER BY name')->fetchAll();
}

function all_services(): array
{
    return db()->query('SELECT * FROM services WHERE active = 1 ORDER BY name')->fetchAll();
}

function appointment_slots(string $start, string $end): array
{
    $slots = [];
    $current = new DateTime($start);
    $last = new DateTime($end);

    while ($current < $last) {
        $slots[] = $current->format('H:i');
        $current->modify('+30 minutes');
    }

    return $slots;
}

function valid_statuses(): array
{
    return ['pending', 'confirmed', 'cancelled', 'completed'];
}

/**
 * Formats a stored HH:MM(:SS) time as a 12-hour clock label.
 */
function format_time(string $time): string
{
    $value = substr(trim($time), 0, 5);
    $parsed = DateTime::createFromFormat('!H:i', $value);

    return $parsed instanceof DateTime ? $parsed->format('g:i A') : $value;
}

/**
 * Finds the next bookable slot for a doctor inside the next week.
 *
 * Sunday is skipped because the clinic is closed. Returns [date, slot] or null.
 *
 * @param array<string, mixed> $doctor
 * @return array{0: string, 1: string}|null
 */
function doctor_next_slot(array $doctor): ?array
{
    static $booked = null;

    $booked ??= db()->prepare(
        "SELECT COUNT(*) FROM appointments
        WHERE doctor_id = :doctor_id
        AND appointment_date = :appointment_date
        AND TIME_FORMAT(appointment_time, '%H:%i') = :appointment_time
        AND status <> 'cancelled'"
    );

    $slots = appointment_slots((string) $doctor['starts_at'], (string) $doctor['ends_at']);
    if ($slots === []) {
        return null;
    }

    $now = new DateTimeImmutable();
    $today = new DateTimeImmutable('today');

    for ($offset = 0; $offset < 8; $offset++) {
        $day = $today->modify("+{$offset} days");
        if ((int) $day->format('N') === 7) {
            continue;
        }

        foreach ($slots as $slot) {
            $moment = $day->setTime((int) substr($slot, 0, 2), (int) substr($slot, 3, 2));
            if ($moment <= $now) {
                continue;
            }

            $booked->execute([
                'doctor_id' => (int) $doctor['id'],
                'appointment_date' => $day->format('Y-m-d'),
                'appointment_time' => $slot,
            ]);

            if ((int) $booked->fetchColumn() === 0) {
                return [$day->format('Y-m-d'), $slot];
            }
        }
    }

    return null;
}

/**
 * Human label for a slot returned by doctor_next_slot().
 *
 * @param array{0: string, 1: string}|null $slot
 */
function slot_label(?array $slot): string
{
    if ($slot === null) {
        return 'No open slot this week';
    }

    [$date, $time] = $slot;
    $day = new DateTimeImmutable($date);
    $clock = format_time($time);

    $today = new DateTimeImmutable('today');
    $difference = (int) $today->diff($day)->format('%r%a');

    return match (true) {
        $difference === 0 => "Today, {$clock}",
        $difference === 1 => "Tomorrow, {$clock}",
        default => $day->format('D, M j') . ", {$clock}",
    };
}
