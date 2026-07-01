---
name: patch-workflow
description: Use when making patches, bug fixes, or small code changes. Triggers on "patch", "fix", "hotfix", "quick fix", "bug fix", "small change", "perbaikan", "fix bug".
---

# Patch Workflow Skill

## Mandatory Steps for Every Patch

### Pre-Patch (SEBELUM edit)

1. **Understand** - Baca file yang akan diubah, pahami konteksnya
2. **Trace** - Cari semua tempat yang menggunakan kode yang akan diubah (grep, glob)
3. **Plan** - Tulis rencana perubahan sebelum mulai edit
4. **Snapshot** - Pastikan snapshot aktif untuk undo/redo

### During Patch (SAAT edit)

1. **Minimal Change** - Ubah sesedikit mungkin, jangan refactor
2. **Follow Patterns** - Tiru style yang sudah ada di codebase
3. **One Concern** - Satu patch = satu perubahan fokus
4. **No Side Effects** - Jangan ubah kode yang tidak relevan

### Post-Patch (SETELAH edit)

1. **Format** - Jalankan formatting command
2. **Test** - Jalankan test jika ada
3. **Verify** - Cek komponen terkait masih berfungsi
4. **Log** - Catat perubahan di session log

## Verification Commands

### PHP/Laravel
```bash
# Code formatting
vendor/bin/pint --dirty --format agent

# Run tests
php artisan test --compact

# Check routes
php artisan route:list --columns=method,uri

# Check specific route
php artisan route:list --columns=method,uri | grep "attendance"
```

### Vue/Frontend
```bash
# Check for errors
cd sms-frontend && npm run build

# Lint (if configured)
cd sms-frontend && npx eslint src/
```

### Database
```bash
# Check migration status
php artisan migrate:status

# Show table structure
php artisan db:table attendances
```

## Rollback Strategy

- Selalu catat perubahan di session log
- Gunakan git untuk version control
- Jika patch gagal, gunakan snapshot untuk undo
- Simpan session log sebelum rollback untuk referensi

## Common Patch Patterns

### Bug Fix
1. Reproduce the bug
2. Identify root cause (trace route -> controller -> service -> model)
3. Fix the root cause, not the symptom
4. Verify fix doesn't break other features

### Small Feature
1. Check if similar feature exists (reuse before create)
2. Follow existing patterns
3. Add validation if needed
4. Update session log

### Refactoring (only when requested)
1. Understand current implementation
2. Plan the refactoring
3. Make changes incrementally
4. Test after each change
5. Never refactor unrelated code
