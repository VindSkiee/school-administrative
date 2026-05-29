<template>
  <div class="space-y-6">
    <!-- Header & Actions -->
    <div
      class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Data Kelas</h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola daftar kelas, penetapan wali kelas, dan pembagian siswa.
        </p>
      </div>
      <button
        @click="openMigrateModal()"
        class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition-colors flex items-center"
      >
        <svg
          class="w-5 h-5 mr-2 text-brand-orange"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
          ></path>
        </svg>
        Migrasi Ganjil ➔ Genap
      </button>
      <button
        @click="exportClassDetailsCsv"
        :disabled="isExportingCsv || isLoading"
        class="bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition-colors flex items-center disabled:opacity-70"
      >
        <svg
          v-if="isExportingCsv"
          class="w-5 h-5 mr-2 animate-spin text-brand-orange"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ isExportingCsv ? 'Mengekspor...' : 'Export Detail CSV' }}
      </button>
      <button
        @click="openClassModal()"
        class="bg-brand-red hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center"
      >
        <svg
          class="w-5 h-5 mr-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          ></path>
        </svg>
        Tambah Kelas
      </button>
    </div>

    <!-- FILTER KELAS (7-9) -->
    <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
      <div class="w-full sm:w-72">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
          Filter Kelas
        </label>
        <BaseSelect
          v-model="classGradeFilter"
          :options="classGradeOptions"
          placeholder="Semua Kelas"
        />
      </div>

      <div class="w-full sm:w-80">
        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
          Filter Tahun Ajaran
        </label>
        <BaseSelect
          v-model="academicYearFilter"
          :options="academicYearOptions"
          placeholder="Semua Tahun Ajaran"
        />
      </div>
    </div>

    <!-- REUSABLE COMPONENT: BaseTable -->
    <BaseTable
      :columns="tableColumns"
      :data="filteredClasses"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      @page-change="fetchClasses"
      emptyMessage="Belum ada data kelas yang terdaftar."
    >
      <!-- Kolom Nama Kelas -->
      <template #cell(name)="{ item }">
        <router-link
          :to="{ name: 'Detail Kelas', params: { id: item.id } }"
          class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
        >
          {{ item.name }}
        </router-link>
      </template>

      <!-- Kolom Tahun Ajaran -->
      <template #cell(academicYear)="{ item }">
        <div class="inline-flex items-center gap-2">
          <span class="text-gray-600">
            {{ item.academic_year?.name || "Belum diatur" }}
          </span>

          <span
            v-if="item.academic_year?.is_active"
            class="px-2 py-0.5 bg-green-100 text-green-700 text-[10px] font-bold rounded uppercase tracking-wider border border-green-200"
          >
            Aktif
          </span>
        </div>
      </template>

      <template #cell(semester)="{ item }">
        <span
          v-if="item.academic_year?.semester === 'odd'"
          class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold rounded-full"
        >
          Ganjil
        </span>
        <span
          v-else-if="item.academic_year?.semester === 'even'"
          class="inline-flex items-center px-3 py-1 bg-green-50 text-green-700 border border-green-200 text-xs font-semibold rounded-full"
        >
          Genap
        </span>
        <span v-else class="text-gray-400 text-sm font-medium">-</span>
      </template>

      <!-- Kolom Wali Kelas -->
      <template #cell(homeroomTeacher)="{ item }">
        <div
          v-if="item.homeroom_teacher"
          class="inline-flex items-center text-gray-800 font-medium"
        >
          <svg
            class="w-4 h-4 mr-1.5 text-brand-orange"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            ></path>
          </svg>

          <router-link
            :to="{ name: 'Detail Pengguna', params: { id: item.homeroom_teacher.user.id } }"
            class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
          >
            {{ item.homeroom_teacher.user.name }}
          </router-link>
        </div>

        <div
          v-else
          class="inline-flex items-center px-2.5 py-1 rounded-full bg-red-50 border border-red-200 text-brand-red text-xs font-semibold cursor-pointer hover:bg-red-100 transition"
          @click="openTeacherModal(item)"
        >
          <svg
            class="w-3.5 h-3.5 mr-1"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            ></path>
          </svg>

          Wali Kelas Kosong
        </div>
      </template>

      <!-- Kolom Jumlah Siswa -->
      <template #cell(studentsCount)="{ item }">
        <span
          :class="
            item.students_count > 0
              ? 'text-gray-800 font-bold'
              : 'text-brand-red font-bold'
          "
        >
          {{ item.students_count }} Siswa
        </span>
      </template>

      <!-- Kolom Aksi -->
      <template #cell(actions)="{ item }">
        <div class="inline-flex items-center gap-2">
          <!-- Wali Kelas -->
          <button
            @click="openTeacherModal(item)"
            class="p-2 bg-orange-50 hover:bg-orange-100 text-brand-orange rounded-lg transition-colors border border-orange-100"
            title="Set Wali Kelas"
          >
            <Icon icon="fontisto:person" class="w-4 h-4" />
          </button>

          <!-- Kelola Siswa -->
          <button
            @click="openStudentModal(item)"
            class="p-2 bg-green-50 hover:bg-green-100 text-green-600 rounded-lg transition-colors border border-green-100"
            title="Kelola Siswa"
          >
            <Icon icon="raphael:people" class="w-4 h-4" />
          </button>

          <!-- Edit -->
          <button
            @click="openClassModal(item)"
            class="px-3 py-2 bg-white border border-gray-200 hover:bg-blue-50 hover:border-blue-200 text-blue-600 font-semibold rounded-lg transition-colors shadow-sm flex items-center"
            title="Edit Kelas"
          >
            <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
          </button>

          <!-- Hapus -->
          <button
            @click="promptDeleteClass(item)"
            class="px-3 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg shadow-md transition-colors flex items-center"
            title="Hapus Kelas"
          >
            <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
          </button>
        </div>
      </template>
    </BaseTable>

    <!-- 1. MODAL: CREATE / EDIT CLASS -->
    <BaseModal
      :isOpen="isClassModalOpen"
      :title="isEditing ? 'Edit Info Kelas' : 'Tambah Kelas Baru'"
      @close="isClassModalOpen = false"
    >
      <form id="classForm" @submit.prevent="saveClass" class="space-y-4">
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Nama Kelas</label
          >
          <input
            v-model="classForm.name"
            type="text"
            required
            placeholder="Contoh: XII-IPA-1"
            class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none transition-colors"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Tahun Ajaran</label
          >
          <!-- Pastikan backend ada route untuk mengambil list academic years -->
          <BaseSelect
            v-model="classForm.academic_year_id"
            :options="academicYearOptions"
            placeholder="Pilih Tahun Ajaran"
            required
          />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="isClassModalOpen = false"
            class="px-5 py-2 bg-white border hover:bg-gray-50 text-gray-700 font-semibold rounded-lg"
          >
            Batal
          </button>
          <button
            type="submit"
            form="classForm"
            :disabled="isSaving"
            class="px-5 py-2 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70"
          >
            {{ isSaving ? "Menyimpan..." : "Simpan" }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- 2. MODAL: ASSIGN TEACHER (Single Select with Search) -->
    <BaseModal
      :isOpen="isTeacherModalOpen"
      title="Tetapkan Wali Kelas"
      @close="isTeacherModalOpen = false"
    >
      <form
        id="teacherForm"
        @submit.prevent="saveTeacherAssignment"
        class="space-y-4"
      >
        <p class="text-sm text-gray-600 mb-2">
          Pilih satu Guru untuk menjadi Wali Kelas
          <strong class="text-gray-900">{{ selectedClass?.name }}</strong
          >.
        </p>

        <!-- INFO BANNER: Penjelasan Sistem Filter -->
        <div
          class="flex items-start p-3 bg-blue-50 border border-blue-100 rounded-lg"
        >
          <svg
            class="w-4 h-4 text-blue-500 mt-0.5 mr-2 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            ></path>
          </svg>
          <p class="text-xs text-blue-700 font-medium leading-relaxed">
            Sistem otomatis menyembunyikan Guru yang
            <strong class="font-bold">sudah menjabat</strong> sebagai Wali Kelas
            di kelas lain pada Tahun Ajaran ini.
          </p>
        </div>

        <!-- FITUR PENCARIAN GURU (LIVE SEARCH) -->
        <div class="relative">
          <svg
            class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            ></path>
          </svg>
          <input
            v-model="searchTeacherQuery"
            type="text"
            placeholder="Cari nama guru..."
            class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-orange focus:border-brand-orange outline-none text-sm transition-colors"
          />
        </div>

        <!-- LIST GURU (Single Select Viewport) -->
        <div
          class="border border-gray-200 rounded-lg max-h-60 overflow-y-auto custom-scrollbar bg-white"
        >
          <div
            v-for="teacher in filteredTeacherList"
            :key="teacher.value"
            @click="teacherForm.teacher_id = teacher.value"
            class="flex items-center px-4 py-3 hover:bg-orange-50/50 border-b border-gray-100 last:border-0 transition-colors cursor-pointer group"
          >
            <!-- Custom Radio Button UI -->
            <div
              class="flex items-center justify-center w-5 h-5 rounded-full border mr-3 transition-colors shrink-0"
              :class="
                teacherForm.teacher_id === teacher.value
                  ? 'border-brand-orange bg-brand-orange'
                  : 'border-gray-300 group-hover:border-brand-orange'
              "
            >
              <div
                v-if="teacherForm.teacher_id === teacher.value"
                class="w-2 h-2 rounded-full bg-white"
              ></div>
            </div>

            <!-- Label -->
            <div class="flex flex-col w-full select-none">
              <span
                class="text-sm font-semibold transition-colors"
                :class="
                  teacherForm.teacher_id === teacher.value
                    ? 'text-brand-orange'
                    : 'text-gray-800'
                "
              >
                {{ teacher.label }}
              </span>
            </div>
          </div>

          <!-- Empty State jika pencarian nihil atau guru habis -->
          <div
            v-if="filteredTeacherList.length === 0"
            class="p-8 flex flex-col items-center justify-center text-center"
          >
            <svg
              class="w-10 h-10 text-gray-300 mb-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
              ></path>
            </svg>
            <p class="text-sm text-gray-500 font-medium">
              Guru tidak ditemukan atau semua guru sudah menjadi wali kelas.
            </p>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="isTeacherModalOpen = false"
            class="px-5 py-2 bg-white border hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            form="teacherForm"
            :disabled="isSaving || !teacherForm.teacher_id"
            class="px-5 py-2 bg-brand-orange hover:bg-brand-red text-white font-semibold rounded-lg disabled:opacity-70 flex items-center transition-colors shadow-sm"
          >
            <svg
              class="w-5 h-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              ></path>
            </svg>
            Terapkan
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- 3. MODAL: ASSIGN STUDENTS (Bulk Action) -->
    <BaseModal
      :isOpen="isStudentModalOpen"
      title="Pilih Siswa Kelas"
      maxWidth="lg"
      @close="isStudentModalOpen = false"
    >
      <form
        id="studentForm"
        @submit.prevent="saveStudentAssignment"
        class="space-y-4"
      >
        <p class="text-sm text-gray-600">
          Pilih siswa aktif yang akan dimasukkan ke kelas
          <strong class="text-gray-900">{{ selectedClass?.name }}</strong
          >.
        </p>

        <!-- FITUR PENCARIAN & SMART FILTER (LIVE SEARCH) -->
        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex-1">
            <svg
              class="w-5 h-5 absolute left-3 top-2.5 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              ></path>
            </svg>
            <input
              v-model="searchStudentQuery"
              type="text"
              placeholder="Cari nama/email siswa..."
              class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none text-sm transition-colors"
            />
          </div>

          <!-- SMART FILTER DROPDOWN -->
          <div class="sm:w-56 shrink-0">
            <BaseSelect
              v-model="studentFilterMode"
              :options="studentFilterOptions"
              placeholder="Filter Siswa"
            />
          </div>
        </div>

        <!-- BINGKAI LIST SISWA -->
        <div
          class="border border-gray-200 rounded-lg bg-white overflow-hidden flex flex-col"
        >
          <!-- Header List: Select All & Total Info -->
          <div
            class="flex justify-between items-center px-4 py-3 bg-gray-50 border-b border-gray-200"
          >
            <div class="flex items-center">
              <input
                type="checkbox"
                id="selectAll"
                v-model="isAllSelected"
                class="w-4 h-4 text-brand-red focus:ring-brand-red border-gray-300 rounded cursor-pointer"
              />
              <label
                for="selectAll"
                class="ml-3 text-sm font-bold text-gray-800 cursor-pointer select-none"
              >
                Pilih Semua
              </label>
            </div>
            <span
              class="text-xs font-semibold text-gray-500 bg-white px-2 py-1 rounded-md border border-gray-200 shadow-sm"
            >
              Total Tersedia: {{ availableStudents.length }} Siswa
            </span>
          </div>

          <!-- Multi-select List (Scrollable) -->
          <div class="max-h-60 overflow-y-auto custom-scrollbar">
            <div
              v-for="student in filteredAvailableStudents"
              :key="student.id"
              class="flex items-center px-4 py-3 hover:bg-red-50/50 border-b border-gray-100 last:border-0 transition-colors"
            >
              <input
                type="checkbox"
                :id="'std-' + student.id"
                :value="student.id"
                v-model="studentForm.student_ids"
                class="w-4 h-4 text-brand-red focus:ring-brand-red border-gray-300 rounded cursor-pointer"
              />
              <label
                :for="'std-' + student.id"
                class="ml-3 flex flex-col cursor-pointer w-full select-none"
              >
                <span class="text-sm font-semibold text-gray-800">{{
                  student.name
                }}</span>
                <span class="text-xs text-gray-500">{{ student.email }}</span>
              </label>
            </div>

            <!-- Empty State jika pencarian tidak ditemukan -->
            <div
              v-if="filteredAvailableStudents.length === 0"
              class="p-8 flex flex-col items-center justify-center text-center"
            >
              <svg
                class="w-10 h-10 text-gray-300 mb-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
              <p class="text-sm text-gray-500 font-medium">
                Siswa tidak ditemukan.
              </p>
            </div>
          </div>
        </div>

        <!-- Indikator Jumlah Terpilih -->
        <div
          class="flex justify-between items-center bg-brand-red/5 p-3 rounded-lg border border-brand-red/10"
        >
          <p class="text-sm font-medium text-brand-red">
            Total Dipilih:
            <span class="font-bold text-lg">{{
              studentForm.student_ids.length
            }}</span>
            Siswa
          </p>
          <button
            type="button"
            @click="studentForm.student_ids = []"
            v-if="studentForm.student_ids.length > 0"
            class="text-xs text-brand-red hover:text-brand-orange font-semibold underline"
          >
            Reset Pilihan
          </button>
        </div>
      </form>

      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="button"
            @click="isStudentModalOpen = false"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors"
          >
            Batal
          </button>
          <button
            type="submit"
            form="studentForm"
            :disabled="isSaving || studentForm.student_ids.length === 0"
            class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-sm disabled:opacity-70 flex items-center transition-colors"
          >
            <svg
              class="w-5 h-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
              ></path>
            </svg>
            Masukkan ke Kelas
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- CONFIRM MODAL DELETE -->
    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      title="Hapus Kelas?"
      :message="confirmModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDeleteClass"
      @cancel="confirmModal.isOpen = false"
    />
    <!-- MODAL: MIGRASI SEMESTER -->
    <BaseModal
      :isOpen="isMigrateModalOpen"
      title="Migrasi Semester (Ganjil ke Genap)"
      @close="isMigrateModalOpen = false"
    >
      <form id="migrateForm" @submit.prevent="executeMigration" class="space-y-4">
        <div class="p-3 bg-blue-50 border border-blue-100 rounded-lg">
          <p class="text-xs text-blue-700 font-medium leading-relaxed">
            <strong class="font-bold">Info Pintar:</strong> Fitur ini akan menduplikasi semua kelas dari semester asal ke semester tujuan beserta <strong class="font-bold">Wali Kelas</strong> dan <strong class="font-bold">Siswa</strong> di dalamnya. Jadwal pelajaran akan dikosongkan. Sangat cocok untuk perpindahan Ganjil ke Genap!
          </p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Dari: Tahun Ajaran Asal (Ganjil)</label>
          <BaseSelect
            v-model="migrateForm.from_academic_year_id"
            :options="academicYearOptions"
            placeholder="Pilih Semester Ganjil..."
            required
          />
        </div>

        <div class="flex justify-center my-2 text-gray-400">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path></svg>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ke: Tahun Ajaran Tujuan (Genap)</label>
          <BaseSelect
            v-model="migrateForm.to_academic_year_id"
            :options="academicYearOptions"
            placeholder="Pilih Semester Genap..."
            required
          />
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button type="button" @click="isMigrateModalOpen = false" class="px-5 py-2 bg-white border text-gray-700 font-semibold rounded-lg">Batal</button>
          <button type="submit" form="migrateForm" :disabled="isSaving || (migrateForm.from_academic_year_id === migrateForm.to_academic_year_id)" class="px-5 py-2 bg-brand-orange hover:bg-brand-red text-white font-semibold rounded-lg disabled:opacity-70 flex items-center">
            Mulai Migrasi Massal
          </button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { Icon } from "@iconify/vue";
import { ref, reactive, computed, onMounted, watch } from "vue";
import { useToastStore } from "../../stores/toast";
import { classService } from "../../services/modules/admin/classService";
import { userService } from "../../services/modules/admin/userService";
import api from "../../services/api"; // Akses langsung untuk route yang belum ada servicenya

// Shared Components
import BaseSelect from "../../components/BaseSelect.vue";
import BaseTable from "../../components/BaseTable.vue";
import BaseModal from "../../components/BaseModal.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";

const toastStore = useToastStore();

// --- CONFIGURATION ---
const tableColumns = [
  { key: "name", label: "Nama Kelas" },
  { key: "academicYear", label: "Tahun Ajaran", align: "center" },
  { key: "semester", label: "Semester", align: "center" },
  { key: "homeroomTeacher", label: "Wali Kelas", align: "center" },
  { key: "studentsCount", label: "Jumlah Siswa", align: "center" },
  { key: "actions", label: "Aksi", align: "center" },
];

// --- STATE ---
const classes = ref([]);
const allClasses = ref([]);
const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 100,
});
const academicYearOptions = ref([]);
const teacherOptions = ref([]);
const availableStudents = ref([]);

