# Ai Assistant

This is a modular, AI-first personal assistant designed to automate life management. It is built as a Laravel Modular Monolith, where every life aspect (Finance, Planning, Communication) exists as an independent, AI-powered module.

## Modules
### Expense Tracker
An intelligent financial companion that allows you to manage your spending through simple natural language.
- **Language**: I spent 20k on dinner.
- **Query**: How much did I spend last week?
- **Logic**: Uses Gemini 1.5 Flash for high-speed tool orchestration.

## Tech Stack
- **Backend**: Laravel 13 (PHP 8.4)
- **Frontend**: React 19 + Inertia.js v3 (Vite 6)
- **Styling**: Tailwind CSS v4
- **Database**: PostgreSQL
- **AI Engine**: `laravel/ai` + `prism-php/prism`
- **Modules**: `nwidart/laravel-modules`

## Setup & Installation

### 1. Prerequisites
- PHP 8.4+
- Node.js 20+
- PostgreSQL

### 2. Installation
```bash
git clone https://github.com/akm-2112/ai-personal-assistant
cd ...
composer install
npm install
```

### 3. Environment Config
Copy `.env.example` to `.env` and configure:
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `GEMINI_API_KEY` (Get one for free at [Google AI Studio](https://aistudio.google.com/))
- `AI_PROVIDER=gemini`

### 4. Database & Build
```bash
php artisan migrate --seed
npm run build
```

### 5. Start Development
```bash
composer run dev
```

## Project Structure
- `Modules/`: Contains all feature-specific code (Logic, AI Tools, Controllers).
- `app/Ai/`: Global AI base configurations and logging.
- `resources/js/`: React components and Inertia pages.

---
*Built with ❤️ and Antigravity*
