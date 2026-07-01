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

### Solusi: Proaktif Save

Agent WAJIB proaktif menyimpan state SEBELUM compaction terjadi:

**Tanda-tanda context mau habis:**
- Banyak tool calls sudah dilakukan (10+ tool calls)
- Output tools sudah panjang
- User belum bilang "done" tapi work sudah banyak

**Ketika mendeteksi, agent WAJIB:**
1. SAVe current state ke session log SEKARANG
2. Jangan tunggu user minta save
3. Simpan: apa yang sedang dikerjakan, file apa saja yang sudah diubah, langkah selanjutnya

### Context Recovery Setelah Compaction

Setelah compaction, agent WAJIB:

1. **Cek session logs** - Baca file terakhir di `.opencode/session_logs/`
2. **Verify state** - Cek git status, cek file yang disebutkan masih ada
3. **Resume** - Lanjutkan dari where it left off

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
Start → Checkpoint 1 → Checkpoint 2 → ... → End
  │         │              │                   │
  ▼         ▼              ▼                   ▼
Load    Save state      Save state          Final
log     (mid-task)      (mid-task)          save
```

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
