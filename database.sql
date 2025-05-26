DROP DATABASE IF EXISTS hospital_management;
CREATE DATABASE hospital_management;

USE hospital_management;

DROP TABLE IF EXISTS Services;
CREATE TABLE Services (
    id_service INT AUTO_INCREMENT PRIMARY KEY,
    nom_service VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS Chambres;
CREATE TABLE Chambres (
    id_chambre INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_service INT NOT NULL,
    Numero_cr INT NOT NULL,
    Numero_lit INT NULL,
    Available BOOLEAN DEFAULT TRUE,
    CONSTRAINT fk_id_service FOREIGN KEY (id_service) REFERENCES Services(id_service)
);

DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_service INT NULL,
    full_name VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password CHAR(255) NOT NULL,
    role ENUM('admin', 'nurse') NOT NULL DEFAULT 'nurse',
    CONSTRAINT fk_id_service_user FOREIGN KEY (id_service) REFERENCES Services(id_service) ON DELETE SET NULL ON UPDATE CASCADE
);

DROP TABLE IF EXISTS Patients;
CREATE TABLE Patients (
    id_patient INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(30) NOT NULL,
    NIN VARCHAR(15) not null,
    age INT NOT NULL,
    sex ENUM('homme','famme') not null,
    adress VARCHAR(100) NOT NULL,
    telephone CHAR(15),
    groupage VARCHAR(3) not null
);

DROP TABLE IF EXISTS Sejour;
CREATE TABLE Sejour (
    id_sejour INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_chambre INT NUll,
    Date_entree TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Date_sortiee TIMESTAMP NULL,
    CONSTRAINT fk_patient_id FOREIGN KEY (id_patient) REFERENCES Patients(id_patient),
    CONSTRAINT fk_chambre_id FOREIGN KEY (id_chambre) REFERENCES Chambres(id_chambre)
);

DROP TABLE IF EXISTS Prescription;
CREATE TABLE Prescription (
    id_prescription INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_sejour int not null,
    Medicament VARCHAR(30) NOT NULL,
    Dosage INT NOT NULL,
    frequence INT NOT NULL,
    instructions VARCHAR(100),
    CONSTRAINT fk_patien_t_id FOREIGN KEY (id_sejour) REFERENCES Sejour(id_sejour)
);

DROP TABLE IF EXISTS Suivi;
CREATE TABLE Suivi(
    id_suivi int not null AUTO_INCREMENT PRIMARY KEY,
    id_sejour int not null,
    id_nurse int not null,
    etat_santee VARCHAR(30) not null,
    tension VARCHAR(20) not null,
    temperature decimal(5,2) not null,  -- Changed from decimal(3,2) to decimal(5,2)
    frequence_quardiaque int not null,
    saturation_oxygene int not null,
    glycemie decimal(3,1) not null,     -- Changed from decimal(2,1) to decimal(3,1)
    poids DECIMAL(5,2) NOT NULL,        -- Added poids (weight)
    taille DECIMAL(4,2) NOT NULL,       -- Added taille (height) in meters
    Remarque TEXT not null,
    Date_observation timestamp default CURRENT_TIMESTAMP,
    CONSTRAINT fk_sejour_i_d FOREIGN key (id_sejour) REFERENCES Sejour(id_sejour),
    CONSTRAINT fk_nurse_i_d FOREIGN key (id_nurse) REFERENCES Users(id)
);

INSERT INTO Services (nom_service) VALUES
('Cardiology'),
('Neurology'),
('Pediatrics'),
('Orthopedics');

INSERT INTO Chambres (id_service, Numero_cr, Numero_lit, Available) VALUES
(1, 101, 1, FALSE),  -- id_chambre = 1 (Occupied by John Doe)
(1, 101, 2, FALSE),  -- id_chambre = 2 (NOW Occupied by Audrey Horne)
(1, 101, 3, FALSE),  -- id_chambre = 3 (Occupied by Tom Hanks)
(1, 102, 4, FALSE),  -- id_chambre = 4 (NOW Occupied by Jane Smith - new sejour)
(2, 201, 1, FALSE),  -- id_chambre = 5 (Service 2, NOW Occupied by Harry S. Truman)
(3, 301, 1, TRUE),   -- id_chambre = 6 (Service 3)
(4, 401, 1, TRUE),   -- id_chambre = 7
(2, 202, 1, FALSE),  -- id_chambre = 8 (For Laura Palmer, Service 2)
(2, 202, 2, TRUE),   -- id_chambre = 9 (Service 2)
(3, 302, 1, FALSE),  -- id_chambre = 10 (For Dale Cooper, Service 3)
(1, 103, 1, TRUE);   -- id_chambre = 11 (Service 1)

INSERT INTO Users (id_service, full_name, email, password, role) VALUES
(NULL, 'Alice Martin', 'admin@hospital.com', '$2y$10$mpspSbYfGfY9CfrRr065ueTju3WrZhxZF4vWyQogTYjCrImysP/4K', 'admin'),
(2, 'Bob Nurse', 'bob.n@hospital.com', '$2y$10$.rwWIJpSLbphSLGWBtiBU.qbdax2TwzHAUuF03DeqicX7mEJXcdTC', 'nurse'),
(3, 'Claire Nurse', 'claire.n@hospital.com', 'hashed_password_3', 'nurse');

