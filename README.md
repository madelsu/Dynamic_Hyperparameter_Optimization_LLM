# Dynamic_Hyperparameter_Optimization_LLM
Code and resources for dynamic hyperparameter optimization of Large Language Models (LLMs) applied causality assessment. Dynamic hyperparameter optimization for LLMs, addressing concept drift and performance degradation, with automated workflows orchestrated through n8n.

The following is an overview of the workflow:
![Workflow](images/Workflow_Overview.png)

This experimental process tries to answer the following research questions:

*RQ1 – Data Infrastructure:*
How can pharmacovigilance data be efficiently organized, stored, and accessed to support dynamic automation of LLM-based causality assessments?

*RQ2 – Automation Framework:*
How can an automated workflow be designed to continuously evaluate, optimize, and store results from LLM assessments of ICSRs from databases?

*RQ3 – Hyperparameters:*
Does changing inference hyperparameters (e.g., temperature, top-p, top-k, etc.) significantly affect the percentage agreement between LLMs and human experts? If so, which hyperparameters—or combinations—lead to the most optimal performance for causality assessment tasks?

*RQ4 – Dynamic Adaptation and Model Degradation:*
How can we monitor performance degradation of LLMs over time and apply dynamic hyperparameter optimization to improve or maintain model stability as data distributions evolve?

Description of Each Step
(a) Human Gold-Standard Evaluations
This step represents the reference dataset of manually validated causality assessments (around 1,838 reports/ 236 FAERS cases). This is the data that was used in the experimentation.
Research question addressed: RQ1

(b) SQL Database and CRUD Web Interface
All data and narratives are structured in a SQL database and made accessible through a CRUD interface. This setup ensures that the information can be easily queried, updated, and integrated into automated workflows.
Research question addressed: RQ1

(c) Automation Workflow (n8n)
The workflow automates LLM causality assessments by iterating across multiple hyperparameter settings (temperature) and storing results in the database. 
Research question addressed: RQ2 

(d) Model Degradation per Hyperparameter (Temperature)
Instead of performing a full Bayesian optimization, this step uses a lookup-based re-evaluation of the hyperparameter combinations previously tested in the automation workflow. It compares how performance changes across different datasets or time points to assess whether model stability is maintained even as data distributions and temperature values vary. This step also examines which temperature settings yield the best performance for each dataset, helping to identify shifts in optimal configurations over time.
Research question addressed: RQ4

⚙️ Environment & Reproducibility

Python: 3.10+

Key Libraries: pandas, sqlalchemy, matplotlib, skopt, openai

Database: MySQL (via mysql-connector or sqlalchemy)

Automation: n8n (self-hosted or via ngrok)

Each folder includes a short README explaining how to run the scripts and what each output represents.