const classGradeFilter = ref("");
const academicYearFilter = ref("");
const classGradeOptions = [
  { value: "7", label: "Kelas 7" },
  { value: "8", label: "Kelas 8" },
  { value: "9", label: "Kelas 9" },
];

const isLoading = ref(true);
const isSaving = ref(false);
const isExportingCsv = ref(false);

const selectedClass = ref(null);

// Modal States
const isClassModalOpen = ref(false);
const isTeacherModalOpen = ref(false);
const isStudentModalOpen = ref(false);
const isEditing = ref(false);

const confirmModal = reactive({
  isOpen: false,
  isLoading: false,
  message: "",
  targetId: null,
});

// Form States
const classForm = reactive({ name: "", academic_year_id: "" });
const teacherForm = reactive({ teacher_id: "" });
const studentForm = reactive({ student_ids: [] });
const searchStudentQuery = ref("");

// TAMBAHKAN STATE FILTER MODE
const studentFilterMode = ref("unassigned");

const studentFilterOptions = [
  { value: "unassigned", label: "Siswa Belum Ada Kelas" },
  { value: "all", label: "Semua Siswa Aktif" },
];

const normalizeClassName = (name) => {
  if (!name) return "";
  return name
    .toString()
    .toUpperCase()
    .replace(/KELAS/g, "")
    .replace(/[^A-Z0-9]/g, "");
};

