# MedPulse: Viva Technical Cheat Sheet

Use this guide to handle technical questions during your oral examination.

---

## 🚀 Top 5 "Killer" Questions & Expert Answers

### 1. "Why did you use Class Table Inheritance for medical codes?"
*   **Answer**: "To maintain **Referential Integrity** and **Normalization**. Instead of repeating code attributes in every table, we have a master `code` table. This allows us to scale. If we want to add a new standard like 'Gene Sequences' tomorrow, we just add a child table to the `code` parent without breaking the existing architecture."

### 2. "Is your database in 3rd Normal Form (3NF)?"
*   **Answer**: "Yes. Every non-key attribute is dependent on the primary key, the whole key, and nothing but the key. For example, in the `patient` table, we store the `region_id` but not the `region_name`. The name is fetched via a Join. This eliminates **Transitive Dependencies** and prevents **Update Anomalies**."

### 3. "How do you handle Concurrent Transactions? (ACID)"
*   **Answer**: "We use the **InnoDB Engine** which supports ACID properties. In `add_questionnaire.php`, we use `begin_transaction()` and `commit()`. If the system crashes mid-way through saving 10 questions, the **Atomicity** property ensures that either *all* questions are saved or *none* are, preventing partial data corruption."

### 4. "Explain the NEWS2 Logic in SQL."
*   **Answer**: "It's a **Rule-Based Evaluation**. We use a `CASE` statement inside a `SELECT`. This is more efficient than doing it in PHP because the database engine can process thousands of records in parallel before sending the final 'Score' to the application."

### 5. "What is your strategy for SQL Injection?"
*   **Answer**: "**Parameterized Queries**. We never concatenate strings like `$sql = "SELECT ... WHERE id = " . $id`. We use `prepare()` and `bind_param()`. This tells the MySQL engine to treat the input strictly as a 'literal value' and never as 'executable code'."

---

## 💎 The "Showstopper" Query
*If the professor asks for your most complex SQL, show this one (from `run_aggregation.php`):*

```sql
INSERT INTO regionalaggregate (region_id, aggregate_date, patient_count, avg_health_score)
SELECT 
    p.region_id, 
    CURDATE(), 
    COUNT(DISTINCT p.patient_id), 
    AVG(h.total_score)
FROM patient p
JOIN healthscore h ON p.patient_id = h.patient_id
GROUP BY p.region_id
HAVING COUNT(DISTINCT p.patient_id) > 0;
```
*   **Why it's impressive**: It uses **Aggregation Functions** (`COUNT`, `AVG`), **Grouping**, and a **Having Clause** to perform real-time population health monitoring.

---

## 🛠️ Key Technical Terms to Drop:
*   **Referential Integrity**: Using Foreign Keys with `ON DELETE CASCADE`.
*   **Prepared Statements**: Preventing SQLi.
*   **Relational Mapping**: How PHP maps to MySQL rows.
*   **Scalability**: "The hierarchical region structure allows us to scale from a single hospital to a national level."

---
**Viva Preparation Guide v1.0**
