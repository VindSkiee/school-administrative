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
3. **At tool call thresholds** (3, 6, 9 calls - see below)
4. **When output is truncated** - Immediate save
5. **When user pauses** (asked a question, thinking)

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

### Auto-Save Trigger Points (AGGRESSIVE)

```
Tool Calls:  1   2   3   4   5   6   7   8   9  10  11  12 ...
             │   │   │   │   │   │   │   │   │   │   │   │
Checkpoint:  ·   ·   ✓   ·   ·   ✓   ·   ·   ✓   ·   ·   EMG
                     ▲           ▲           ▲           ▲
                     │           │           │           │
                First save   Second    Third save   EMERGENCY
                           save                    (save now!)
```

| Threshold | Aksi | Keterangan |
|-----------|------|------------|
| **3 tool calls** | Save checkpoint | First save - minimal summary |
| **6 tool calls** | Save checkpoint | Second save - update semua progress |
| **9 tool calls** | **EMERGENCY SAVE** | Save SEKARANG - compaction sudah dekat |
| **10+ tool calls** | **STOP & SAVE** | Jangan lanjut kerja sampai save selesai |

### Context Budget Awareness

Agent WAJIB memperhatikan context budget:

**Signal 1: Tool Call Count**
- 3+ tool calls → First checkpoint
- 6+ tool calls → Second checkpoint
- 9+ tool calls → Emergency save

**Signal 2: Output Size**
- Tool output mulai di-truncate → Save sekarang
- Response terakhir sangat panjang → Save sekarang

**Signal 3: Conversation Length**
- Banyak back-and-forth messages → Save sekarang
- User bertanya pertanyaan kompleks → Save sekarang

**Signal 4: Work Complexity**
- Selesai edit 1 file → Save checkpoint
- Selesai running migration → Save checkpoint
- Selesai refactor → Save checkpoint
- Selesai debugging → Save checkpoint

### Emergency Save Protocol

Ketika context sudah sangat dekat dengan compaction (9+ tool calls atau output truncated):

```
⚠️ EMERGENCY SAVE - Context hampir habis!
Menyimpan session log SEKARANG...
[Save minimal session log]
✅ Emergency save selesai.
Melanjutkan kerja...
```

**Minimal Save Format (untuk emergency):**

```markdown
# Session: YYYY-MM-DD - [Topic] (EMERGENCY SAVE)

## Summary
[1 kalimat apa yang sedang dikerjakan]

## Current State
- Task: [nama task]
- File sedang di-edit: [nama file]
- Progress: [X/Y selesai]

## Files Modified
- `path/to/file1.php`: [status]
- `path/to/file2.php`: [status]

## Next Steps
1. [Langkah paling krusial yang harus dilanjutkan]
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

## Proactive Session Management (AGGRESSIVE)

### Context Warning Signs - WAJIB DETEKSI

Agent harus mendeteksi tanda-tanda context mau habis:

1. **Tool call count tinggi** - 3+ tool calls sudah dilakukan
2. **Output truncate** - Tool output terpotong (tanda context penuh)
3. **Repetitive actions** - Melakukan hal yang sama berulang kali
4. **Long conversations** - Banyak back-and-forth messages
5. **Large file reads** - Membaca file besar (1000+ baris)
6. **Multiple file edits** - Edit banyak file sekaligus

### When Context Gets Long - IMMEDIATE ACTION

Jika context sudah panjang (3+ tool calls), agent WAJIB:

```
⚠️ Context getting long (X tool calls). Saving checkpoint...
[Save current state to session log]
✅ Checkpoint saved. Continuing...
```

**JANGAN tunggu sampai 10+ tool calls. Mulai save di 3 tool calls.**

### Before Starting Complex Work

Sebelum memulai task kompleks, agent WAJIB:

1. **Cek session log terakhir** - Apakah ada checkpoint?
2. **Save current state** - Jika ada work in progress
3. **Buat rencana** - Sebelum mulai banyak tool calls

```
Found recent session from [date]. Loading context...
[Summary of last session]
Ready to continue or start fresh?
```

### Continuous Save Mindset

```
SETIAP SAAT = POTENTIAL SAVE POINT
─────────────────────────────────────────────────────
Work → Check: 3 calls yet? → Yes → SAVE
  │
  ▼
Work → Check: milestone? → Yes → SAVE
  │
  ▼
Work → Check: output truncated? → Yes → EMERGENCY SAVE
  │
  ▼
Work → Check: 9 calls? → Yes → EMERGENCY SAVE
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