const getClassGrade = (name) => {
  const normalized = normalizeClassName(name);
  if (!normalized) return null;

  const romanMap = [
    { roman: "VIII", grade: "8" },
    { roman: "VII", grade: "7" },
    { roman: "IX", grade: "9" },
  ];

  for (const { roman, grade } of romanMap) {
    if (normalized.startsWith(roman)) return grade;
  }

  const firstChar = normalized[0];
  if (firstChar === "7" || firstChar === "8" || firstChar === "9") {
    return firstChar;
  }

  return null;
};

const isStudentAssignedInAcademicYear = (student, academicYearId) => {
  if (!student?.student || !academicYearId) return false;

  const classes = student.student.classes || [];
  if (classes.length > 0) {
    return classes.some((cls) => {
      const pivotYearId = cls.pivot?.academic_year_id;
      const classYearId = cls.academic_year_id;
      const resolvedYearId = pivotYearId ?? classYearId;
      return String(resolvedYearId) === String(academicYearId);
    });
  }

  return Boolean(student.student.class_id);
};

const filteredClasses = computed(() => {
  if (!classGradeFilter.value) return classes.value;
  return classes.value.filter(
    (cls) => getClassGrade(cls.name) === classGradeFilter.value,
  );
});

// BUBAT COMPUTED UNTUK LIVE SEARCH & SMART FILTER
const filteredAvailableStudents = computed(() => {
  let filtered = availableStudents.value;
  const academicYearId = selectedClass.value?.academic_year_id;
  const selectedSet = new Set(
    studentForm.student_ids.map((id) => String(id)),
  );

  // 1. Terapkan Smart Filter (Berdasarkan Status Kelas Siswa)
  if (studentFilterMode.value === "unassigned") {
    // Hanya tampilkan siswa yang belum punya kelas di tahun ajaran ini
    filtered = filtered.filter(
      (u) => !isStudentAssignedInAcademicYear(u, academicYearId),
    );
  }

  // 2. Terapkan Filter Pencarian Teks (Live Search)
  if (searchStudentQuery.value) {
    const query = searchStudentQuery.value.toLowerCase();
    filtered = filtered.filter(
      (u) =>
        u.name.toLowerCase().includes(query) ||
        u.email.toLowerCase().includes(query),
    );
  }

  return [...filtered].sort((a, b) => {
    const aSelected = selectedSet.has(String(a.id));
    const bSelected = selectedSet.has(String(b.id));

    if (aSelected !== bSelected) return aSelected ? -1 : 1;

    const aName = (a.name || "").toString();
    const bName = (b.name || "").toString();
    return aName.localeCompare(bName, "id", { sensitivity: "base" });
  });
});

