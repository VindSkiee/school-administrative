# AGENTS.md - EduPlatform Project Instructions

## Project Identity

- **Name:** EduPlatform (School Administrative System)
- **Purpose:** Integrated School Management System for academic administration and digital learning
- **Stack:** Laravel 13 + Vue 3 (Composition API) + MySQL
- **Roles:** admin, teacher, student, principal
- **API:** REST JSON, versioned `api/v1/{role}/`
- **Auth:** JWT via tymon/jwt-auth
- **Language:** Bahasa Indonesia (user-facing messages)

## Mandatory Workflow (SEBELUM SETIAP TASK)

### Step 1: Session Recovery

Cek `.opencode/session_logs/` untuk file terbaru. Baca session log untuk memahami state project. Jika ada pending tasks, lanjutkan dari situ.

### Step 2: Understand Before Build

Baca file referensi berikut untuk memahami project:
- `PROJECT_CONTEXT.md` - Arsitektur dan struktur project
- `AGENT_GUIDE.md` - Coding conventions dan rules
- `backend/AGENTS.md` - Laravel-specific rules

### Step 3: Plan Before Code

Buat rencana sebelum mengedit file:
- Identifikasi semua file yang terdampak
- Pertimbangkan rollback strategy
- Gunakan `/plan` agent untuk arsitektur decisions

### Step 4: Execute Safely

Ikuti workflow patch:
- Patch workflow: minimal change, follow patterns, one concern
- Jalankan verification commands setelah perubahan
- Update session log setelah selesai

## Session Lifecycle Management

### Session Phases

Setiap session memiliki 3 phase:

1. **STARTUP** - Load session log terakhir, recover context, verify state
2. **WORK** - Eksekusi task, ikuti workflow, jalankan verification
3. **SHUTDOWN** - Save session log, provide summary, indicate next session needs

### Session Completion Detection

Agent HARUS mendeteksi kapan session sudah mencapai tujuan. Tanda-tanda session complete:

**Auto-Detect (Agent proaktif mendeteksi):**
- Semua verification checklist items sudah checked (✓)
- Tidak ada pending tasks yang tersisa
- Semua files yang dimodifikasi sudah di-verify
- User berkata "done", "selesai", "finished", "completed"

**When detected, agent WAJIB:**
1. Tampilkan summary pekerjaan yang sudah dilakukan
2. Tampilkan files yang sudah diubah
3. Tanyakan: "Apakah ada yang perlu disimpan sebelum session berakhir?"
4. Jalankan session-save jika user konfirmasi

### Session Shutdown Protocol

Ketika session akan berakhir, agent WAJIB:

```
=== SESSION SHUTDOWN CHECKLIST ===
[✓] Task completed: [nama task]
[✓] Files modified: [jumlah] files
[✓] Verification: All checks passed
[✓] Session log: Ready to save

Summary: [1 paragraf apa yang sudah dikerjakan]

Next session will need:
1. [Task selanjutnya]
2. [Task selanjutnya]

Type "save session" to save context, or "continue" to keep working.
================================
```

### Session Save Rules

**WAJIB save session ketika:**
- Task sudah selesai dan verified
- User berkata "save session", "simpan session", "akhiri session"
- User akan berhenti bekerja
- Context sudah mulai panjang (proaktif save sebelum compaction)

**Session log location:** `.opencode/session_logs/YYYY-MM-DD_topic.md`

### Session Load Rules

**WAJIB load session ketika:**
- Session baru dimulai
- User berkata "load session", "recall", "where were we", "lanjutkan"
- Agent perlu recover context sebelum mulai kerja

**Session load location:** `.opencode/session_logs/` (file terbaru)

## Session Logging Protocol

Setiap session AKHIR, simpan log ke `.opencode/session_logs/` dengan format `YYYY-MM-DD_topic.md`.

### Isi Session Log Wajib:

1. **Summary** - Apa yang sudah dikerjakan (1 paragraf)
2. **Decisions Made** - Keputusan architectural/design yang diambil
3. **Files Modified** - Semua file yang diubah beserta deskripsi
4. **Verification** - Test/check yang dijalankan dan hasilnya
5. **Pending Tasks** - Yang belum selesai
6. **Next Steps** - Langkah selanjutnya yang direkomendasikan

### Session Log Trigger Commands

| Trigger | Action |
|---------|--------|
| "save session" | Save current context to session log |
| "session save" | Save current context to session log |
| "simpan session" | Save current context to session log |
| "load session" | Load latest session log and summarize |
| "session load" | Load latest session log and summarize |
| "lanjutkan" | Load latest session and continue work |
| "where were we" | Load latest session and show status |
| "done" / "selesai" | Trigger session shutdown checklist |
| "finished" / "completed" | Trigger session shutdown checklist |

## Context Preservation (CRITICAL)

### Masalah Compaction

Ketika context window hampir penuh, opencode akan melakukan **compaction** (kompresi). Masalahnya:
- Agent bisa LUPA apa yang sedang dikerjakan
- Detail spesifik (angka, nama file, baris kode) bisa hilang
- Reasoning di belakang keputusan bisa hilang
- **Compaction terjadi TANPA peringatan dan TANPA otomatis save**

