# 📈 Agreement vs. Hyperparameter Variation (Notebook Guide)

This README explains what the notebook **`agreement_vs_hyperparameter_variation.ipynb`** does, how to run it, and what inputs/outputs and plots it produces.  
The goal is to **measure agreement** between human (ground truth) Naranjo assessments and **LLM-generated** assessments, and then **analyze how agreement changes** with **inference hyperparameters** (e.g., temperature).

---

## 💡 What the notebook does (high level)

1. **Connects to MySQL** and loads:
   - `icsr_assessment_import` → human/curated Naranjo information (or the fields needed to rebuild it).
   - `hp_results2` → LLM outputs and inference hyperparameters (e.g., temperature, frequency_penalty, max_new_token).
2. **Builds human narratives** in a standardized format (Q1–Q10 with scores + reasoning).
3. **Normalizes and joins** human vs. LLM rows by `(case_id, drug, event)` with robust cleaning (digits-only IDs, uppercase drug/event).
4. **Calls an LLM evaluator** (OpenAI) to compare the two narratives and produce **per-question agreement** (boolean for Q1..Q10) using a **strict JSON schema**.
5. **Computes `question_agreement_rate`** = average of 10 booleans per pair.
6. **Saves progress incrementally** (`agreement_progress.csv`) and final results (`agreement_df.csv`).
7. **Creates diagnostic plots** to visualize **agreement vs. temperature** (boxplot, polynomial trend, LOESS, binned bar chart, 2D KDE).

> The output is a tidy DataFrame you can use downstream for Bayesian optimization and performance tracking over time.

---

## 🧱 Inputs

- **MySQL database** (read-only access is enough):
  - DB: `db_icsr_assessment_manuela`
  - Tables:
    - `icsr_assessment_import` — contains case-level information and/or the Naranjo Q1..Q10 fields and reasoning needed to build a “human narrative”
    - `hp_results2` — contains LLM outputs for the same cases plus inference hyperparameters
- **OpenAI credentials** for the evaluator step (see Security notes below).

### Expected columns (typical)
- From **`icsr_assessment_import`** (or equivalent):
  - `case_id`, `drug_name`, `pt` (event/preferred term)
  - `q1_score` … `q10_score`, `q1_reasoning` … `q10_reasoning` *(names can be adapted in the notebook)*
- From **`hp_results2`**:
  - `case_id`, `drug`, `event`, `llm_output`
  - `temperature`, `frequency_penalty`, `max_new_token` *(and any others you may add like `top_p`, `top_k`)*

---

## 📤 Outputs

- **`agreement_progress.csv`** — incremental save during evaluation (safe to resume).
- **`agreement_df.csv`** — final table with these key columns:
  - IDs/keys: `case_id`, `drug`, `event`
  - Ten booleans: `agree_q1` … `agree_q10`
  - **`question_agreement_rate`** (0–1)
  - Hyperparameters: `temperature`, `frequency_penalty`, `max_new_token`
  - `notes` (optional evaluator comments)

You can download results directly in Colab (the notebook includes a `files.download(...)` call).

---

## 🛠️ How it works (key steps & methods)

### 1) DB connection & data load
- Uses **SQLAlchemy** (`create_engine`) + **pymysql** driver.
- Example query: `SELECT * FROM icsr_assessment_import;` and `SELECT * FROM hp_results2;`
- Resulting DataFrames are previewed (`head()`) and basic metadata is printed.

### 2) Human narrative builder
- The notebook defines **Naranjo questions (Q1–Q10)**.
- For each row, it builds a concise narrative block with:
  - Question text
  - Score (parsed safely, e.g., `" +1 "` → `1`)
  - Reasoning
  - Total score and **interpretation** (`Definite / Probable / Possible / Doubtful`)
- **Helper functions** ensure missing values are handled gracefully (`_safe`, `_safe_score`).

### 3) Robust joining (human ↔ LLM)
- Normalizes keys to avoid “same case, different format” mismatches:
  - `case_id_key` = digits-only string
  - `drug_key`, `event_key` = uppercase trimmed strings
- Performs an **inner merge** on `(case_id_key, drug_key, event_key)` to create **pairs**.

### 4) Agreement evaluation via LLM
- Defines a **strict JSON schema** with booleans: `agree_q1`..`agree_q10`.
- **System prompt** instructs the evaluator to extract Q1..Q10 from both narratives and judge **semantic equivalence** (ignore wording).
- Calls OpenAI with `response_format={"type":"json_schema", ...}` to **force valid JSON**.
- Computes **`question_agreement_rate`** as the mean of the 10 booleans.

> The notebook **auto-resumes** thanks to `agreement_progress.csv`: previously processed cases are skipped.

### 5) Visualization
Generates several complementary plots of **agreement vs. temperature**:
- **Box + strip plot** (distribution per temperature)
- **Quadratic trend** (Polynomial regression) — quick nonlinear check
- **LOESS** smoothed trend — non-parametric local regression
- **Bar chart by temperature bins** — coarse-grained view
- **2D KDE heatmap** — density of observations in (temp, agreement) space

These give a first-pass view of whether agreement is **flat**, **monotonic**, or **U-shaped** across temperatures.

---

## 🔧 How to run (Colab or local)

1. Install dependencies (first cell):
   ```bash
   pip install pymysql sqlalchemy pandas scikit-learn statsmodels seaborn matplotlib
   ```
2. Set your **DB connection** variables (`host`, `port`, `user`, `password`, `database`).  
3. **Security:** set your OpenAI API key as an **environment variable**, not plain text:
   ```python
   import os
   os.environ["OPENAI_API_KEY"] = "sk-..."  # or set this in Colab/Secrets
   from openai import OpenAI
   client = OpenAI(api_key=os.environ["OPENAI_API_KEY"])
   ```
4. Run cells in order:
   - Load tables → Build human narratives → Join with LLM outputs → Evaluate agreement → Save CSVs → Plot.

---

## 📦 Files produced

- `agreement_progress.csv` — intermediate state (safe to resume)
- `agreement_df.csv` — final tidy results (downloaded in Colab)
- Plots rendered inline in the notebook