const searchTeacherQuery = ref("");

// BUBAT COMPUTED UNTUK FILTER WALI KELAS (Anti-Rangkap + Live Search)
const filteredTeacherList = computed(() => {
  let available = teacherOptions.value;

  // 1. Filter Anti-Rangkap (Sembunyikan guru yang sudah jadi wali di kelas lain tahun ini)
  if (selectedClass.value) {
    const currentAcademicYearId = selectedClass.value.academic_year_id;
    const currentClassId = selectedClass.value.id;

    const busyTeacherIds = allClasses.value
      .filter(
        (cls) =>
          cls.academic_year_id === currentAcademicYearId &&
          cls.id !== currentClassId &&
          cls.homeroom_teacher_id !== null,
      )
      .map((cls) => cls.homeroom_teacher_id);

    available = available.filter(
      (teacher) => !busyTeacherIds.includes(teacher.value),
    );
  }

  // 2. Filter Live Search
  if (searchTeacherQuery.value) {
    const query = searchTeacherQuery.value.toLowerCase();
    available = available.filter((teacher) =>
      teacher.label.toLowerCase().includes(query),
    );
  }

  return available;
});

const isAllSelected = computed({
  get() {
    // Jika tidak ada data yang tampil, checkbox 'Pilih Semua' tidak dicentang
    if (filteredAvailableStudents.value.length === 0) return false;

    // Centang otomatis JIKA semua siswa yang sedang tampil sudah masuk ke array pilihan
    return filteredAvailableStudents.value.every((student) =>
      studentForm.student_ids.includes(student.id),
    );
  },
  set(value) {
    if (value) {
      // Jika dicentang: Ambil ID dari siswa yang tampil, gabungkan dengan yang sudah ada (hindari duplikat pakai Set)
      const visibleIds = filteredAvailableStudents.value.map((s) => s.id);
      studentForm.student_ids = [
        ...new Set([...studentForm.student_ids, ...visibleIds]),
      ];
    } else {
      // Jika hapus centang: Keluarkan semua ID siswa yang sedang tampil dari array pilihan
      const visibleIds = filteredAvailableStudents.value.map((s) => s.id);
      studentForm.student_ids = studentForm.student_ids.filter(
        (id) => !visibleIds.includes(id),
      );
    }
  },
});

