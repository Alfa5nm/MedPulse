


CREATE DATABASE IF NOT EXISTS medpulse;
USE medpulse;





CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Doctor', 'HealthWorker', 'Patient') NOT NULL DEFAULT 'HealthWorker',
    patient_id INT NULL,
    is_self_registered BOOLEAN DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_patient FOREIGN KEY (patient_id) REFERENCES patient(patient_id) ON DELETE SET NULL
);

CREATE TABLE region (
    region_id INT AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(100) NOT NULL,
    region_type ENUM('Division','District','Sub-district') NOT NULL,
    parent_region_id INT NULL,
    division VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    sub_district VARCHAR(100) NULL,
    trigger_type VARCHAR(50) NULL,
    trigger_value FLOAT NULL,
    CONSTRAINT fk_region_parent
        FOREIGN KEY (parent_region_id) REFERENCES region(region_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE patient (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    blood_group VARCHAR(5),
    phone VARCHAR(20),
    email VARCHAR(100),
    CONSTRAINT fk_patient_region
        FOREIGN KEY (region_id) REFERENCES region(region_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE questionnaire_template (
    template_id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(120) NOT NULL,
    version_no VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active'
);

CREATE TABLE question (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    question_text VARCHAR(255) NOT NULL,
    question_type ENUM('YesNo','Text','Numeric','MCQ') NOT NULL,
    display_order INT NOT NULL,
    CONSTRAINT fk_question_template
        FOREIGN KEY (template_id) REFERENCES questionnaire_template(template_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE response (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    question_id INT NOT NULL,
    response_value VARCHAR(255) NOT NULL,
    response_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_response_patient
        FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_response_question
        FOREIGN KEY (question_id) REFERENCES question(question_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE code (
    code_id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL,
    code_type ENUM('LOINC', 'ICD10', 'RxNorm', 'SNOMED') NOT NULL,
    code_name VARCHAR(255) NOT NULL,
    unit VARCHAR(50) NULL
);

CREATE TABLE icd_code (
    icd_code_id INT AUTO_INCREMENT PRIMARY KEY,
    code_id INT NULL,
    icd_code VARCHAR(20) NOT NULL UNIQUE,
    disease_name VARCHAR(150) NOT NULL,
    disease_category VARCHAR(100),
    CONSTRAINT fk_icd_master_code FOREIGN KEY (code_id) REFERENCES code(code_id) ON DELETE SET NULL
);

CREATE TABLE snomed_code (
    snomed_code_id INT AUTO_INCREMENT PRIMARY KEY,
    code_id INT NULL,
    snomed_code VARCHAR(30) NOT NULL UNIQUE,
    concept_name VARCHAR(150) NOT NULL,
    concept_type VARCHAR(80),
    CONSTRAINT fk_snomed_master_code FOREIGN KEY (code_id) REFERENCES code(code_id) ON DELETE SET NULL
);

CREATE TABLE loinc_code (
    loinc_code_id INT AUTO_INCREMENT PRIMARY KEY,
    code_id INT NULL,
    loinc_code VARCHAR(30) NOT NULL UNIQUE,
    test_name VARCHAR(150) NOT NULL,
    unit_name VARCHAR(50),
    CONSTRAINT fk_loinc_master_code FOREIGN KEY (code_id) REFERENCES code(code_id) ON DELETE SET NULL
);

CREATE TABLE medication_code (
    medication_code_id INT AUTO_INCREMENT PRIMARY KEY,
    code_id INT NULL,
    rxnorm_code VARCHAR(30) UNIQUE,
    medication_name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150),
    dosage_form VARCHAR(80),
    CONSTRAINT fk_medication_master_code FOREIGN KEY (code_id) REFERENCES code(code_id) ON DELETE SET NULL
);

CREATE TABLE diagnosis (
    diagnosis_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    icd_code_id INT NOT NULL,
    snomed_code_id INT NULL,
    diagnosis_date DATE NOT NULL,
    diagnosis_notes TEXT,
    CONSTRAINT fk_diagnosis_patient
        FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_diagnosis_icd
        FOREIGN KEY (icd_code_id) REFERENCES icd_code(icd_code_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_diagnosis_snomed
        FOREIGN KEY (snomed_code_id) REFERENCES snomed_code(snomed_code_id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE observation (
    observation_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    loinc_code_id INT NOT NULL,
    observation_value DECIMAL(10,2) NOT NULL,
    unit VARCHAR(30),
    is_verified BOOLEAN DEFAULT 1,
    verified_by INT NULL,
    observation_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_observation_patient
        FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_observation_loinc
        FOREIGN KEY (loinc_code_id) REFERENCES loinc_code(loinc_code_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE prescription (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medication_code_id INT NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    duration_days INT NOT NULL,
    prescribed_date DATE NOT NULL,
    instructions TEXT,
    is_verified BOOLEAN DEFAULT 1,
    verified_by INT NULL,
    CONSTRAINT fk_prescription_patient
        FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_prescription_medication
        FOREIGN KEY (medication_code_id) REFERENCES medication_code(medication_code_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE intake_log (
    intake_log_id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    intake_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    intake_status ENUM('Taken','Missed','Delayed') NOT NULL,
    remarks VARCHAR(255),
    CONSTRAINT fk_intakelog_prescription
        FOREIGN KEY (prescription_id) REFERENCES prescription(prescription_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE healthscore (
    health_score_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    score_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    respiratory_score TINYINT NOT NULL DEFAULT 0,
    oxygen_score TINYINT NOT NULL DEFAULT 0,
    systolic_bp_score TINYINT NOT NULL DEFAULT 0,
    pulse_score TINYINT NOT NULL DEFAULT 0,
    temperature_score TINYINT NOT NULL DEFAULT 0,
    consciousness_score TINYINT NOT NULL DEFAULT 0,
    response_score TINYINT NOT NULL DEFAULT 0,
    total_score TINYINT NOT NULL DEFAULT 0,
    risk_level ENUM('Low','Medium','High','Critical') NOT NULL,
    CONSTRAINT fk_healthscore_patient
        FOREIGN KEY (patient_id) REFERENCES patient(patient_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE regionalaggregate (
    aggregate_id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    aggregate_date DATE NOT NULL,
    patient_count INT NOT NULL DEFAULT 0,
    avg_health_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    fever_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    low_oxygen_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    CONSTRAINT uq_region_date UNIQUE (region_id, aggregate_date),
    CONSTRAINT fk_regionalaggregate_region
        FOREIGN KEY (region_id) REFERENCES region(region_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE diseasealert (
    alert_id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    alert_date DATE NOT NULL,
    trigger_type VARCHAR(100) NOT NULL,
    trigger_value DECIMAL(8,2) NOT NULL,
    alert_level ENUM('Low','Medium','High','Critical') NOT NULL,
    remarks VARCHAR(255),
    CONSTRAINT fk_diseasealert_region
        FOREIGN KEY (region_id) REFERENCES region(region_id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action_type VARCHAR(50) NOT NULL, 
    target_entity VARCHAR(50) NULL, 
    target_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);


CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_audit_action ON audit_log(action_type);


CREATE INDEX idx_patient_region ON patient(region_id);
CREATE INDEX idx_observation_patient_datetime ON observation(patient_id, observation_datetime);
CREATE INDEX idx_diagnosis_patient_date ON diagnosis(patient_id, diagnosis_date);
CREATE INDEX idx_prescription_patient_date ON prescription(patient_id, prescribed_date);
CREATE INDEX idx_intakelog_prescription_datetime ON intake_log(prescription_id, intake_datetime);
CREATE INDEX idx_healthscore_patient_datetime ON healthscore(patient_id, score_datetime);
CREATE INDEX idx_alert_region_date ON diseasealert(region_id, alert_date);






INSERT INTO region (region_name, region_type, parent_region_id) VALUES 
('Dhaka Division', 'Division', NULL); 

INSERT INTO region (region_name, region_type, parent_region_id) VALUES 
('Dhaka District', 'District', 1),    
('Gazipur District', 'District', 1),  
('Narayanganj District', 'District', 1); 

INSERT INTO region (region_name, region_type, parent_region_id) VALUES 
('Mirpur', 'Sub-district', 2),        
('Gulshan', 'Sub-district', 2),       
('Tongi', 'Sub-district', 3),         
('Sonargaon', 'Sub-district', 4);     


INSERT INTO patient (region_id, full_name, date_of_birth, gender, blood_group, phone) VALUES 
(5, 'Abdur Rahman', '1985-04-12', 'Male', 'O+', '01711223344'),
(6, 'Fatima Begum', '1992-08-25', 'Female', 'A+', '01822334455'),
(7, 'Kamal Hossain', '1978-11-05', 'Male', 'B-', '01933445566'),
(8, 'Nusrat Jahan', '2001-02-14', 'Female', 'AB+', '01544556677'),
(5, 'Sakib Hasan', '1995-07-30', 'Male', 'O-', '01655667788');


INSERT INTO loinc_code (loinc_code, test_name, unit_name) VALUES 
('8310-5', 'Body temperature', 'C'),
('2708-6', 'Oxygen saturation', '%'),
('8480-6', 'Systolic blood pressure', 'mmHg'),
('8867-4', 'Heart rate', 'beats/min'),
('9279-1', 'Respiratory rate', 'breaths/min');


INSERT INTO icd_code (icd_code, disease_name, disease_category) VALUES 
('J06.9', 'Acute upper respiratory infection, unspecified', 'Respiratory'),
('U07.1', 'COVID-19, virus identified', 'Infectious'),
('I10', 'Essential (primary) hypertension', 'Cardiovascular');



INSERT INTO observation (patient_id, loinc_code_id, observation_value, unit, observation_datetime) VALUES
(1, 1, 36.8, 'C', NOW() - INTERVAL 1 DAY),
(1, 2, 98, '%', NOW() - INTERVAL 1 DAY),
(1, 3, 120, 'mmHg', NOW() - INTERVAL 1 DAY),
(1, 4, 75, 'beats/min', NOW() - INTERVAL 1 DAY);


INSERT INTO observation (patient_id, loinc_code_id, observation_value, unit, observation_datetime) VALUES
(2, 1, 38.5, 'C', NOW() - INTERVAL 2 HOUR),
(2, 2, 91, '%', NOW() - INTERVAL 2 HOUR),
(2, 3, 105, 'mmHg', NOW() - INTERVAL 2 HOUR),
(2, 4, 115, 'beats/min', NOW() - INTERVAL 2 HOUR);


INSERT INTO observation (patient_id, loinc_code_id, observation_value, unit, observation_datetime) VALUES
(3, 1, 37.1, 'C', NOW() - INTERVAL 5 HOUR),
(3, 2, 97, '%', NOW() - INTERVAL 5 HOUR),
(3, 3, 160, 'mmHg', NOW() - INTERVAL 5 HOUR),
(3, 4, 88, 'beats/min', NOW() - INTERVAL 5 HOUR);


INSERT INTO observation (patient_id, loinc_code_id, observation_value, unit, observation_datetime) VALUES
(5, 1, 39.2, 'C', NOW() - INTERVAL 1 HOUR),
(5, 2, 88, '%', NOW() - INTERVAL 1 HOUR),
(5, 3, 85, 'mmHg', NOW() - INTERVAL 1 HOUR),
(5, 4, 130, 'beats/min', NOW() - INTERVAL 1 HOUR);


INSERT INTO healthscore (patient_id, score_datetime, respiratory_score, oxygen_score, systolic_bp_score, pulse_score, temperature_score, consciousness_score, total_score, risk_level) VALUES
(1, NOW() - INTERVAL 1 DAY, 0, 0, 0, 0, 0, 0, 0, 'Low'),
(2, NOW() - INTERVAL 2 HOUR, 1, 3, 0, 1, 1, 0, 6, 'High'),
(3, NOW() - INTERVAL 5 HOUR, 0, 0, 1, 0, 0, 0, 1, 'Low'),
(5, NOW() - INTERVAL 1 HOUR, 2, 3, 3, 2, 2, 0, 12, 'Critical');


INSERT INTO diseasealert (region_id, alert_date, trigger_type, trigger_value, alert_level, remarks) VALUES 
(5, CURDATE(), 'Low Oxygen Rate', 50.00, 'Critical', 'Spike in low oxygen cases detected in Mirpur');


INSERT INTO medication_code (rxnorm_code, medication_name, generic_name, dosage_form) VALUES 
('161', 'Acetaminophen 500 MG Oral Tablet', 'Acetaminophen', 'Tablet'),
('308136', 'Amoxicillin 250 MG Oral Capsule', 'Amoxicillin', 'Capsule'),
('314076', 'Lisinopril 10 MG Oral Tablet', 'Lisinopril', 'Tablet'),
('855332', 'Albuterol 90 MCG/ACTUAT Inhaler', 'Albuterol', 'Inhaler'),
('153010', 'Ibuprofen 400 MG Oral Tablet', 'Ibuprofen', 'Tablet');


INSERT INTO questionnaire_template (template_name, version_no, status) VALUES 
('General COVID-19 Screening', '1.0', 'Active'),
('Mental Health Assessment (PHQ-4)', '1.0', 'Active');


INSERT INTO question (template_id, question_text, question_type, display_order) VALUES 
(1, 'Have you experienced fever in the last 48 hours?', 'YesNo', 1),
(1, 'Do you have a persistent dry cough?', 'YesNo', 2),
(1, 'Are you experiencing any shortness of breath?', 'YesNo', 3),
(1, 'Have you lost your sense of taste or smell recently?', 'YesNo', 4);


INSERT INTO question (template_id, question_text, question_type, display_order) VALUES 
(2, 'Over the last 2 weeks, how often have you been bothered by feeling nervous, anxious or on edge? (0-3)', 'Numeric', 1),
(2, 'Over the last 2 weeks, how often have you been bothered by not being able to stop or control worrying? (0-3)', 'Numeric', 2),
(2, 'Over the last 2 weeks, how often have you felt down, depressed, or hopeless? (0-3)', 'Numeric', 3),
(2, 'Over the last 2 weeks, how often have you had little interest or pleasure in doing things? (0-3)', 'Numeric', 4);

