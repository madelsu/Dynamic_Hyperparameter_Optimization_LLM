# 🧩 CRUD Web Interface for ICSR Assessment

This folder contains the PHP/MySQL CRUD web application we developed to visualize, manage, and export results from the ICSR reports.
It allows us to easily **browse**, **edit**, **import/export**, and **analyze** the cases.

![CRUD](images/CRUD.png)
---

## 💡 Overview

The interface was built using **PHP** and **MySQL** (via XAMPP) and is mainly designed for:
- Managing entries from the *ICSR Assessment Import* table  
- Adding or editing individual records through web forms  
- Importing or exporting data in Excel format  
- Authenticating users (simple login system)  
- Exploring or sampling cases for evaluation

---

## 📁 Folder Structure

Below is a simplified overview of the main files and what each one does:

```
icsrcrud/
├── auth.php            # Session control: redirects users if not logged in
├── create.php          # Adds new records through a form
├── create_admin.php    # One-time script to create an admin account
├── dashboard.php       # Optional landing page after login
├── db.php              # Database connection settings
├── delete.php          # Deletes selected records
├── edit.php            # Edits existing entries
├── export.php          # Exports filtered data to Excel
├── import.php          # Imports .xlsx data into MySQL
├── index.php           # Main CRUD table view with filters and pagination
├── login.php           # Login form for authentication
├── logout.php          # Ends session and redirects to login
├── navbar.php          # Navigation bar (shared across pages)
├── random_cases.php    # Random case sampling for testing
├── SimpleXLSX.php      # Library to read .xlsx files
├── SimpleXLSXGen.php   # Library to write .xlsx files
├── test.php / test2.php# Scratch files for quick testing
└── view.php            # Read-only detailed view of one record
```
---

## ⚙️ Requirements

- **XAMPP** (includes Apache, PHP, and MySQL)
- A local MySQL database (e.g., `db_icsr_assessment_manuela`)
- PHP extension `mysqli` enabled (default in XAMPP)

---

## 🔧 Setting Up the Database Connection

Open `db.php` and insert your credentials:

```php
<?php
$DB_HOST = "...";
$DB_PORT = "...";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "db_icsr_assessment_manuela";

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, (int)$DB_PORT);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
```

---

## 🧱 Example Tables

These are the main tables the CRUD app interacts with.  
You can adapt them depending on what data you want to visualize.

### 🧬 `icsr_assessment_import`
Stores the original ICSR data imported from Excel or FAERS.

```sql
CREATE TABLE icsr_assessment_import (
  id INT AUTO_INCREMENT PRIMARY KEY,
  case_id BIGINT,
  drug TEXT,
  event TEXT,
  case_narrative LONGTEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 📊 `performance_metrics`
Contains the LLM assessment results and hyperparameter configurations.

```sql
CREATE TABLE performance_metrics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  case_id BIGINT,
  drug TEXT,
  event TEXT,
  average_likert_score DOUBLE,
  question_agreement_rate DOUBLE,
  reasoning_agreement_q2 DOUBLE,
  reasoning_agreement_q3 DOUBLE,
  reasoning_agreement_q4 DOUBLE,
  reasoning_agreement_q5 DOUBLE,
  reasoning_agreement_q6 DOUBLE,
  reasoning_agreement_q7 DOUBLE,
  reasoning_agreement_q8 DOUBLE,
  reasoning_agreement_q9 DOUBLE,
  reasoning_agreement_q10 DOUBLE,
  temperature DOUBLE,
  frequency_penalty DOUBLE,
  max_new_tokens INT,
  top_k INT,
  top_p DOUBLE,
  typical_p DOUBLE,
  min_p DOUBLE,
  timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### 👤 `users`
Stores login credentials and roles.

```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(64) UNIQUE,
  password_hash VARCHAR(255),
  role ENUM('admin','user') DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Use `create_admin.php` once to create the first admin account.

---

## 🚀 How to Run the Interface

1. Copy the folder `icsrcrud/` to your XAMPP directory:  
   ```
   C:\xampp\htdocs\icsrcrud\
   ```
2. Start **Apache** and **MySQL** in the XAMPP control panel.  
3. Create your database and tables using the SQL code above.  
4. Open a browser and go to:
   ```
   http://localhost/icsrcrud/login.php
   ```
5. Log in (or first run `create_admin.php` to set up your credentials).  
6. Navigate to `index.php` — this is the main dashboard with all CRUD options.

---

## 🧭 Key Pages Explained

| File | Function |
|------|-----------|
| **index.php** | Displays all records with filtering and pagination. The central page of the CRUD interface. |
| **create.php** | Adds new entries via a web form. |
| **edit.php** | Updates existing entries. |
| **view.php** | Shows one record in full detail (useful for long narratives). |
| **import.php** | Loads `.xlsx` files into the database (uses SimpleXLSX). |
| **export.php** | Exports current view to Excel (uses SimpleXLSXGen). |
| **login.php / logout.php / auth.php** | Basic session authentication and user management. |
| **navbar.php** | Shared navigation menu used in all pages. |

---

## 📥 Inputs & Outputs

| Action | Input | Output |
|--------|--------|---------|
| **Import** | `.xlsx` file with matching column headers | Rows inserted into MySQL |
| **Create/Edit** | Data entered in the form | Record added or updated |
| **Export** | Filtered data from the table | Downloaded `.xlsx` file |
| **Delete** | Row ID | Record removed |
| **Login** | Username + password | Session created |

---

## 🧰 Configuration Tips

- If you want to change which table is shown in `index.php`, edit the `$TABLE_NAME` variable near the top of the file.  
- Adjust numeric vs. text filters by editing the arrays in `index.php`.  
- Long text fields (like narratives) are hidden in the list view but shown in `view.php`.  
- You can customize the navigation bar in `navbar.php` to include quick links to Import/Export.

---

## 🛡️ Security Notes

- Passwords are stored securely using PHP’s `password_hash()`.  
- Every page includes `auth.php` to ensure only logged-in users can access it.  
- Avoid uploading untrusted files through `import.php`.  