// --- DATA FETCHING ---
const fetchInitialData = async () => {
  isLoading.value = true;
  try {
    // 1. Ambil Data Kelas untuk tabel (Pagination)
    const resClasses = await classService.getAll({
      page: 1,
      per_page: paginationMeta.per_page,
      academic_year_id: academicYearFilter.value || undefined,
      grade: classGradeFilter.value || undefined,
    });
    const classPayload = resClasses.data;
    classes.value = classPayload.data || classPayload;
    paginationMeta.total = classPayload.total ?? classes.value.length;
    paginationMeta.current_page = classPayload.current_page ?? 1;
    paginationMeta.last_page = classPayload.last_page ?? 1;
    paginationMeta.per_page = classPayload.per_page ?? paginationMeta.per_page;

    // 1b. Ambil semua kelas untuk kebutuhan logika internal
    const resAllClasses = await classService.getAll({ per_page: "all" });
    allClasses.value = resAllClasses.data.data || resAllClasses.data;

    // 2. Ambil Data Tahun Ajaran (Untuk Dropdown Buat Kelas)
    const resYears = await api.get("/v1/admin/academic-years");
    const yearsData = resYears.data.data || resYears.data;
    academicYearOptions.value = yearsData.map((y) => ({
      label: `${y.name} ${y.semester === "odd" ? "(Ganjil)" : "(Genap)"} ${y.is_active ? " - Aktif" : ""}`,
      value: y.id,
    }));

    // 3. Ambil Data Guru (Langsung difilter dari Backend & tarik semua data)
    const resTeachers = await userService.getAll({
      role: "teacher",
      per_page: "all",
    });
    const teachersData = resTeachers.data.data || resTeachers.data;
    teacherOptions.value = teachersData.map((t) => ({
      label: t.name,
      value: t.id,
    }));

    // 4. Ambil Data Siswa (Langsung difilter dari Backend & tarik semua data)
    const resStudents = await userService.getAll({
      role: "student",
      per_page: "all",
    });
    availableStudents.value = resStudents.data.data || resStudents.data;
  } catch (error) {
    toastStore.error("Gagal memuat data referensi sistem.");
    console.error(error); // Membantu debug jika ada error di console browser
  } finally {
    isLoading.value = false;
  }
};

