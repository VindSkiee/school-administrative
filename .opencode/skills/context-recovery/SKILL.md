---
name: context-recovery
description: Use when recovering context from a previous session. Triggers on "where were we", "what's the context", "recall", "resume", "continue", "what did we do last time", "session context", "lanjutkan", "kita tadi". Also triggers at session STARTUP automatically.
---

# Context Recovery Skill

## Session Startup Protocol

When a new session starts, the agent MUST:

1. Check for existing session logs
2. If found, load and summarize
3. Verify current state matches the log
4. Offer to continue with pending tasks

## Recovery Protocol

### Step 1: Find Session Logs

```bash
ls -la .opencode/session_logs/
```

Or use glob to find all `.md` files in `.opencode/session_logs/`.

### Step 2: Read Latest Log

Find the most recent `.md` file (by filename date, e.g., `2026-06-26_topic.md`) and read it.

### Step 3: Summarize State

Provide a concise summary:

```
╔══════════════════════════════════════════════════════╗
║              SESSION RECOVERY                        ║
╠══════════════════════════════════════════════════════╣
║                                                      ║
║  Last session: [Date] - [Topic]                      ║
║                                                      ║
║  WHAT WAS DONE:                                      ║
║  • [Accomplishment 1]                                ║
║  • [Accomplishment 2]                                ║
║                                                      ║
║  PENDING:                                            ║
║  • [Pending task 1]                                  ║
║  • [Pending task 2]                                  ║
║                                                      ║
║  KEY DECISIONS:                                      ║
║  • [Decision 1]                                      ║
║                                                      ║
║  NEXT: [Recommended action]                          ║
║                                                      ║
╚══════════════════════════════════════════════════════╝
```

### Step 4: Verify State

- Check `git status` for uncommitted changes
- Check that files mentioned in session log still exist
- Check for any conflicts or errors
- If everything looks good, proceed with pending tasks

### Step 5: Confirm Continuation

Ask user:
- "Lanjutkan dari mana?" (Continue from where?)
- Or suggest the next pending task from the log

## If No Session Log Found

1. Read `PROJECT_CONTEXT.md` for project overview
2. Read `AGENT_GUIDE.md` for conventions
3. Ask the user about the current task
4. Create a new session log to start fresh

## Session Completion Detection

Agent should proactively detect when a session is complete:

### Signals of Completion:
- All verification steps pass
- No pending tasks remain
- User says "done", "selesai", "finished", "completed"
- User asks to save session

### When Detected:
1. Show shutdown checklist
2. Ask for confirmation
3. Save session log if confirmed
4. Provide final summary

## Session Log Naming Convention

- Format: `YYYY-MM-DD_topic.md`
- Example: `2026-06-26_meetings-migration.md`
- Use hyphens for multi-word topics
- Keep topic concise but descriptive

## Session Log Location

```
.opencode/session_logs/
├── .gitkeep
├── 2026-06-26_workflow-setup.md
├── 2026-06-27_meetings-migration.md
└── ...
```

## Continuous Chain Concept

```
Session N-1 → Session N → Session N+1
    │            │            │
    ▼            ▼            ▼
[Work done]  [Load log]  [Load log]
    │            │            │
    ▼            ▼            ▼
[Save log]   [Work done] [Work done]
    │            │            │
    ▼            ▼            ▼
[File: *.md] [Save log]  [Save log]
```

Each session:
1. **Starts** by reading the latest session log (context recovery)
2. **Continues** work from where it left off
3. **Ends** by saving an updated session log

This creates an unbroken chain of context across sessions.
