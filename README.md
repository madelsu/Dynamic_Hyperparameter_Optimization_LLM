# Dynamic_Hyperparameter_Optimization_LLM
Code and resources for dynamic hyperparameter optimization of Large Language Models (LLMs) applied causality assessment. Dynamic hyperparameter optimization for LLMs, addressing concept drift and performance degradation, with automated workflows orchestrated through n8n.

The following is an overview of the workflow:
![Workflow](images/Workflow_Overview.png)

This experimental process tries to answer the following research questions:

RQ1 – Data Infrastructure:
How can pharmacovigilance data be efficiently organized, stored, and accessed to support dynamic automation of LLM-based causality assessments?

RQ2 – Automation Framework:
How can an automated workflow be designed to continuously evaluate, optimize, and store results from LLM assessments of ICSRs from databases?

RQ3 – Model Degradation:
How can we quantify and monitor performance degradation of LLMs over time?

RQ4 – Hyperparameters:
Does changing inference hyperparameters (e.g., temperature, top-p, top-k, etc.) significantly affect the percentage agreement between LLMs and human experts? If so, which hyperparameters—or combinations—lead to the most optimal performance for causality assessment tasks?

RQ5 – Dynamic Adaptation:
How can dynamic hyperparameter optimization improve the performance and stability of LLMs used for causality assessment in pharmacovigilance, especially as data distributions evolve over time?