const refreshClasses = async () => {
  await fetchClasses(paginationMeta.current_page);
  await fetchAllClasses();
};

const fetchAllClasses = async () => {
  try {
    const resAllClasses = await classService.getAll({ per_page: "all" });
    allClasses.value = resAllClasses.data.data || resAllClasses.data;
  } catch (error) {
    toastStore.error("Gagal memuat data kelas.");
  }
};

const fetchClasses = async (page = 1) => {
  isLoading.value = true;
  try {
    const resClasses = await classService.getAll({
      page: page,
      per_page: paginationMeta.per_page,
      academic_year_id: academicYearFilter.value || undefined,
      grade: classGradeFilter.value || undefined,
    });
    const payload = resClasses.data;
    classes.value = payload.data || payload;
    paginationMeta.total = payload.total ?? classes.value.length;
    paginationMeta.current_page = payload.current_page ?? 1;
    paginationMeta.last_page = payload.last_page ?? 1;
    paginationMeta.per_page = payload.per_page ?? paginationMeta.per_page;
  } catch (error) {
    toastStore.error("Gagal memuat data kelas.");
  } finally {
    isLoading.value = false;
  }
};

const escapeCsvValue = (value) => {
  const normalized = value === null || value === undefined ? '' : String(value);
  return `"${normalized.replaceAll('"', '""')}"`;
};

