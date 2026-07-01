# Plan: Disable Delete Button + Backend Validation for Associated Data

## Goal
Prevent deletion of data that is associated with other data to minimize missing/orphaned data. Each CRUD page's delete button should be disabled when the entity has downstream associations. Special handling for UserManagement: button changes to "Nonaktifkan" instead of "Hapus".

## Pages to handle (excluding ScheduleManagement.vue)

| Page | Entity | Backend validation | Frontend disable |
|------|--------|--------------------|------------------|
| UserManagement.vue | User | **Needs new** | **Needs new** (Nonaktifkan button) |
| ClassManagement.vue | SchoolClass | ✅ Already exists | **Needs `has_data` flag** |
| SubjectManagement.vue | Subject | ✅ Already exists | **Needs `has_data` flag** |
| AcademicYearManagement.vue | AcademicYear | ✅ Already exists | ✅ Already disables for `is_active` |

---

## Backend Changes

### 1. UserController — Add `has_data` flag + association check on delete

**File:** `backend/app/Http/Controllers/API/Admin/UserController.php`

**index method** — After fetching users, add `has_data` flag per user:
```php
$users->getCollection()->transform(function ($user) {
    $user->has_data = false;
    if ($user->teacher) {
        $user->has_data = $user->teacher->schedules()->exists()
            || $user->teacher->homeroomClass()->exists();
    } elseif ($user->student) {
        $user->has_data = $user->student->classes()->exists()
            || $user->student->submissions()->exists()
            || $user->student->attendances()->exists();
    }
    return $user;
});
```

**destroy method** — Add association check before soft-delete:
```php
// Check if user has associated data
if ($user->teacher) {
    if ($user->teacher->schedules()->exists() || $user->teacher->homeroomClass()->exists()) {
        return response()->json(['error' => '...'], 403);
    }
} elseif ($user->student) {
    if ($user->student->classes()->exists() || $user->student->submissions()->exists()) {
        return response()->json(['error' => '...'], 403);
    }
}
```

### 2. ClassController — Add `has_data` flag in index

**File:** `backend/app/Http/Controllers/API/Admin/ClassController.php`

**index method** — After fetching classes, add:
```php
$classes->getCollection()->transform(function ($class) {
    $class->has_data = $class->students()->exists() || $class->schedules()->exists();
    return $class;
});
```

The `destroy` method already validates — no change needed there.

### 3. SubjectController — Add `has_data` flag in index

**File:** `backend/app/Http/Controllers/API/Admin/SubjectController.php`

**index method** — After fetching subjects, add:
```php
$subjects->getCollection()->transform(function ($subject) {
    $subject->has_data = $subject->schedules()->exists();
    return $subject;
});
```

The `destroy` method already validates — no change needed there.

### 4. AcademicYearController — Already fine

The `destroy` method already checks `is_active` and `classes()->exists()`. The frontend already disables for `is_active`. No backend changes needed. Optionally add `has_data` flag for classes in index, but the frontend already handles this via `is_active`.

---

## Frontend Changes

### 1. UserManagement.vue — "Nonaktifkan" button

**File:** `sms-frontend/src/pages/admin/UserManagement.vue`

**Delete button** — Replace the single "Hapus" button with two states:

```html
<!-- Nonaktifkan (when user has associated data) -->
<button
  v-if="item.has_data && item.id !== authStore.user?.id"
  @click="promptDeactivateUser(item)"
  class="px-3 py-2 bg-yellow-50 border border-yellow-200 hover:bg-yellow-100 text-yellow-700 font-semibold rounded-lg transition-colors flex items-center"
  title="Nonaktifkan"
>
  <Icon icon="mdi:account-off-outline" class="w-4 h-4" />
</button>

<!-- Hapus (when no associated data) -->
<button
  v-else-if="item.id !== authStore.user?.id"
  @click="promptDeleteUser(item)"
  class="px-3 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors flex items-center"
  title="Hapus"
>
  <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
</button>
```

**New functions:**
- `promptDeactivateUser(user)` — Opens ConfirmModal with message about deactivating
- `executeDeactivateUser()` — Calls `userService.update(user.id, { is_active: false })` instead of delete

**ConfirmModal** — Make title/message dynamic (similar to AcademicYearManagement pattern):
- For delete: title="Hapus Pengguna?", confirmText="Ya, Hapus!"
- For deactivate: title="Nonaktifkan Pengguna?", confirmText="Ya, Nonaktifkan"

### 2. ClassManagement.vue — Disable delete when `has_data`

**File:** `sms-frontend/src/pages/admin/ClassManagement.vue`

**Delete button** — Add disabled condition:
```html
<button
  @click="promptDeleteClass(item)"
  :disabled="item.has_data"
  :class="[
    'px-3 py-2 font-semibold rounded-lg shadow-md transition-colors flex items-center',
    item.has_data
      ? 'bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed'
      : 'bg-brand-red hover:bg-brand-orange text-white'
  ]"
  :title="item.has_data ? 'Tidak bisa hapus: kelas masih memiliki siswa atau jadwal' : 'Hapus Kelas'"
>
```

### 3. SubjectManagement.vue — Disable delete when `has_data`

**File:** `sms-frontend/src/pages/admin/SubjectManagement.vue`

**Delete button** — Add disabled condition:
```html
<button
  @click="promptDelete(item)"
  :disabled="item.has_data"
  :class="[
    'px-3 py-2 font-semibold rounded-lg shadow-md transition-colors flex items-center',
    item.has_data
      ? 'bg-gray-100 border border-gray-200 text-gray-300 cursor-not-allowed'
      : 'bg-brand-red hover:bg-brand-orange text-white'
  ]"
  :title="item.has_data ? 'Tidak bisa hapus: mapel masih terikat jadwal' : 'Hapus Mapel'"
>
```

### 4. AcademicYearManagement.vue — No changes needed

Already disables delete button when `is_active` is true. Backend already blocks deletion when classes exist.

---

## File Change Summary

| File | Change |
|------|--------|
| `backend/app/Http/Controllers/API/Admin/UserController.php` | Add `has_data` flag in index, add association check in destroy |
| `backend/app/Http/Controllers/API/Admin/ClassController.php` | Add `has_data` flag in index |
| `backend/app/Http/Controllers/API/Admin/SubjectController.php` | Add `has_data` flag in index |
| `sms-frontend/src/pages/admin/UserManagement.vue` | Two-state button (Nonaktifkan/Hapus), new deactivate flow |
| `sms-frontend/src/pages/admin/ClassManagement.vue` | Disable delete button when `has_data` |
| `sms-frontend/src/pages/admin/SubjectManagement.vue` | Disable delete button when `has_data` |

## Verification
- Run `vendor/bin/pint --dirty --format agent` after PHP changes
- Test each CRUD page: verify delete button is grayed out when data has associations
- Test UserManagement: verify "Nonaktifkan" button appears for users with data, and deactivation works
- Test backend: verify 403 response when trying to delete associated data via API
