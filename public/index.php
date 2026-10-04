<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/helpers.php';

$doctors = all_doctors();
$services = all_services();

page_header('Home', 'home-page');
?>

<section class="hero">
    <div class="shell hero-grid">
        <div class="hero-copy">
            <p class="eyebrow">Same-week appointments</p>
            <h1>Book your clinic visit online, without the phone queue.</h1>
            <p>Pick a date and time, then choose from the doctors who are actually free in that slot. Your request reaches the clinic the moment you send it.</p>
            <ul class="hero-points">
                <li><?= icon('check') ?> No account needed</li>
                <li><?= icon('shield') ?> Details stay between you and the clinic</li>
            </ul>
            <div class="hero-actions">
                <a class="button primary" href="/book.php">Book appointment <?= icon('arrow-right', 'icon-sm') ?></a>
                <a class="button secondary" href="/lookup.php">Find my appointment</a>
            </div>
        </div>

        <form class="launcher" method="get" action="/book.php">
            <div class="launcher-head">
                <h2>Book an appointment</h2>
                <span class="launcher-icon" aria-hidden="true"><?= icon('calendar') ?></span>
            </div>
            <input type="hidden" name="step" value="1">
            <div class="form-grid">
                <label class="form-field full">
                    <span>Date</span>
                    <input type="date" name="date" data-min-today required>
                </label>
                <label class="form-field full">
                    <span>Time</span>
                    <select name="time" required>
                        <option value="">Choose a time</option>
                        <?php for ($hour = 9; $hour <= 17; $hour++): ?>
                            <?php foreach (['00', '30'] as $minute): ?>
                                <?php $value = sprintf('%02d:%s', $hour, $minute); ?>
                                <option value="<?= e($value) ?>"><?= e(format_time($value)) ?></option>
                            <?php endforeach; ?>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>

            <p class="launcher-note">
                <?= icon('clock', 'icon-sm') ?>
                <span><strong><?= count($doctors) ?> doctors</strong> take appointments in 30-minute slots between 9:00&nbsp;AM and 6:00&nbsp;PM.</span>
            </p>
            <button class="button primary" type="submit">Find available doctors</button>
        </form>
    </div>
</section>

<section class="section-band">
    <div class="shell metrics">
        <div class="metric" data-tone="primary">
            <strong><?= count($doctors) ?></strong>
            <span>Doctors on duty</span>
        </div>
        <div class="metric">
            <strong><?= count($services) ?></strong>
            <span>Visit types</span>
        </div>
        <div class="metric" data-tone="secondary">
            <strong><?= icon('clock') ?> 30 min</strong>
            <span>Standard slot</span>
        </div>
        <div class="metric">
            <strong>Mon&ndash;Sat</strong>
            <span>Clinic hours</span>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="section-head">
            <h2>Care administration without friction or anxiety.</h2>
            <p>Medical visits need focus, not paperwork. Every step below keeps you on the same short path to a booked slot.</p>
        </div>
        <div class="grid features">
            <article class="feature">
                <div>
                    <span class="feature-icon" aria-hidden="true"><?= icon('calendar') ?></span>
                    <h3>Availability you can trust</h3>
                    <p>Step one is a date and a time. The booking form only shows doctors whose working hours cover that slot and who have no visit booked in it.</p>
                </div>
                <a class="feature-link" href="/book.php">See open slots <?= icon('arrow-right', 'icon-sm') ?></a>
            </article>
            <article class="feature">
                <div>
                    <span class="feature-icon" aria-hidden="true"><?= icon('users') ?></span>
                    <h3>Pick the doctor you want</h3>
                    <p>Each doctor lists their specialty, room, and in-clinic hours, so you can choose from real information instead of guessing.</p>
                </div>
                <a class="feature-link" href="/book.php">Browse doctors <?= icon('arrow-right', 'icon-sm') ?></a>
            </article>
            <article class="feature">
                <div>
                    <span class="feature-icon" data-tone="secondary" aria-hidden="true"><?= icon('search') ?></span>
                    <h3>Retrieve your booking any time</h3>
                    <p>Every request gets a reference number. Look it up later with the email you booked with to check the current status.</p>
                </div>
                <a class="feature-link" href="/lookup.php">Find my appointment <?= icon('arrow-right', 'icon-sm') ?></a>
            </article>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="split-head">
            <div>
                <p class="eyebrow">Visit types</p>
                <h2>Consultations and fees</h2>
            </div>
            <span class="hint">Fees are confirmed when the clinic approves your request.</span>
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
    </div>