const downloadCsv = (filename, headers, rows) => {
  const lines = [
    headers.map(escapeCsvValue).join(','),
    ...rows.map((row) => row.map(escapeCsvValue).join(',')),
  ];

  const blob = new Blob([`\uFEFF${lines.join('\r\n')}`], {
    type: 'text/csv;charset=utf-8;',
  });

  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
};

const translateDay = (day) => {
  const mapping = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
  };

  return mapping[day] || day || '-';
};

const formatTime = (time) => (time ? String(time).slice(0, 5) : '-');

const formatAcademicYear = (academicYear) => {
  if (!academicYear) {
    return '-';
  }

  const semesterLabel = academicYear.semester === 'odd'
    ? 'Ganjil'
    : academicYear.semester === 'even'
      ? 'Genap'
      : academicYear.semester || '-';

  return `${academicYear.name || '-'} (${semesterLabel})`;
};

const sanitizeFilename = (value) => {
  return String(value || 'data-kelas')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') || 'data-kelas';
};

const exportClassDetailsCsv = async () => {
  isExportingCsv.value = true;

  try {
    const resClasses = await classService.getAll({
      per_page: 'all',
      academic_year_id: academicYearFilter.value || undefined,
      grade: classGradeFilter.value || undefined,
    });

    const exportClasses = resClasses.data.data || resClasses.data || [];
    const rows = [];

    for (const cls of exportClasses) {
      const detailRes = await classService.getById(cls.id);
      const detail = detailRes.data.data || detailRes.data;
      const academicYear = detail.academic_year || detail.academicYear;
      const homeroomTeacherName = detail.homeroom_teacher?.user?.name || detail.homeroomTeacher?.user?.name || '-';
      const schedules = [...(detail.schedules || [])];
      const scheduleCount = schedules.length;

      rows.push([
        'Ringkasan Kelas',
        detail.name || '-',
        formatAcademicYear(academicYear),
        academicYear?.semester === 'odd' ? 'Ganjil' : academicYear?.semester === 'even' ? 'Genap' : '-',
        homeroomTeacherName,
        detail.students_count ?? detail.students?.length ?? 0,
        scheduleCount,
        '',
        '',
        '',
        '',
      ]);

      if (schedules.length === 0) {
        continue;
      }

      schedules.forEach((schedule) => {
        rows.push([
          'Jadwal',
          detail.name || '-',
          formatAcademicYear(academicYear),
          academicYear?.semester === 'odd' ? 'Ganjil' : academicYear?.semester === 'even' ? 'Genap' : '-',
          homeroomTeacherName,
          detail.students_count ?? detail.students?.length ?? 0,
          scheduleCount,
          translateDay(schedule.day_of_week),
          `${formatTime(schedule.start_time)} - ${formatTime(schedule.end_time)}`,
          schedule.subject?.name || '-',
          schedule.teacher?.user?.name || '-',
        ]);
      });
    }

    const filterParts = [];
    if (academicYearFilter.value) {
      const selectedYear = academicYearOptions.value.find((year) => year.value === academicYearFilter.value);
      filterParts.push(selectedYear?.label || `tahun-${academicYearFilter.value}`);
    }
    if (classGradeFilter.value) {
      filterParts.push(`kelas-${classGradeFilter.value}`);
    }

    const filename = `detail-kelas-${sanitizeFilename(filterParts.join('-') || 'semua')}.csv`;

    downloadCsv(
      filename,
      [
        'Jenis Data',
        'Nama Kelas',
        'Tahun Ajaran',
        'Semester',
        'Wali Kelas',
        'Total Siswa',
        'Total Jadwal',
        'Hari',
        'Waktu',
        'Mata Pelajaran',
        'Guru Pengajar',
      ],
      rows,
    );

    toastStore.success('CSV detail kelas berhasil diekspor.');
  } catch (error) {
    toastStore.error(error.response?.data?.message || 'Gagal mengekspor CSV detail kelas.');
  } finally {
    isExportingCsv.value = false;
  }
};
watch([academicYearFilter, classGradeFilter], () => {
  fetchClasses(1);
});

