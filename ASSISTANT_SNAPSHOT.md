# 🧠 Personal AI Assistant: Architecture & Context Snapshot

> [!IMPORTANT]
> **PURPOSE**: This file exists to provide immediate context to any future AI Agent session. If the conversation history is lost, the AI should read this first to understand the current state and roadmap

## 🏗️ Project Architecture

- **Type**: Modular Monolith (Laravel 13 + SQLite).
- **Note**: 'i used postgresql instead of sqlite.'
- **Frontend**: Inertia.js + React + Tailwind CSS (using shadcn/ui style components).
- **Core Package**: [laravel/ai](https://github.com/laravel/ai) for Agentic workflows.

## 🗄️ Database & Models

### Core System (`app/Models`)

| Model     | Table                         | Purpose                                                   |
| :-------- | :---------------------------- | :-------------------------------------------------------- |
| `User`    | `users`                       | Auth and personal data.                                   |
| `AiRun`   | `ai_runs`                     | Logs every AI execution (agent name, status, timestamps). |
| `AiUsage` | `ai_usages`                   | Token consumption metrics for individual runs.            |
| -         | `agent_conversations`         | Stores chat session metadata (Titles).                    |
| -         | `agent_conversation_messages` | Full thread history including Tool Calls and Results.     |

### Expense Tracker Module (`Modules/ExpenseTracker`)

| Model     | Table      | Purpose                                                                |
| :-------- | :--------- | :--------------------------------------------------------------------- |
| `Expense` | `expenses` | Core records: `amount`, `currency`, `category`, `description`, `date`. |

## 🤖 AI Agents & Tools

### 1. PersonalAssistant (`app/Ai/Agents/PersonalAssistant.php`)

- **Role**: General coordinator.
- **Tools**: `GetLocalTime`, `ReadCalendar`, `ScheduleTask`.
- **Status**: Skeleton implemented.
- **Note**: 'this is just testing purpose. i implemented to test Ai api and how the agents work in Laravel AI SDK. this is not related to my project'

### 2. ExpenseTrackerAgent (`Modules/ExpenseTracker/app/Ai/Agents/ExpenseTrackerAgent.php`)

- **Role**: Specialized financial analyst.
- **Tool**: `RecordExpense` (talks to `CreateExpenseAction`).
- **Status**: Backend Fully Complete (including Pest Tests).

## 🚀 Today's Roadmap (Current Progress)

1.  ✅ **Phase 1: Backend Foundation** (Done: April 12).
2.  🔄 **Phase 2: Web UI (Targeting Today)**.
    - Create `ExpenseTracker/Index.tsx` dashboard.
    - Add Sidebar navigation.
3.  🔜 **Phase 3: Conversational Agent**.
    - Implement a "Bridge" to allow the UI to talk to the `ExpenseTrackerAgent`.
4.  🔜 **Phase 4: Telegram Bot Hook**.
    - Connect the Telegram Webhook to the existing Agent logic.

## 📂 Key File Locations

- **Backend Core**: `Modules/ExpenseTracker/app/`
- **Frontend Pages**: `resources/js/Pages/ExpenseTracker/` (Pending)
- **AI Tools**: `Modules/ExpenseTracker/app/Ai/Tools/`
- **Tests**: `Modules/ExpenseTracker/tests/`

# \*\*\* THE MAIN THINGS YOU, THE AI MODELS MUST FOLLOW

- You act like a mentor, assistant to me
- give me guidence, advices, suggests.
- \*\*Do not Write or fix yourself.
- ONLY WRITE OR FIX IN THE CODEBASE WHEN I ASK TO.
- BE MY ASSISTANT, BE MY MENTOR I TRY TO LEARN NEW THINGS SO DON'T MAKE ME A STUPID.
