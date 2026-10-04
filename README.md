# Clinic Appointment System

A clean, framework-free PHP and MySQL clinic appointment system.

## Features

- Public clinic homepage with services and doctors
- Patient appointment booking with validation
- Duplicate doctor/time-slot prevention
- Appointment confirmation lookup
- Admin login and appointment dashboard
- Appointment status updates and cancellation
- Admin doctor directory management
- Docker Compose setup for PHP Apache and MySQL

## Run With Docker

```bash
docker compose up --build
```

Open:

- Website: http://localhost:8080
- MySQL: localhost:3307

Admin login:

- Email: `admin@clinic.test`
- Password: `admin123`

The database seeds three doctors: Anika Shah, Marcus Lee, and Priya Raman. After signing in, use `Manage doctors` on the dashboard to add more doctors and their appointment hours.

## Migrations

`db/migrations/*.sql` are applied automatically when the app boots against a
database, and each is recorded in `schema_migrations` so it runs once.
Concurrent starts are serialised with a MySQL advisory lock, which makes it
safe on platforms that boot several containers at once. Add a new numbered
file for any schema change.

## Project Structure

```text
public/          Web root and pages
public/assets/   CSS and JavaScript
src/             Shared PHP helpers
db/migrations/   Ordered schema migrations
Dockerfile       PHP Apache image
docker-compose.yml
```

## Local PHP Notes

The app expects these environment variables when not using Docker:

- `DB_HOST`
- `DB_PORT` (defaults to `3306`)
- `DB_NAME`
- `DB_USER`
- `DB_PASS`
