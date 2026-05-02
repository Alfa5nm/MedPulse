# MedPulse: National Population Health Monitoring System
**CSE370: Database Systems - Final Project Documentation**

---

## 1. Project Overview
MedPulse is a database-driven clinical platform designed for population-level health monitoring. It bridges the gap between individual patient care and national health surveillance by using standardized medical codes (ICD, LOINC, RxNorm) to detect disease outbreaks in real-time.

---

## 2. System Architecture & "The Connection"
The system is built on a **Three-Tier Architecture**:
1.  **Frontend (Presentation)**: Built with Bootstrap 5 and custom CSS (Glassmorphism). It uses **Chart.js** for trends and **Leaflet.js** for geographic heatmaps.
2.  **Backend (Logic)**: PHP 8.1 handles the business logic, NEWS2 risk calculation, and session management.
3.  **Database (Data)**: A MySQL (MariaDB) database using **InnoDB** for transactional integrity.

### How everything is connected:
*   **Patient to Data**: Every vitals record, diagnosis, and prescription is linked to a `patient_id`.
*   **Data to Analytics**: The **Aggregation Engine** runs a daily `GROUP BY` query on clinical records, filtering by `region_id` to calculate the average health of a sub-district, district, or division.
*   **Analytics to UI**: The dashboard fetches these aggregates and maps them to **GeoJSON** coordinates, causing regions to change color based on their health score.

---

## 3. Technical Implementation Details

### A. Medical Coding Inheritance (Database)
We implemented a **Class Table Inheritance** pattern. All medical codes (ICD-10 for diseases, LOINC for vitals, RxNorm for meds) inherit from a master `code` table. This ensures data normalization and allows for a unified search engine across different medical terminologies.

### B. NEWS2 Scoring Engine
The system uses the **National Early Warning Score (NEWS2)**. When a clinician enters vitals (Pulse, Temp, SpO2, BP), the system uses a complex **SQL CASE Statement** to assign points (0-3) to each metric.
*   **Example**: `CASE WHEN temperature >= 39.1 THEN 3 WHEN temperature >= 38.1 THEN 1 ELSE 0 END`.
*   The sum of these points determines the **Risk Level** (Low, Medium, High, Critical).

### C. Outbreak Velocity Scoring
Unique to MedPulse, the system calculates **"Predictive Velocity."** It compares today's regional health score against the score from 48 hours ago. If the increase is >25%, it flags a **High Momentum Outbreak Risk**, even if the absolute numbers are still low.

---

## 4. Team Division of Labor (Presentation Guide)

### **Alfa (Systems & Data Lead)**
*   **Backend Engineering**: Developed the NEWS2 Scoring Engine and the Regional Aggregation SQL logic.
*   **Advanced Analytics**: Created the 7-day Trend Analysis and the Predictive Outbreak Velocity Score.
*   **System Security**: Implemented full MySQLi Prepared Statement coverage and the Clinical Audit Logging system.
*   **Geographic Visuals**: Integrated the Leaflet.js Heatmap and coordinated the GeoJSON name-matching logic.

### **Sneha (Clinical & UX Lead)**
*   **Clinical Workflows**: Designed the patient registration, clinical profile, and the ICD-10/SNOMED-CT diagnosis modules.
*   **Patient Engagement**: Built the "My Health" portal, including the Medication Adherence bars and Bi-daily survey system.
*   **Core Logic**: Managed the Prescription and Intake Logging systems, ensuring medication data flows correctly.
*   **Design System**: Lead the UI/UX development, creating the premium Glassmorphism aesthetic and responsive layouts.

---

## 5. Demo Walkthrough (Step-by-Step)

### **Phase 1: The Dashboard (The "Big Picture")**
1.  **Explain the Map**: "Notice the heatmap. These colors aren't random; they represent the real-time average NEWS2 score of these districts."
2.  **Explain the Trend**: "Our 7-day trend chart shows how health is moving. If the Velocity Score (the % / 48h) turns red, we know an outbreak is coming before it even hits the news."

### **Phase 2: The Clinician View (Managing Patients)**
1.  **Search**: Go to the Patient Directory and filter by "O+" blood group or "High Risk."
2.  **Diagnosis**: Open a patient profile. Add a Diagnosis using the dual ICD-10 and SNOMED-CT selector.
3.  **Vitals**: Record new vitals (e.g., Temp 39C, Oxygen 90%). Save and show how the **Risk Score** instantly jumps to "Critical."

### **Phase 3: The Patient Portal (Engagement)**
1.  **Login**: Login as the patient.
2.  **Adherence**: Show the Medication Adherence bar. Log a dose of "Acetaminophen" and show how the progress bar moves forward.
3.  **Survey**: Fill out the health questionnaire and submit.

### **Phase 4: Closing (Final Sync)**
1.  Go back to the Dashboard.
2.  Click **"Run Daily Aggregation."**
3.  Show how the Map and the Trend Chart have updated to reflect the new "Critical" data you just entered for that patient.

---
**MedPulse: Monitoring the Pulse of the Nation.**
