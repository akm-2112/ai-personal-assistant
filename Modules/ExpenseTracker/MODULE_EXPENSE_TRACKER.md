# Expense Tracker Module Documentation

The Expense Tracker module is a flagship component of the **OpenClaw OS** personal assistant. It provides an intelligent, natural language interface to manage financial records seamlessly.

## Architecture

This module follows a clean, modular monolith architecture using the `nwidart/laravel-modules` pattern.

### 1. AI Layer
- **Agent**: `ExpenseTrackerAgent`
  - Specifically tuned for financial data manipulation.
  - Powered by **Gemini 1.5 Flash** for low-latency and cost-effective performance.
  - Uses `laravel/ai` SDK for tool calling and conversation history.
- **Tools**:
  - `RecordExpense`: Captures spend amount, category, and description.
  - `GetExpenseList`: Retrieves itemized transactions with time and category filters.
  - `GetExpenseSummary`: Aggregates data to provide insights (Total spend, averages, etc.).
- **Orchestration**:
  - Located in `Ai/Prompts`, deterministic rules guide the LLM on which tool to use via natural language intents.

### 2. Backend Logic
- **Action Pattern**: `HandleExpenseChatAction` processes incoming chat messages.
- **Database**: 
  - `expenses` table tracks `amount`, `currency`, `category`, `description`, and `user_id`.
  - Full support for multi-user isolation.

### 3. Frontend
- Built with **React** and **Inertia.js v3**.
- Features a real-time Chat interface that bridges natural language to the backend tools.

## Key Features
- ✅ **Natural Language Input**: "I spent 5000 on dinner" automatically triggers data entry.
- ✅ **Context Awareness**: Ask "What did I spend yesterday?" and follow up with "And on coffee?"
- ✅ **Summary Insights**: "Show me my spend this month" provides aggregated totals.
- ✅ **Modular Integration**: Built to work as a standalone module that shares the global User context.

## Configuration
- Default Provider: `gemini`
- Intent Model: `gemini-1.5-flash`
- Token Limit: 1000 per response

---
*Created by Antigravity (Advanced Agentic Coding Assistant)*
