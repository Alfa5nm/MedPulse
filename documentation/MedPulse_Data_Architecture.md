# MedPulse: Data Flow & Connectivity Architecture

This document provides a technical breakdown of how MedPulse fetches, processes, and stores data, and how the relational database tables are interlinked.

---

## 1. The Core Connectivity Layer
MedPulse uses a **Synchronous Data Flow** model powered by the **MySQLi Native Driver**.

*   **Connection Point**: `config/db.php`. Every PHP file includes this to establish a persistent connection to the `medpulse` database.
*   **Security Layer**: All "Write" operations (Uploads) use **Prepared Statements** (`$stmt->prepare()`). This prevents SQL injection by separating the query logic from the user data.

---

## 2. Master Connection & Data Flow Table

| PHP Module | Action Type | Primary Table(s) | Interlinked Table(s) | Update Logic |
| :--- | :--- | :--- | :--- | :--- |
| **register.php** | **Upload** | `users` | `patient` | Creates a User record; links to `patient_id` if registering as a patient. |
| **add_observation.php** | **Upload** | `observation` | `loinc_code`, `patient` | Records vitals; triggers NEWS2 calculation in `calculate_score.php`. |
| **calculate_score.php** | **Upload** | `healthscore` | `observation` | Aggregates last 6 vital values into a single risk score for a patient. |
| **add_diagnosis.php** | **Upload** | `diagnosis` | `icd_code`, `snomed_code` | Links a disease code to a patient; supports dual coding standards. |
| **add_prescription.php** | **Upload** | `prescription`| `medication_code` | Stores medication schedule; linked via `medication_code_id`. |
| **record_intake.php** | **Upload** | `intake_log` | `prescription` | Updates the "Taken" status for a specific prescription. |
| **run_aggregation.php** | **Fetch/Write**| `regionalaggregate`| `patient`, `healthscore` | **Complex Query**: Aggregates patient scores by `region_id` to create heatmaps. |
| **dashboard.php** | **Fetch** | `regionalaggregate`| `region`, `diseasealert` | Reads the latest aggregated scores to render the Map and Trend charts. |
| **patient_details.php** | **Fetch** | `patient` | `diagnosis`, `observation` | Joins 5+ tables to display a complete clinical history on one page. |
| **my_health.php** | **Fetch** | `patient` | `intake_log`, `prescription`| Calculates **Adherence %** by comparing logs vs duration since prescribed. |

---

## 3. How the Database is Interlinked (Relational Logic)

The database is built on **Referential Integrity**. This means if you delete a patient, their history is handled safely.

### A. The Geographic Chain (Hierarchy)
*   **Link**: `region.parent_region_id` → `region.region_id` (Self-join).
*   **Logic**: Every patient is linked to a **Sub-district**. That sub-district is linked to a **District**, which is linked to a **Division**.
*   **Outcome**: This allows us to "drill up." We can see health data for a tiny village or an entire province using the same query.

### B. The Clinical Chain (Terminology)
*   **Link**: `icd_code`, `snomed_code`, and `loinc_code` all inherit from the master `code` table.
*   **Logic**: A **Diagnosis** doesn't store a disease name; it stores a **Foreign Key** to the code dictionary. This ensures that "COVID-19" is spelled the same way across 1 million records.

### C. The Risk Chain (Analytics)
*   **Link**: `observation` → `healthscore` → `regionalaggregate` → `diseasealert`.
*   **Logic**:
    1.  **Vitals** are uploaded.
    2.  **HealthScore** is computed.
    3.  **Aggregator** calculates the average Score for a Region.
    4.  **Alert Engine** checks if that Avg Score > Threshold.
    5.  **Alert** is uploaded if the condition is met.

---

## 4. Data Lifecycle Example (The "Vitals" Journey)

1.  **Fetch**: `add_observation.php` fetches the list of **LOINC** codes so the doctor can pick "Heart Rate."
2.  **Upload**: Doctor enters "85 bpm." The data is uploaded to `observation` table.
3.  **Interlink**: The code calculates the NEWS2 score. It fetches the patient's age and previous vitals from the `patient` and `observation` tables to see if the status is improving or worsening.
4.  **Refresh**: `dashboard.php` fetches the new average for that patient's region, and the **Heatmap** color changes from Green to Yellow on the next refresh.

---
**MedPulse Data Architecture v2.1**
