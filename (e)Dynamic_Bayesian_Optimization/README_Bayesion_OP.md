# 🧠 Dynamic (Lookup) Bayesian Optimization — Notebook Guide

This README describes the notebook **`Dynamic(look_up)_Bayesian_Op.ipynb`**, which performs **lookup-based Bayesian Optimization** over time on model performance results from the pharmacovigilance LLM assessments.  
It is designed to simulate *dynamic re-running and re-optimization* as new data batches (or “time points”) become available.

---

## 💡 Purpose

The main goal of this notebook is to:
- **Model how optimal inference hyperparameters (like temperature)** evolve over time as new results are added;
- **Quantify performance trends** (agreement stability or degradation);
- **Identify high-impact hyperparameters** that drive better agreement between LLM and human causality assessments.

It uses the results produced by previous workflows (agreement and automation steps) to feed a lightweight, interpretable **Bayesian Optimization (BO)** pipeline.

---

## 🧱 Inputs

- **Database:**  
  MySQL database `db_icsr_assessment_manuela`  
  - Table `icsr_assessment_import` — case-level ICSR data.  
  - Table `hp_results2` — model outputs + hyperparameters.

- **CSV input:**  
  The notebook expects an uploaded CSV file (`agreement_df.csv`) containing the human vs LLM **agreement results** from the previous notebook.

  Expected columns include:
  ```
  case_id, drug, event, question_agreement_rate,
  temperature, frequency_penalty, max_new_tokens
  ```

---

## 🔄 Workflow Overview

| Step | Description |
|------|--------------|
| **1️⃣ Load Data** | Connects to MySQL and loads the relevant tables for context. Then reads `agreement_df.csv` from Colab upload. |
| **2️⃣ Create Time Samples** | Simulates data batches (like “time points”) by splitting the dataset into ~10 subsamples of similar size using `create_time_samples_per_case()`. |
| **3️⃣ Run Lookup-Only Bayesian Optimization** | For each sample, it uses existing combinations of parameters and computes mean performance for each — no new model runs are made, it’s a *lookup mode* optimization. |
| **4️⃣ Analyze and Plot Results** | Produces line and scatter plots showing how optimal performance and parameters (temperature, etc.) change over time. |
| **5️⃣ Repeat Optimization** | Repeats BO multiple times (e.g., 10×) to check stability of results and visualize variance. |
| **6️⃣ Detect Outliers / Drift** | Flags samples that deviate significantly from expected behavior using Z-score, IQR, and rolling thresholds. |
| **7️⃣ Characterize Cases** | Extracts metadata from narratives (like number of drugs, ADRs, patient age, weight) and aggregates these by time point to explore links between data features and performance. |

---

## ⚙️ Key Functions

### 🧩 `create_time_samples_per_case()`
Splits the agreement DataFrame into N time samples (default = 10).  
Each “time point” contains roughly 50–60 cases, keeping all data from the same `case_id` together.

Returns:
- `sampled_df` → the expanded dataset with a new column `sample_id`.  
- `samples_meta` → summary table with counts and mean/median agreement per sample.

---

### 🧩 `evaluate_lookup()` & `_mask_params()`
- These functions compute the **mean agreement** for a given combination of hyperparameters (e.g., `temperature=0.3`, `max_new_tokens=512`) found in that sample.
- If no exact match exists, the function returns 0.0 to discourage BO from repeating invalid combinations.

---

### 🧩 `run_lookup_bo_over_time()`
This is the main loop that performs Bayesian Optimization *per time sample*:
- Builds a categorical search space dynamically (based on the parameters that vary in that batch).
- Runs `gp_minimize()` from **scikit-optimize** to find the hyperparameter combo with the highest agreement.
- Collects:
  - `bo_summary`: best score + best parameters per sample.  
  - `bo_trials`: all evaluated parameter combinations.

---

## 📊 Plots and Analysis

1. **Performance Over Time**  
   Line plot of best mean agreement across samples (interpreted as “days” or time points).  

2. **Hyperparameters Evolution**  
   Line plot showing how temperature evolves through time.
   
4. **Stability Analysis**  
   Repeats BO multiple times with different seeds → shows whether performance curves stay stable (important for robustness).

5. **Anomaly Detectors**  
   Uses three flagging systems:
   - Z-score bounds (statistical outlier detection)
   - IQR bounds (robust range)
   - Rolling window checks (local deviations)
   These help identify when model performance starts deviating.

---

## 🧠 Narrative Feature Extraction

The notebook also explores **case-level features** extracted from narrative text:
- Number of drugs listed
- Number of adverse reactions (ADRs)
- Patient age and weight (if found)

It cleans and parses text fields to count or extract these indicators, then aggregates them by `sample_id`.  
This helps assess whether **data composition shifts** (e.g., older patients, more drugs) correlate with **performance changes**.

---

## 📦 Outputs

| File | Description |
|------|--------------|
| `samples_meta.csv` | Summary of time samples (size, mean agreement). |
| `bo_summary.csv` | Best hyperparameters and performance per sample. |
| `bo_trials.csv` | All tested parameter combinations. |
| `summary_table.csv` | Case-feature summary by time point. |

---

## 🧩 Dependencies

```bash
pip install pymysql sqlalchemy pandas scikit-optimize matplotlib seaborn
```
