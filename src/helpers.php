<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('Invalid request token.');
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
            <span>&copy; <?= e(date('Y')) ?> <?= e(APP_NAME) ?></span>
            <span>Open Monday to Saturday, 9:00&nbsp;AM &ndash; 6:00&nbsp;PM</span>
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
