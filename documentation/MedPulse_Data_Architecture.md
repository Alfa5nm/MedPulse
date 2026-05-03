# MedPulse: Deep Data Flow & Connectivity Architecture

This document provides a line-level technical breakdown of how MedPulse fetches, processes, and stores data using relational database principles.

---

## 1. The Core Connectivity Layer
MedPulse uses a **Synchronous Data Flow** model powered by the **MySQLi Native Driver**.

### Connection Logic (`config/db.php`)
Every module starts by establishing a handshake with the database. We use a centralized configuration to allow for easy deployment in different environments (Localhost vs Docker).

```php
// config/db.php
$host = 'localhost'; // Or 'db' in Docker
$user = 'root';
$pass = '';
$dbname = 'medpulse';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
```

---

## 2. Secure Data Upload (Write Operations)
All "Write" operations (Uploads) use **MySQLi Prepared Statements**. This ensures that user input is never executed as a command, neutralizing SQL Injection attacks.

### Example: Clinical Observation Upload (`add_observation.php`)
When a clinician records vitals, the data flows through this secure pipeline:

```php
// Step 1: Prepare the Template
$stmt = $conn->prepare("INSERT INTO observation (patient_id, loinc_code_id, observation_value, unit, observation_datetime) 
                        VALUES (?, ?, ?, ?, NOW())");

// Step 2: Bind User Data to the Template
$stmt->bind_param("iiss", $patient_id, $loinc_id, $value, $unit);

// Step 3: Execute the Interlinked Action
$stmt->execute();
```

---

## 3. Relational Interlinks (Fetching Data)
MedPulse relies on **Complex Joins** to fetch data across multiple tables. This is how we "interlink" clinical history into a single view.

### The "Deep Search" Query (`patient_details.php`)
To show a patient's profile, we must link the Patient record to their Geographical hierarchy:

```sql
SELECT p.*, CONCAT_WS(' > ', divi.region_name, d.region_name, r.region_name) as full_region_name
FROM patient p 
LEFT JOIN region r ON p.region_id = r.region_id
LEFT JOIN region d ON r.parent_region_id = d.region_id
LEFT JOIN region divi ON d.parent_region_id = divi.region_id
WHERE p.patient_id = ?
```
*   **Logic**: This query uses **Three Left Joins** on the same table (`region`) to climb the hierarchy from Sub-district up to Division.

---

## 4. The Analytics Aggregator (The "Group By" Engine)
The **Heatmap** and **Trend Charts** are powered by a massive aggregation query in `run_aggregation.php`. This query "compresses" individual patient scores into a regional summary.

### Aggregation Query Logic:
```sql
INSERT INTO regionalaggregate (region_id, aggregate_date, patient_count, avg_health_score, fever_rate)
SELECT 
    p.region_id, 
    CURDATE(), 
    COUNT(DISTINCT p.patient_id), 
    AVG(h.total_score),
    (SUM(CASE WHEN o.loinc_code_id = 1 AND o.observation_value >= 38 THEN 1 ELSE 0 END) / COUNT(*)) * 100
FROM patient p
JOIN healthscore h ON p.patient_id = h.patient_id
JOIN observation o ON p.patient_id = o.patient_id
GROUP BY p.region_id;
```
*   **Interlink**: This query connects **Demographics** (Patient) with **Clinical Status** (HealthScore) and **Raw Vitals** (Observation) to produce a regional risk metric.

---

## 5. Master Connection Logic Table

| PHP Module | Connection Method | Key Code Snippet | Interlink Goal |
| :--- | :--- | :--- | :--- |
| **calculate_score.php** | `CASE` Statement | `SUM(CASE WHEN value > threshold THEN 3...)` | Convert raw vitals into a NEWS2 Risk Score. |
| **my_health.php** | `DATEDIFF` | `DATEDIFF(CURDATE(), prescribed_date)` | Calculate **Medication Adherence** over time. |
| **dashboard.php** | `JSON_ENCODE` | `json_encode($mapData)` | Convert SQL results into JS objects for Leaflet.js. |
| **audit_log.php** | `INSERT` | `INSERT INTO audit_log (user_id, action...)` | Forensic tracking of every read/write action. |

---
**Technical Documentation v3.0**
