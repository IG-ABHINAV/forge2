# Architecture — FORGE 2 Kanban Board

## Multi-Agent System Overview

This project was built using a two-agent autonomous system orchestrated through Slack:

```
┌─────────────────────────────────────────────────────┐
│                    HUMAN (You)                       │
│          Posts goals in #sprint-main                 │
└─────────────┬───────────────────────┬───────────────┘
              │ goals & reviews       │ approvals
              ▼                       │
┌─────────────────────────────┐       │
│   🧠 Hermes (Orchestrator)  │◄──────┘
│   Model: gemini-2.5-flash   │
│   Role: Planning, memory,   │
│   task decomposition,        │
│   validation, delegation     │
└─────────────┬───────────────┘
              │ delegates tasks
              ▼
┌─────────────────────────────┐
│   🤖 OpenClaw (Coder)       │
│   Model: Ollama qwen2.5-    │
│          coder:7b            │
│   Role: Code generation,    │
│   file writes, testing,     │
│   debugging, building        │
└─────────────────────────────┘
```

## Agent Roles

### Hermes — The Brain (Orchestrator)
- **Model**: Google `gemini-2.5-flash` (free tier via AI Studio)
- **Responsibilities**:
  - Receives high-level goals from the human in `#sprint-main`
  - Decomposes goals into discrete coding tasks
  - Delegates tasks to OpenClaw in `#agent-coder`
  - Validates outputs (runs tests, checks builds)
  - Maintains persistent memory across sessions
  - Posts autonomous progress updates via cron
  - Runs the `status-report` skill for structured updates

### OpenClaw — The Hands (Coder)
- **Model**: Ollama `qwen2.5-coder:7b` (local, unlimited)
- **Responsibilities**:
  - Receives coding tasks from Hermes
  - Writes/modifies source files (PHP, JSX, CSS)
  - Runs shell commands (artisan, npm, git)
  - Executes tests and reports results
  - Reports status in What I Did / What's Left / What Needs Your Call format

## Model Routing Rationale

| Agent | Model | Why |
|-------|-------|-----|
| Hermes (brain) | `gemini-2.5-flash` | Large context window for planning; strong reasoning for task decomposition; generous free tier |
| OpenClaw (hands) | `qwen2.5-coder:7b` via Ollama | Purpose-built for code generation; runs locally with zero rate limits; fast inference for rapid file writes |

**Fallback ladder**: If Gemini rate-limited → Groq `openai/gpt-oss-120b` → Ollama local. OpenClaw on Ollama never rate-limits (local).

## Slack Channel Scheme

| Channel | Purpose | Who posts |
|---------|---------|-----------|
| `#sprint-main` | Human ↔ Hermes communication. Goals, plans, status updates, approvals. | Human + Hermes |
| `#agent-coder` | Hermes delegates tasks; OpenClaw codes and reports. | Hermes + OpenClaw |
| `#agent-orchestrator` | Hermes planning trees, state transitions, delegation orders. | Hermes |
| `#agent-log` | Raw build logs: test runs, migrations, builds, git commits, system status. | OpenClaw + Hermes |

## The Chat Loop

```
1. Human posts goal in #sprint-main
   └→ "Build the Kanban board with drag-and-drop"

2. Hermes decomposes into tasks, posts plan
   └→ Planning tree: 20 milestones

3. Hermes delegates Task 1 to OpenClaw in #agent-coder
   └→ "Scaffold Laravel API with migrations"

4. OpenClaw writes code, runs commands, reports
   └→ "What I Did: Created 3 migrations, 3 models..."

5. Hermes validates (runs tests), moves to Task 2
   └→ "Tests: 18/18 PASS. Proceeding to frontend."

6. Human reviews and approves/corrects at any point
   └→ All visible in Slack channels
```

## Technology Stack

| Layer | Technology | Details |
|-------|-----------|---------|
| Backend | Laravel 12 (PHP 8.3) | REST API, Eloquent ORM |
| Database | SQLite | Zero-config, file-based |
| Frontend | React 19 (Vite 6) | SPA with Axios API client |
| Styling | Custom CSS | Glassmorphic dark mode |
| Drag & Drop | HTML5 DnD API | Native, no library |
| Orchestrator | Hermes Agent | NousResearch |
| Coder | OpenClaw | openclaw.ai |
| Comms | Slack (Socket Mode) | 4 channels |

## Data Model

```
Board (1) ──→ (N) KanbanList (1) ──→ (N) Card
  │                  │                  │
  ├─ id              ├─ id              ├─ id
  ├─ name            ├─ board_id (FK)   ├─ kanban_list_id (FK)
  └─ timestamps      ├─ name            ├─ title
                     ├─ position        ├─ description
                     └─ timestamps      ├─ labels_csv
                                        ├─ assigned_member
                                        ├─ due_date
                                        ├─ position
                                        └─ timestamps
```

## API Endpoints

| Method | Endpoint | Action |
|--------|----------|--------|
| GET | `/api/boards` | List all boards |
| POST | `/api/boards` | Create a board |
| GET | `/api/boards/{id}` | Show board with lists & cards |
| PUT | `/api/boards/{id}` | Update board |
| DELETE | `/api/boards/{id}` | Delete board |
| POST | `/api/boards/{id}/lists` | Create list in board |
| PUT | `/api/lists/{id}` | Update list |
| DELETE | `/api/lists/{id}` | Delete list |
| POST | `/api/lists/{id}/cards` | Create card in list |
| PUT | `/api/cards/{id}` | Update card |
| DELETE | `/api/cards/{id}` | Delete card |
| PATCH | `/api/cards/{id}/move` | Move card between lists |
