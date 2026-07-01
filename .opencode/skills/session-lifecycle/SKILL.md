---
name: session-lifecycle
description: Use when detecting session completion or when user indicates work is done. Triggers on "done", "selesai", "finished", "completed", "sudah", "habis", "cukup", "akhir", "stop", "pause", "break". Also use proactively when all verification steps pass.
---

# Session Lifecycle Skill

## Purpose

This skill ensures every session has a proper beginning, middle, and end. It prevents context loss by enforcing session save/load protocols.

## Session Completion Detection

### Auto-Detect Signals

The agent should proactively detect when a session is complete:

1. **All verification steps pass:**
   - [ ] Code formatted (Pint/Built-in)
   - [ ] Tests pass (if applicable)
   - [ ] No errors in console
   - [ ] Files verified

2. **No pending tasks remain:**
   - All items in the current task list are checked
   - User didn't mention any follow-up work

3. **User verbal signals:**
   - "done", "selesai", "finished", "completed"
   - "sudah", "habis", "cukup"
   - "stop", "pause", "break"
   - "save session", "simpan session"

### When Completion Detected

Agent MUST execute this sequence:

```
Step 1: Verify all work
Step 2: Run final verification (format, test, etc.)
Step 3: Generate summary
Step 4: Display shutdown checklist
Step 5: Ask user for confirmation
Step 6: Save session log (if confirmed)
```

## Shutdown Checklist Template

When session is ending, display:

```
╔══════════════════════════════════════════════════════╗
║              SESSION SHUTDOWN CHECKLIST              ║
╠══════════════════════════════════════════════════════╣
║                                                      ║
║  [✓/○] Task: [task name]                            ║
║  [✓/○] Files modified: [count] files                ║
║  [✓/○] Verification: [pass/fail]                    ║
║  [✓/○] Session log: [ready/pending]                 ║
║                                                      ║
║  SUMMARY:                                            ║
║  [1 paragraph of what was done]                      ║
║                                                      ║
║  FILES CHANGED:                                      ║
║  • path/to/file1.php - [description]                ║
║  • path/to/file2.php - [description]                ║
║                                                      ║
║  NEXT SESSION WILL NEED:                             ║
║  1. [Next task]                                      ║
║  2. [Following task]                                 ║
║                                                      ║
║  Commands:                                           ║
║  • Type "save session" to save and exit              ║
║  • Type "continue" to keep working                   ║
║  • Type "save session as [topic]" for custom name    ║
║                                                      ║
╚══════════════════════════════════════════════════════╝
```

## Session Save Protocol

### Standard Save

When user says "save session" or agent detects completion:

1. Create file: `.opencode/session_logs/YYYY-MM-DD_topic.md`
2. Topic should be the main task name (e.g., "meetings-migration", "bug-fix-attendance")
3. Use the session log template from AGENTS.md

### Custom Save

When user says "save session as [topic]":

1. Create file: `.opencode/session_logs/YYYY-MM-DD_[topic].md`
2. Use user-provided topic as filename

### Save Content Template

```markdown
# Session: YYYY-MM-DD - [Topic]

## Summary
[One paragraph description of what was accomplished]

## Decisions Made
- [Decision 1]: [Rationale]
- [Decision 2]: [Rationale]

## Files Modified
- `path/to/file.php`: [What changed and why]

## Verification
- [x] Code formatted
- [x] Tests pass (if applicable)
- [x] Manual verification

## Pending Tasks
- [ ] Task 1 (if any)

## Next Steps
1. [Recommended next action]
2. [Following action]
```

## Session Load Protocol

When user says "load session", "recall", "where were we", "lanjutkan":

1. List files in `.opencode/session_logs/`
2. Find most recent `.md` file
3. Read and summarize
4. Provide recovery summary

### Recovery Summary Format

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

## Mid-Session Checkpoint (Context Preservation)

### When to Auto-Save

Agent MUST auto-save context at these points:

1. **After every milestone** (e.g., finished editing 1 file)
2. **Before complex operations** (e.g., running migration, bulk edit)
3. **When context looks long** (10+ tool calls already done)
4. **When user pauses** (asked a question, thinking)

### Checkpoint Format

After each milestone, update session log with checkpoint:

```markdown
# Session: YYYY-MM-DD - [Topic]

## Summary
[Updated summary]

## Current State
- **Task:** [nama task yang sedang dikerjakan]
- **Progress:** [X/Y files modified]
- **Current file:** [file yang sedang/sudah di-edit]
- **Current action:** [apa yang baru saja dilakukan]

## Decisions Made
- [Decision 1]: [Rationale]
- [Decision 2]: [Rationale]

## Files Modified
- `path/to/file1.php`: [What changed] ✓ DONE
- `path/to/file2.php`: [What changed] ✓ DONE
- `path/to/file3.php`: [What changed] ⏳ IN PROGRESS

## Pending Tasks
- [ ] [Remaining task 1]
- [ ] [Remaining task 2]

## Next Steps
1. [Immediate next action]
2. [Following action]
```

### Auto-Save Trigger Points

```
Tool Calls:  1   2   3   4   5   6   7   8   9  10  11  12 ...
             │   │   │   │   │   │   │   │   │   │   │   │
Checkpoint:  ·   ·   ·   ·   ✓   ·   ·   ·   ✓   ·   ·   ✓
                          ▲               ▲           ▲
                          │               │           │
                     Save here        Save here   Save here
                     (5 calls)        (9 calls)   (12 calls)
```

### Context Warning Signs

Agent should recognize these signs that context is getting full:

1. **Many tool calls** - More than 10 tool calls in sequence
2. **Large outputs** - Tool outputs are getting truncated
3. **Repetitive actions** - Doing similar things multiple times
4. **Long conversations** - Many back-and-forth messages

When detected, agent MUST:
```
⚠️ Context getting long. Saving checkpoint...
[Save current state to session log]
Checkpoint saved. Continuing...
```

## Compaction Recovery Protocol

### If Compaction Happens Mid-Task

When agent notices it lost context (compaction happened):

1. **Recognize the gap** - Agent realizes it doesn't remember what it was doing
2. **Check session logs** - Read the latest log file
3. **Verify git status** - Check what files were modified
4. **Resume from checkpoint** - Continue from last saved state

### Recovery Steps

```bash
# Step 1: Find latest session log
ls -la .opencode/session_logs/

# Step 2: Read the log
cat .opencode/session_logs/YYYY-MM-DD_topic.md

# Step 3: Check git status
git status
git diff --stat

# Step 4: Verify files mentioned in log still exist
ls -la path/to/modified/files
```

### Recovery Summary Format

```
╔══════════════════════════════════════════════════════╗
║         CONTEXT RECOVERY AFTER COMPACTION            ║
╠══════════════════════════════════════════════════════╣
║                                                      ║
║  Compaction detected. Recovering context...          ║
║                                                      ║
║  LAST CHECKPOINT: [Date] - [Time]                    ║
║                                                      ║
║  WHAT WAS BEING DONE:                                ║
║  • [Task description]                                ║
║                                                      ║
║  FILES MODIFIED (from git):                          ║
║  • path/to/file1.php - [status]                      ║
║  • path/to/file2.php - [status]                      ║
║                                                      ║
║  RESUMING FROM:                                      ║
║  • [Last completed step]                             ║
║  • [Next step to do]                                 ║
║                                                      ║
╚══════════════════════════════════════════════════════╝
```

## Proactive Session Management

### When Context Gets Long

If the conversation is getting long (many tool calls, large outputs), the agent should proactively suggest:

```
Context is getting long. Recommend saving session to preserve context.
Type "save session" to save current progress.
```

### Before Starting Complex Work

Before starting a complex task, check if there's a recent session log to load:

```
Found recent session from [date]. Loading context...
[Summary of last session]
Ready to continue or start fresh?
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
