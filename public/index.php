<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$doctors = all_doctors();
$services = all_services();

page_header('Home', 'home-page');
?>

<section class="hero">
    <div class="hero-copy">
        <p class="eyebrow">Same-week appointments</p>
        <h1>Book your clinic visit online.</h1>
        <p>Choose a date and time, then pick from the doctors who are free in that slot.</p>
        <div class="hero-actions">
            <a class="button primary" href="/book.php">Book appointment</a>
            <a class="button secondary" href="/lookup.php">Find my appointment</a>
        </div>
    </div>
    <div class="hero-facts">
        <div>
            <strong><?= count($doctors) ?></strong>
            <span>Doctors</span>
        </div>
        <div>
            <strong><?= count($services) ?></strong>
            <span>Visit types</span>
        </div>
        <div>
            <strong>Mon&ndash;Sat</strong>
            <span>9:00&nbsp;AM &ndash; 6:00&nbsp;PM</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <p class="eyebrow">Services</p>
        <h2>Visit types and fees</h2>
    </div>
    <div class="grid cards">
        <?php foreach ($services as $service): ?>
            <article class="card">
                <div class="card-topline">
                    <h3><?= e($service['name']) ?></h3>
                    <span class="badge"><?= (int) $service['duration_minutes'] ?> min</span>
                </div>
                <p><?= e($service['description']) ?></p>
                <p class="card-price">$<?= e(number_format((float) $service['price'], 2)) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section-band">
    <div class="band">
        <div class="section-head">
            <p class="eyebrow">Care team</p>
            <h2>Doctors and specialists</h2>
        </div>
        <div class="grid doctors">
            <?php foreach ($doctors as $doctor): ?>
                <article class="doctor-card">
                    <div class="avatar" aria-hidden="true"><?= e(mb_substr((string) str_replace('Dr. ', '', $doctor['name']), 0, 1)) ?></div>
                    <div>
                        <h3><?= e($doctor['name']) ?></h3>
                        <p class="doctor-specialty"><?= e($doctor['specialty']) ?></p>
                        <p><?= e($doctor['bio']) ?></p>
                        <div class="doctor-meta">
                            <span class="badge">Room <?= e($doctor['room']) ?></span>
                            <span class="badge">
                                <?= e(substr((string) $doctor['starts_at'], 0, 5)) ?>&ndash;<?= e(substr((string) $doctor['ends_at'], 0, 5)) ?>
                            </span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php page_footer(); ?>