</section>

<section class="section-band">
    <div class="shell section">
        <div class="split-head">
            <div>
                <p class="eyebrow">Care team</p>
                <h2>Doctors and specialists</h2>
            </div>
            <a class="button secondary" href="/book.php">Start a booking</a>
        </div>
        <div class="grid doctors">
            <?php foreach ($doctors as $doctor): ?>
                <article class="doctor-card">
                    <div>
                        <div class="doctor-head">
                            <span class="avatar" aria-hidden="true"><?= e(mb_substr((string) str_replace('Dr. ', '', $doctor['name']), 0, 1)) ?></span>
                            <div>
                                <div class="doctor-name">
                                    <h3><?= e($doctor['name']) ?></h3>
                                    <?= icon('verified', 'icon-sm') ?>
                                </div>
                                <p class="doctor-specialty"><?= e($doctor['specialty']) ?></p>
                            </div>
                        </div>
                        <p class="doctor-about"><?= e($doctor['bio']) ?></p>
                        <div class="doctor-facts">
                            <p class="doctor-fact">
                                <span>Room</span>
                                <strong><?= e($doctor['room']) ?></strong>
                            </p>
                            <p class="doctor-fact">
                                <span>In clinic</span>
                                <strong><?= e(format_time((string) $doctor['starts_at'])) ?> &ndash; <?= e(format_time((string) $doctor['ends_at'])) ?></strong>
                            </p>
                        </div>
                    </div>
                    <div class="doctor-foot">
                        <span class="doctor-slot">
                            <span class="kicker">Earliest slot</span>
                            <strong><span class="dot" aria-hidden="true"></span> <?= e(slot_label(doctor_next_slot($doctor))) ?></strong>
                        </span>
                        <a class="button primary small" href="/book.php">Book visit</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="shell">
        <div class="info-card">
            <div>
                <p class="eyebrow">Before you arrive</p>
                <h2>Everything you need is on the booking form.</h2>
                <p class="form-intro">Name, email, a reachable phone number, the visit type, and anything the doctor should know before you arrive. Notes are optional.</p>
                <div class="info-points">
                    <div class="info-point">
                        <strong><?= icon('clock', 'icon-sm') ?> Requests are reviewed by the clinic</strong>
                        <p>Your booking starts as pending. The clinic confirms it, and the status shows on your confirmation page.</p>
                    </div>
                    <div class="info-point">
                        <strong><?= icon('ticket', 'icon-sm') ?> Keep your reference number</strong>
                        <p>It appears on the confirmation page straight after you submit the form.</p>
                    </div>
                </div>
            </div>
            <div class="hours-card">
                <div class="hours-head">
                    <strong><?= icon('building') ?> Clinic hours</strong>
                    <span class="badge" data-tone="secondary">Open</span>
                </div>
                <ul class="hours-list">
                    <li><span>Monday &ndash; Saturday</span><strong class="open">9:00&nbsp;AM &ndash; 6:00&nbsp;PM</strong></li>
                    <li><span>Sunday</span><strong>Closed</strong></li>
                    <li><span>Appointment lookup</span><strong>Anytime</strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="shell">
        <div class="cta-banner">
            <h2>Need to see a doctor today? Start in two steps.</h2>
            <p>Skip the phone queue. Choose a date and time, pick a free doctor, and the clinic gets your request right away.</p>
            <div class="cta-actions">
                <a class="button on-light" href="/book.php">Book an appointment <?= icon('arrow-right', 'icon-sm') ?></a>
                <a class="button quiet" href="/lookup.php"><?= icon('search', 'icon-sm') ?> Find existing booking</a>
            </div>
        </div>
    </div>
</section>

<?php page_footer(); ?>