### Rule #1: SELALU ADA SESSION LOG

**SETIAP** session WAJIB memiliki session log. Tidak ada exception.

```
Session Log Rule:
─────────────────────────────────────────────────────
Mulai kerja → BUAT session log → Isi minimal summary
     │
     ▼
Kerja 3 tool calls → UPDATE session log
     │
     ▼
Kerja 6 tool calls → UPDATE session log
     │
     ▼
Kerja 9 tool calls → UPDATE session log (EMERGENCY SAVE)
     │
     ▼
Selesai → FINAL session log
```

### Proaktif Save Protocol

Agent WAJIB proaktif menyimpan state SEBELUM compaction terjadi.

#### Checkpoint Thresholds (WAJIB)

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

#### Multiple Signal Detection

Agent harus mendeteksi multiple sinyal, bukan hanya tool call count:

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

#### Emergency Save Protocol

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

### Context Recovery Setelah Compaction

Setelah compaction, agent WAJIB:

1. **Cek session logs** - Baca file terakhir di `.opencode/session_logs/`
2. **Verify state** - Cek git status, cek file yang disebutkan masih ada
3. **Resume** - Lanjutkan dari where it left off

```
Recovery Protocol:
─────────────────────────────────────────────────────
Compaction detected
     │
     ▼
Baca session log terakhir
     │
     ▼
Cek git status + file list
     │
     ▼
Verify apa yang sudah dikerjakan
     │
     ▼
Resume dari last checkpoint
```

### Mid-Session Checkpoint

Setelah setiap milestone (misal: selesai edit 1 file), agent otomatis update session log:

```
=== CHECKPOINT ===
Task: [nama task]
Progress: [X/Y files modified]
Current: [apa yang sedang dikerjakan SEKARANG]
Next: [langkah selanjutnya]
Files changed: [list files]
=================
```

### Session Log Sebagai "Save Point"

Session log bukan hanya untuk akhir session, tapi juga **mid-session checkpoint**:

```
Session Log Timeline:
─────────────────────────────────────────────────────
Start → CP1 → CP2 → CP3 → CP4 → CP5 → CP6 → CP7 → CP8 → End
  │      │     │     │     │     │     │     │     │     │
  ▼      ▼     ▼     ▼     ▼     ▼     ▼     ▼     ▼     ▼
Load   Save  Save  Save  Save  Save  Save  Save  Save  Final
log    3tc   6tc   9tc   12tc  15tc  18tc  21tc  24tc  save
              ▲           ▲
              │           │
         Checkpoint   Emergency
                    (compaction risk)
```

**tc = tool calls**

## Error Prevention Rules

### SELALU (Wajib)

- Jalankan `vendor/bin/pint --dirty --format agent` setelah edit PHP
- Jalankan `php artisan test --compact` setelah perubahan besar
- Baca file yang akan diubah SEBELUM mulai edit
- Cari semua tempat yang menggunakan kode yang akan diubah

### JANGAN (Larangan)

- Hapus data tanpa backup
- Ubah migration yang sudah jalan di production
- Refactor code yang tidak relevan dengan task
- Duplikat service/component yang sudah ada
- Perkenalkan library baru tanpa justifikasi

## Architecture Rules

### Backend (Laravel)

- **Layered Architecture:** Route -> Controller -> Service -> Model
- **Thin Controllers:** Logic bisnis di Service, bukan di Controller
- **Form Requests:** Validasi menggunakan Form Request classes
- **Eloquent:** Gunakan relationships, hindari raw SQL
- **API Response:** Konsisten dengan `response()->json()`

### Frontend (Vue 3)

- **Composition API:** Selalu gunakan `<script setup>`
- **Component Reuse:** BaseTable, BaseModal, BaseSelect, ConfirmModal
- **State Management:** Minimal global state (Pinia)
- **Styling:** Tailwind CSS v4, brand colors #E02E2B, #C66716

### Database

- **Migrations:** Selalu buat migration baru untuk perubahan
- **Indexes:** Tambah index untuk kolom yang sering di-query
- **Foreign Keys:** Gunakan `constrained()` untuk referensi
- **Rollback:** Selalu uji `down()` method

## Verification Checklist

Setelah setiap perubahan kode:

- [ ] `vendor/bin/pint --dirty --format agent` (PHP formatting)
- [ ] `php artisan test --compact` (jika ada test)
- [ ] Routes masih berfungsi (`php artisan route:list`)
- [ ] Models relationships masih benar
- [ ] Session log sudah di-update

## Quick Reference: Key Files

| Purpose | Location |
|---------|----------|
| Backend Models | `backend/app/Models/` |
| Backend Controllers | `backend/app/Http/Controllers/API/{Role}/` |
| Backend Services | `backend/app/Services/` |
| Backend Routes | `backend/routes/api/v1/{role}.php` |
| Frontend Pages | `sms-frontend/src/pages/{role}/` |
| Frontend Services | `sms-frontend/src/services/modules/{role}/` |
| Migrations | `backend/database/migrations/` |
| Seeders | `backend/database/seeders/` |
