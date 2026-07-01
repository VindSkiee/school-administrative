---
name: session-management
description: Use when saving or loading session context. Triggers on "save session", "log session", "end session", "session summary", "what did we do last time", "session save", "simpan session", "akhiri session". Also triggers when agent detects session completion.
---

# Session Management Skill

## Save Session Protocol

When saving session context, create a markdown file at:
`.opencode/session_logs/YYYY-MM-DD_topic.md`

### Required Sections:

1. **Summary** - One paragraph of what was accomplished
2. **Decisions Made** - Key architectural/design decisions with rationale
3. **Files Modified** - List of all files changed with brief description
4. **Verification** - What tests/checks were run and their results
5. **Pending Tasks** - What's not yet done
6. **Next Steps** - Recommended actions for next session

### Template:

```markdown
# Session: YYYY-MM-DD - [Topic]

## Summary
[One paragraph description of what was accomplished in this session]

## Decisions Made
- [Decision 1]: [Rationale]
- [Decision 2]: [Rationale]

## Files Modified
- `path/to/file.php`: [What changed and why]
- `path/to/other.php`: [What changed and why]

## Verification
- [x] Code formatted
- [x] Tests pass (if applicable)
- [x] Manual verification

## Pending Tasks
- [ ] Task 1
- [ ] Task 2

## Next Steps
1. [First action to take in next session]
2. [Second action to take in next session]
```

### How to Determine Session Topic:

- Use the main task name (e.g., "meetings-migration", "workflow-setup")
- For multiple tasks, use the most significant one
- Keep filename concise but descriptive

## Load Session Protocol

When loading session context:

1. List files in `.opencode/session_logs/` using glob
2. Find the most recent `.md` file (by filename date)
3. Read the file
4. Provide a concise summary:
   - Current project state
   - What was last done
   - What's pending
   - Any important decisions to remember

### Summary Format:

```
=== SESSION RECOVERY ===
Date: [YYYY-MM-DD]
Topic: [topic]
Last done: [one sentence]
Pending: [list of pending tasks]
Key decisions: [list important decisions]
Next: [recommended next action]
========================
```

## Session Lifecycle Triggers

### When User Says "save session" / "simpan session"

1. Generate session log content based on current conversation
2. Create file at `.opencode/session_logs/YYYY-MM-DD_topic.md`
3. Confirm file was saved
4. Provide summary of what was saved

### When User Says "done" / "selesai" / "finished"

1. Run final verification (format, test, etc.)
2. Display shutdown checklist
3. Ask for confirmation to save
4. Save if confirmed

### When User Says "load session" / "lanjutkan" / "where were we"

1. Find latest session log
2. Read and summarize
3. Offer to continue with pending tasks

### When Agent Detects Completion

Agent should proactively detect when work is done:
- All verification steps pass
- No pending tasks remain
- User gives completion signals

When detected, show shutdown checklist and offer to save.

## Session Log Location

```
.opencode/session_logs/
├── .gitkeep
├── 2026-06-26_workflow-setup.md
├── 2026-06-27_meetings-migration.md
└── ...
```
