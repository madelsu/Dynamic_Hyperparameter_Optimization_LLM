# 🧠 Building the SQL Database for ICSR Assessment

This section explains how the **SQL_Database.ipynb** and **Adding_narratives.ipynb** notebooks work together to create and maintain the MySQL database used throughout the project.  
The database is a key component that stores the ICSR cases, model assessments, and narrative texts used for LLM evaluation.

---

## 💡 Overview

These two notebooks automate the entire process of preparing and populating the database in a reproducible way.

1. **SQL_Database.ipynb** → creates and loads the main database table with structured ICSR data.  
2. **Adding_narratives.ipynb** → enriches the database by updating and cleaning case narratives.

Together, they ensure all data is clean, consistent, and ready for analysis or visualization through the CRUD interface.

---

## ⚙️ Requirements

- **Python 3.10+**
- Installed packages:
  ```bash
  pip install pandas sqlalchemy mysql-connector-python
  ```
- **MySQL server** (e.g., XAMPP or local installation)
- Excel files containing ICSR case data and narratives

---

## 🧱 1️⃣ SQL_Database.ipynb — Creating and Loading the Database

This notebook is responsible for:
- **Reading and cleaning** the main Excel file (`ICSR_assessment.xlsx`)  
- **Converting it** to a clean, tab-delimited text file (`.txt`)  
- **Loading it into MySQL** as a table called `icsr_assessment_import`

### 🔍 Key steps

- **Excel import and normalization:**  
  Column headers are standardized (lowercase, underscores) to ensure compatibility with MySQL.
  
- **Intermediate export:**  
  The cleaned data is saved as a tab-delimited `.txt` file to prevent encoding issues.
  
- **Database connection:**  
  Uses `SQLAlchemy` with a connection string such as:  
  `mysql+mysqlconnector://root:@localhost:.../db_icsr_assessment_manuela`
  
- **Data type mapping:**  
  Automatically detects long text columns (`narrative`, `reasoning`) and assigns them the correct MySQL type (`TEXT`).
  
- **Chunked writing:**  
  The data is written to MySQL in batches of 500 rows to handle large datasets efficiently.
  
- **Verification:**  
  After loading, the notebook queries the database to confirm that all rows were successfully inserted.

### 📦 Output

A structured MySQL table called:
```sql
db_icsr_assessment_manuela.icsr_assessment_import
```
containing all ICSR case data, ready for querying or visualization.

---

## 🧬 2️⃣ Adding_narratives.ipynb — Linking and Updating Narrative Texts

This notebook complements the first one by filling in or updating **case narratives** (text fields) for each ICSR record.

### 🔍 Key steps

- **Excel input:**  
  Reads a second file (`cases.xlsx`) containing two columns: `caseid` and `narrative`.

- **Cleaning and standardization:**  
  Removes line breaks, tabs, and special characters, ensuring one clean text line per narrative.  
  Deduplicates entries and keeps the most recent version for each case.

- **Digit-based matching:**  
  Matches cases between the *staging table* and *main table* using the numeric portion of the case ID.  
  This is especially useful when case IDs contain letters or formatting differences.

- **Longest-narrative selection:**  
  If multiple narratives exist for the same ID, the longest one is selected automatically.

- **Batch updates:**  
  Updates are sent to MySQL in chunks of 1,000 rows, ensuring efficiency even with large datasets.

- **Verification:**  
  After completion, the notebook prints how many narratives were added or updated.

---

## 🧩 Combined Workflow

| Step | Notebook | Description | Output |
|------|-----------|--------------|---------|
| 1 | **SQL_Database.ipynb** | Loads cleaned ICSR data into MySQL | `icsr_assessment_import` table |
| 2 | **Adding_narratives.ipynb** | Fills in missing or updated narratives | Complete dataset with full text fields |