// --- CRUD KELAS ---
const openClassModal = (cls = null) => {
  isEditing.value = !!cls;
  selectedClass.value = cls;
  classForm.name = cls?.name || "";
  classForm.academic_year_id = cls?.academic_year_id || "";
  isClassModalOpen.value = true;
};

const saveClass = async () => {
  isSaving.value = true;
  try {
    if (isEditing.value) {
      await classService.update(selectedClass.value.id, classForm);
      toastStore.success("Data kelas diperbarui.");
    } else {
      await classService.create(classForm);
      toastStore.success("Kelas baru berhasil dibuat.");
    }
    isClassModalOpen.value = false;
    refreshClasses();
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal menyimpan kelas.");
  } finally {
    isSaving.value = false;
  }
};

const promptDeleteClass = (cls) => {
  confirmModal.targetId = cls.id;
  confirmModal.message = `Hapus kelas "${cls.name}"? Pastikan tidak ada siswa atau jadwal yang terkait dengan kelas ini.`;
  confirmModal.isOpen = true;
};

const executeDeleteClass = async () => {
  confirmModal.isLoading = true;
  try {
    await classService.delete(confirmModal.targetId);
    toastStore.success("Kelas berhasil dihapus.");
    refreshClasses();
  } catch (error) {
    // Tangkap error 403 dari controller jika kelas masih ada siswanya
    toastStore.error(error.response?.data?.error || "Gagal menghapus kelas.");
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

const openTeacherModal = (cls) => {
  selectedClass.value = cls;
  teacherForm.teacher_id = cls.homeroom_teacher?.user_id || "";
  searchTeacherQuery.value = ""; // Reset kolom pencarian
  isTeacherModalOpen.value = true;
};

const saveTeacherAssignment = async () => {
  isSaving.value = true;
  try {
    await classService.assignTeacher(
      selectedClass.value.id,
      teacherForm.teacher_id,
    );
    toastStore.success(
      `Wali kelas untuk ${selectedClass.value.name} berhasil ditetapkan.`,
    );
    isTeacherModalOpen.value = false;
    refreshClasses();
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal menetapkan wali kelas.",
    );
  } finally {
    isSaving.value = false;
  }
};

// --- ASSIGN SISWA ---
const openStudentModal = async (cls) => {
  selectedClass.value = cls;
  studentForm.student_ids = [];
  searchStudentQuery.value = "";
  studentFilterMode.value = cls.students_count > 0 ? "all" : "unassigned";
  isStudentModalOpen.value = true;

  try {
    const res = await classService.getById(cls.id);
    const detail = res.data.data || res.data;
    const existingStudentIds = (detail.students || [])
      .map((student) => student.user_id)
      .filter(Boolean);

    selectedClass.value = detail;
    studentForm.student_ids = [...new Set(existingStudentIds)];

    if (studentForm.student_ids.length > 0) {
      studentFilterMode.value = "all";
    }
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal memuat data kelas.",
    );
  }
};

const saveStudentAssignment = async () => {
  isSaving.value = true;
  try {
    await classService.assignStudents(
      selectedClass.value.id,
      studentForm.student_ids,
    );
    toastStore.success(
      `${studentForm.student_ids.length} siswa berhasil dimasukkan ke kelas.`,
    );
    isStudentModalOpen.value = false;
    refreshClasses();
  } catch (error) {
    // Tangkap error 422 khusus dari service (Siswa tidak aktif)
    toastStore.error(
      error.response?.data?.error ||
        error.response?.data?.message ||
        "Gagal memasukkan siswa.",
    );
  } finally {
    isSaving.value = false;
  }
};

// --- STATE MIGRASI ---
const isMigrateModalOpen = ref(false);
const migrateForm = reactive({
  from_academic_year_id: "",
  to_academic_year_id: ""
});

// --- FUNGSI MIGRASI ---
const openMigrateModal = () => {
  migrateForm.from_academic_year_id = "";
  migrateForm.to_academic_year_id = "";
  isMigrateModalOpen.value = true;
};

const executeMigration = async () => {
  if (migrateForm.from_academic_year_id === migrateForm.to_academic_year_id) {
    toastStore.error("Tahun Ajaran asal dan tujuan tidak boleh sama.");
    return;
  }

  isSaving.value = true;
  try {
    const response = await classService.migrateSemester(migrateForm);
    toastStore.success(response.data.message || "Migrasi semester berhasil!");
    isMigrateModalOpen.value = false;
    refreshClasses(); // Refresh data tabel kelas
  } catch (error) {
    toastStore.error(error.response?.data?.message || "Gagal melakukan migrasi semester.");
  } finally {
    isSaving.value = false;
  }
};

onMounted(() => {
  fetchInitialData();
});
</script>