INSERT INTO Patients (full_name,NIN, age,sex, adress, telephone,groupage) VALUES
('John Doe','054845678', 45,'homme', '123 Main St, Cityville', '0612345678','AB+'),
('Jane Smith','069874268', 30,'homme', '456 Elm St, Townsville', '0623456789','A+'),
('Tom Hanks','023659874', 60,'homme', '789 Oak St, Villagetown', '0634567890','B+'),
-- New Patients
('Laura Palmer', '071234567', 17, 'famme', '1 Lynch Street, Twin Peaks', '0645678901', 'O-'),
('Dale Cooper', '089876543', 35, 'homme', '1 Federal Plaza, Philadelphia', '0656789012', 'A-'),
-- New Patients (IDs 6, 7)
('Audrey Horne', '091112233', 18, 'famme', 'Great Northern Hotel, Twin Peaks', '0667890123', 'B-'),
('Harry S. Truman', '102223344', 45, 'homme', 'Sheriff Station, Twin Peaks', '0678901234', 'O+');

INSERT INTO Sejour (id_patient, id_chambre, Date_entree, Date_sortiee) VALUES
(1, 1, '2025-05-01 08:00:00', NULL),
(2, 2, '2025-05-05 14:30:00', '2025-05-10 11:00:00'), -- Jane's first sejour (discharged)
(3, 3, '2025-05-07 10:00:00', NULL),
(4, 8, '2025-05-15 10:00:00', NULL), -- Laura Palmer in Room 8
(5, 10, '2025-05-16 11:30:00', NULL), -- Dale Cooper in Room 10
-- New Sejours (IDs 6, 7, 8)
(6, 2, '2025-05-20 09:00:00', NULL), -- Audrey Horne (patient_id=6) in Room 2 (id_chambre=2)
(7, 5, '2025-05-21 14:00:00', NULL), -- Harry S. Truman (patient_id=7) in Room 5 (id_chambre=5)
(2, 4, '2025-05-22 16:00:00', NULL); -- Jane Smith (patient_id=2) new sejour in Room 4 (id_chambre=4)

INSERT INTO Prescription (id_sejour, Medicament, Dosage, frequence, instructions) VALUES
(1, 'Amlodipine', 5, 1, 'Take one tablet daily in the morning.'),
(2, 'Paracetamol', 500, 3, 'After meals, for 5 days.'),
(3, 'Metoprolol', 50, 2, 'Twice a day, monitor blood pressure.'),
(4, 'Lorazepam', 1, 3, 'As needed for anxiety'),
(5, 'Coffee', 1, 24, 'Damn fine cup!'),
-- New Prescriptions (assuming sejour IDs 6, 7, 8 for Audrey, Harry, and Jane's new stay)
(6, 'Lipstick', 1, 1, 'Apply as needed for confidence.'),
(7, 'Doughnuts', 2, 3, 'With coffee, especially in the morning.'),
(8, 'Aspirin', 325, 1, 'For headache after previous stay.');

INSERT INTO Suivi (
    id_sejour, id_nurse, etat_santee, tension, temperature, frequence_quardiaque,
    saturation_oxygene, glycemie, poids, taille, Remarque
) VALUES
-- Entry for John Doe
(1, 2, 'Stable', '120/80', 36.7, 72, 98, 5.6, 70.5, 1.75, 'Patient is stable. No signs of distress.'),
-- Entry for Jane Smith (first sejour)
(2, 3, 'Fatigue', '110/70', 37.2, 76, 97, 5.2, 65.2, 1.65, 'Patient reports mild fatigue, under observation.'),
-- Entry for Tom Hanks
(3, 2, 'Critical', '90/60', 38.4, 100, 90, 7.1, 80.0, 1.80, 'High fever and low oxygen level. Immediate attention needed.'),
-- Entry for Laura Palmer
(4, 2, 'Agitated', '130/85', 37.0, 90, 97, 5.0, 55.0, 1.68, 'Patient experiencing distress.'),
-- Entry for Dale Cooper
(5, 3, 'Good', '120/80', 36.5, 70, 99, 4.5, 75.0, 1.82, 'Enjoying the coffee. Reports seeing a giant.'),
-- New Suivi Entries (assuming sejour IDs 6, 7, 8 and nurse IDs 2 and 3)
(6, 2, 'Sassy', '115/75', 36.8, 80, 98, 5.1, 58.0, 1.70, 'Asking for her father.'),
(7, 3, 'Calm', '125/80', 36.6, 75, 99, 4.8, 80.0, 1.85, 'Wants to know who shot Laura Palmer.'),
(8, 2, 'Recovering', '120/80', 37.0, 70, 98, 5.3, 66.0, 1.65, 'Feeling better this time around.');