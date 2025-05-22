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
    Available boolean default true,
    CONSTRAINT fk_id_service FOREIGN KEY (id_service) REFERENCES Services(id_service)
);

DROP TABLE IF EXISTS Users;
CREATE TABLE Users (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_service INT NOT NULL,
    full_name VARCHAR(30) NOT NULL,
    email VARCHAR(50) NOT NULL UNIQUE,
    password CHAR(255) NOT NULL,
    role ENUM('admin', 'nurse') NOT NULL DEFAULT 'nurse',
    CONSTRAINT fk_id_service_user FOREIGN KEY (id_service) REFERENCES Services(id_service)
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

INSERT INTO Chambres (id_service, Numero_cr, Numero_lit) VALUES
(1, 101, 1),
(1, 101, 2),
(1, 101, 3),
(2, 201, 1),
(3, 301, 1),
(4, 401, 1);

INSERT INTO Users (id_service, full_name, email, password, role) VALUES
(1, 'Alice Martin', 'admin@hospital.com', '$2y$10$mpspSbYfGfY9CfrRr065ueTju3WrZhxZF4vWyQogTYjCrImysP/4K', 'admin'),
(2, 'Bob Nurse', 'bob.n@hospital.com', '$2y$10$.rwWIJpSLbphSLGWBtiBU.qbdax2TwzHAUuF03DeqicX7mEJXcdTC', 'nurse'),
(3, 'Claire Nurse', 'claire.n@hospital.com', 'hashed_password_3', 'nurse');

INSERT INTO Patients (full_name,NIN, age,sex, adress, telephone,groupage) VALUES
('John Doe','054845678', 45,'homme', '123 Main St, Cityville', '0612345678','AB+'),
('Jane Smith','069874268', 30,'homme', '456 Elm St, Townsville', '0623456789','A+'),
('Tom Hanks','023659874', 60,'homme', '789 Oak St, Villagetown', '0634567890','B+');

INSERT INTO Sejour (id_patient, id_chambre, Date_entree, Date_sortiee) VALUES
(1, 1, '2025-05-01 08:00:00', NULL),
(2, 2, '2025-05-05 14:30:00', '2025-05-10 11:00:00'),
(3, 3, '2025-05-07 10:00:00', NULL);

INSERT INTO Prescription (id_sejour, Medicament, Dosage, frequence, instructions) VALUES
(1, 'Amlodipine', 5, 1, 'Take one tablet daily in the morning.'),
(2, 'Paracetamol', 500, 3, 'After meals, for 5 days.'),
(3, 'Metoprolol', 50, 2, 'Twice a day, monitor blood pressure.');

INSERT INTO Suivi (
    id_sejour, id_nurse, etat_santee, tension, temperature, frequence_quardiaque,
    saturation_oxygene, glycemie, Remarque
) VALUES
-- Entry for John Doe
(1, 2, 'Stable', '120/80', 36.7, 72, 98, 5.6, 'Patient is stable. No signs of distress.'),

-- Entry for Jane Smith
(2, 3, 'Fatigue', '110/70', 37.2, 76, 97, 5.2, 'Patient reports mild fatigue, under observation.'),

-- Entry for Tom Hanks
(3, 2, 'Critical', '90/60', 38.4, 100, 90, 7.1, 'High fever and low oxygen level. Immediate attention needed.');