---
name: migration-safety
description: Use when creating or modifying database migrations. Triggers on "migration", "migrate", "schema change", "add column", "drop column", "database structure", "ALTER TABLE", "create table", "buat migration".
---

# Migration Safety Skill

## CRITICAL RULES

### NEVER (Larangan Mutlak)

- JANGAN hapus kolom yang masih di-reference oleh model
- JANGAN ubah type kolom tanpa data migration
- JANGAN jalankan migration tanpa backup database
- JANGAN edit migration yang sudah dijalankan di production
- JANGAN gunakan `Schema::drop()` tanpa `down()` method

### ALWAYS (Wajib Dilakukan)

- SELALU buat migration baru untuk setiap perubahan
- SELALU gunakan `down()` method untuk rollback
- SELALU test migration di database dev/lokal dulu
- SELALU backup sebelum jalankan migration
- SELALU gunakan `constrained()` untuk foreign keys
- SELALU tambah index untuk kolom yang sering di-query

## Migration Workflow

### Step 1: Plan

```bash
# CREATE table
php artisan make:migration create_meetings_table

# ADD column
php artisan make:migration add_meeting_id_to_attendances_table

# DROP column
php artisan make:migration_drop column_name_from_table_name_table
```

### Step 2: Implement

```php
// up() - Forward migration
public function up(): void
{
    Schema::create('meetings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
        $table->date('date');
        $table->integer('meeting_number');
        $table->enum('status', ['normal', 'canceled', 'holiday'])->default('normal');
        $table->text('notes')->nullable();
        $table->timestamps();
        
        // Unique constraint
        $table->unique(['schedule_id', 'meeting_number']);
        
        // Indexes for common queries
        $table->index(['schedule_id', 'date']);
    });
}

// down() - Rollback
public function down(): void
{
    Schema::dropIfExists('meetings');
}
```

### Step 3: Data Migration (jika perlu)

```php
// Di dalam up() - pindahkan data dari tabel lama ke baru
DB::transaction(function () {
    $attendances = DB::table('attendances')
        ->select('schedule_id', 'date')
        ->distinct()
        ->get();
    
    foreach ($attendances as $att) {
        $meetingId = DB::table('meetings')->insertGetId([
            'schedule_id' => $att->schedule_id,
            'date' => $att->date,
            'meeting_number' => 1,
            'status' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Update attendances table
        DB::table('attendances')
            ->where('schedule_id', $att->schedule_id)
            ->where('date', $att->date)
            ->update(['meeting_id' => $meetingId]);
    }
});
```

### Step 4: Test Rollback

```bash
php artisan migrate:refresh     # Full reset
php artisan migrate:rollback    # Step back
php artisan migrate             # Run forward again
```

### Step 5: Verify

```bash
php artisan db:show                    # List all tables
php artisan db:table meetings          # Show table structure
php artisan migrate:status             # Check migration status
```

## Naming Convention

| Type | Pattern | Example |
|------|---------|---------|
| CREATE | `create_{table}_table` | `create_meetings_table` |
| ADD | `add_{column}_to_{table}_table` | `add_meeting_id_to_attendances_table` |
| DROP | `drop_{column}_from_{table}_table` | `drop_column_from_table_table` |
| UPDATE | `update_{table}_table` | `update_attendances_table` |

## Rollback Safety Checklist

- [ ] `down()` method correctly reverses `up()`
- [ ] Data can be recovered after rollback
- [ ] Foreign keys are properly dropped
- [ ] Indexes are properly dropped
- [ ] No orphaned records left behind
