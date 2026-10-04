CREATE DATABASE IF NOT EXISTS clinic_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_app;

CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    bio TEXT NOT NULL,
    room VARCHAR(40) NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unique_doctor_name (name),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unique_service_name (name)
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(120) NOT NULL,
    patient_email VARCHAR(160) NOT NULL,
    patient_phone VARCHAR(40) NOT NULL,
    doctor_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    notes TEXT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id),
    UNIQUE KEY unique_doctor_slot (doctor_id, appointment_date, appointment_time)
);

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO doctors (name, specialty, bio, room, starts_at, ends_at) VALUES
('Dr. Anika Shah', 'Family Medicine', 'Primary care for adults and children with a preventive-care focus.', 'A-101', '09:00:00', '16:30:00'),
('Dr. Marcus Lee', 'Cardiology', 'Heart health, blood pressure management, and cardiac risk consultation.', 'B-204', '10:00:00', '17:00:00'),
('Dr. Priya Raman', 'Dermatology', 'Skin, hair, and allergy care with practical treatment plans.', 'C-112', '09:30:00', '15:30:00')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO services (name, description, duration_minutes, price) VALUES
('General Consultation', 'A routine visit for diagnosis, prescriptions, referrals, and follow-up planning.', 30, 49.00),
('Annual Wellness Check', 'A preventive checkup with vitals review and lifestyle guidance.', 45, 79.00),
('Specialist Consultation', 'A focused visit with a specialty doctor for ongoing or new concerns.', 40, 99.00),
('Follow-up Visit', 'A shorter visit for test results, progress checks, and treatment adjustments.', 20, 35.00)
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO admins (name, email, password_hash)
SELECT 'Clinic Admin', 'admin@clinic.test', '$2y$10$QIzYCkuEX3L0LJsaHQlpuOohbsPzL0yLFn1vP.vZyVHtV9Wyu0Mg6'
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE email = 'admin@clinic.test');
