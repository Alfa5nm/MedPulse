# MedPulse: National Population Health Monitoring System

MedPulse is a high-fidelity, database-driven clinical platform designed to monitor patient health at a population level and detect outbreak risks using structured medical data.

## 🏥 Key Features
- **Medical Standard Compliance**: Utilizes **ICD-10** (Diseases), **LOINC** (Vitals), **RxNorm** (Medications), and **SNOMED-CT** (Clinical Terms).
- **NEWS2 Scoring Engine**: Rule-based health risk calculation based on respiratory rate, SpO2, BP, pulse, temperature, and consciousness.
- **Outbreak Detection**: Automated regional data aggregation with threshold-based alerting (Group By/Having).
- **Patient Portal**: Personalized health dashboard with medication adherence tracking and health surveys.
- **Security**: 100% prepared statement coverage using MySQLi.

## 🛠️ Technology Stack
- **Backend**: PHP 8.x
- **Database**: MySQL (MariaDB)
- **Frontend**: Bootstrap 5, FontAwesome 6, Chart.js, Leaflet.js (Heatmaps)

## 🚀 Installation
1. Clone this repository.
2. Import `schema.sql` into your MySQL database.
3. Configure your database credentials in `config/db.php`.
4. Run `database_fix.php` in your browser to finalize the schema hierarchy.

---
*Developed for CSE370: Database Systems*
