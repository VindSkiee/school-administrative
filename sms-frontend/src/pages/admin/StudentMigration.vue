<template>
  <div class="space-y-6">
    <!-- HEADER -->
    <section class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">Migrasi Siswa</h1>
        <p class="text-sm text-gray-500 mt-1">Migrasi semester dan kenaikan kelas dengan evaluasi nilai gabungan.</p>
      </div>
      <div class="w-full lg:w-[320px]">
        <label class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5">Tahun Ajaran Aktif</label>
        <BaseSelect v-model="selectedYearId" :options="yearOptions" placeholder="Pilih Tahun Ajaran" :disabled="isLoadingYears" />
        <p v-if="activeYear" class="text-xs text-gray-500 mt-1.5">
          Semester {{ activeYear.semester === 'odd' ? 'Ganjil' : 'Genap' }}
          <span v-if="activeYear.semester === 'odd'" class="ml-1 inline-flex items-center px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded">MIGRASI SEMESTER</span>
          <span v-else class="ml-1 inline-flex items-center px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded">KENAIKAN KELAS</span>
        </p>
      </div>
    </section>

    <!-- NO ACTIVE YEAR -->
    <section v-if="!selectedYearId" class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-10 text-center">
      <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800 mb-2">Pilih Tahun Ajaran</h3>
      <p class="text-sm text-gray-500 max-w-md mx-auto">Pilih tahun ajaran aktif untuk memulai migrasi semester atau kenaikan kelas.</p>
    </section>

    <template v-else>
      <!-- LOADING -->
      <section v-if="isLoadingPreview" class="bg-white rounded-2xl border border-gray-200 p-10 text-center">
        <div class="inline-flex items-center text-gray-600">
          <svg class="animate-spin h-5 w-5 mr-3 text-brand-red" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          Memuat data migrasi...
        </div>
      </section>

      <template v-else>
        <!-- NOT PUBLISHED WARNING -->
        <section v-if="!isYearPublished" class="rounded-2xl border-2 border-dashed border-amber-300 bg-amber-50 p-10 text-center">
          <div class="w-16 h-16 bg-amber-100 text-amber-500 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
          </div>
          <h3 class="text-lg font-bold text-amber-800 mb-2">Rapor Belum Diterbitkan</h3>
          <p class="text-sm text-amber-700 max-w-md mx-auto">
            Tahun ajaran <strong>{{ activeYear?.name }}</strong> ({{ activeYear?.semester === 'odd' ? 'Ganjil' : 'Genap' }}) belum menerbitkan rapor. Migrasi hanya dapat dilakukan setelah rapor diterbitkan.
          </p>
        </section>

        <!-- ============================================ -->
        <!-- TAB: MIGRASI SEMESTER (odd → even)           -->
        <!-- ============================================ -->
        <section v-else-if="isOddYear" class="space-y-4">
          <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
            <p class="text-sm text-blue-800 font-medium leading-relaxed">
              <strong class="font-bold">Migrasi Semester:</strong> Menduplikasi semua kelas dari semester Ganjil ke Genap beserta Wali Kelas, Siswa, dan Jadwal Pelajaran.
            </p>
          </div>

          <div class="bg-white rounded-2xl border border-gray-200 p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Dari: Semester Ganjil</label>
                <div class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-800 font-medium">{{ activeYear?.name }} (Ganjil)</div>
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Ke: Semester Genap</label>
                <BaseSelect v-model="semesterMigrateForm.to_academic_year_id" :options="semesterTargetOptions" placeholder="Pilih semester genap tujuan..." />
              </div>
            </div>

            <!-- Class table with expandable rows -->
            <div v-if="semesterPreviewClasses.length > 0" class="border border-gray-200 rounded-xl overflow-hidden">
              <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-sm">{{ migrationResult ? 'Hasil Migrasi' : 'Preview Migrasi' }}</h3>
                <span class="text-xs text-gray-500">{{ semesterPreviewClasses.length }} kelas, {{ semesterPreviewStudentCount }} siswa</span>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-brand-red text-white">
                    <tr>
                      <th class="px-4 py-3 text-center w-8"></th>
                      <th class="px-4 py-3 text-left">Kelas</th>
                      <th class="px-4 py-3 text-left">Wali Kelas</th>
                      <th class="px-4 py-3 text-center">Jumlah Siswa</th>
                      <th class="px-4 py-3 text-center">Jadwal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <template v-for="cls in semesterPreviewClasses" :key="cls.id">
                      <tr class="hover:bg-gray-50 cursor-pointer" @click="toggleClassDetail('semester_' + cls.id)">
                        <td class="px-4 py-3 text-center">
                          <svg class="w-4 h-4 text-gray-500 transition-transform duration-150" :class="{ 'rotate-90': expandedClassId === 'semester_' + cls.id }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ cls.name }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ cls.homeroom_teacher?.user?.name || '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ cls.students_count }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ cls.schedules_count }}</td>
                      </tr>
                      <tr v-if="expandedClassId === 'semester_' + cls.id">
                        <td colspan="5" class="p-0">
                          <div class="bg-gray-50 border-t border-b border-gray-200 px-6 py-4">
                            <StudentDetailTable
                              :loading="classDetailLoading['semester_' + cls.id]"
                              :students="classDetailCache['semester_' + cls.id]"
                              :show-status="false"
                            />
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="flex justify-end">
              <button @click="executeSemesterMigration" :disabled="isSaving || !semesterMigrateForm.to_academic_year_id || !!migrationResult"
                class="px-6 py-2.5 bg-brand-orange hover:bg-brand-red text-white font-semibold rounded-lg disabled:opacity-70 flex items-center transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                {{ isSaving ? 'Memigrasi...' : 'Mulai Migrasi Semester' }}
              </button>
            </div>
          </div>
        </section>

        <!-- ============================================ -->
        <!-- TAB: KENAIKAN KELAS (even → new year)        -->
        <!-- ============================================ -->
        <section v-else class="space-y-4">
          <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
            <p class="text-sm text-amber-800 font-medium leading-relaxed">
              <strong class="font-bold">Kenaikan Kelas:</strong> Nilai siswa dievaluasi dari <strong>semester Ganjil dan Genap</strong> menggunakan bobot yang dikonfigurasi.
              Siswa yang semua mapel memenuhi ambang batas (min. 60) akan naik kelas. Siswa yang tidak tallas akan <strong class="font-bold">tetap di tahun ajaran lama</strong> (ulang).
            </p>
          </div>

          <div v-if="!classTargetOptions.length" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
            <p class="text-sm text-gray-500">Buat tahun ajaran baru terlebih dahulu sebelum melakukan kenaikan kelas.</p>
          </div>

          <template v-else>
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
              <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                <div class="w-full sm:w-80">
                  <label class="block text-sm font-semibold text-gray-700 mb-1.5">Tahun Ajaran Tujuan</label>
                  <BaseSelect v-model="classMigrateForm.to_academic_year_id" :options="classTargetOptions" placeholder="Pilih tahun ajaran baru..." />
                </div>
                <button @click="loadClassPreview" :disabled="isLoadingClassPreview || !classMigrateForm.to_academic_year_id || !!migrationResult"
                  class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-sm disabled:opacity-70 flex items-center transition-colors">
                  <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                  Muat Preview
                </button>
              </div>
            </div>

            <!-- Class table with expandable rows -->
            <div v-if="classPreviewLoaded && classPreviewClasses.length > 0" class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
              <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">{{ migrationResult ? 'Hasil Migrasi' : 'Preview Kenaikan Kelas' }}</h3>
                <div class="flex items-center gap-3 text-xs">
                  <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> Naik: {{ classPreviewTotalPromoted }}</span>
                  <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-red-500"></span> Ulang: {{ classPreviewTotalRepeated }}</span>
                  <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Lulus: {{ classPreviewTotalGraduated }}</span>
                </div>
              </div>

              <div v-if="isLoadingClassPreview" class="p-8 text-center text-gray-500 text-sm">Memuat data siswa...</div>

              <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-brand-red text-white">
                    <tr>
                      <th class="px-4 py-3 text-center w-8"></th>
                      <th class="px-4 py-3 text-left">Kelas</th>
                      <th class="px-4 py-3 text-center">Total</th>
                      <th class="px-4 py-3 text-center">Naik</th>
                      <th class="px-4 py-3 text-center">Ulang</th>
                      <th class="px-4 py-3 text-center">Lulus</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <template v-for="cls in classPreviewClasses" :key="cls.class_name">
                      <tr class="hover:bg-gray-50 cursor-pointer" @click="toggleClassDetail('preview_' + cls.class_name)">
                        <td class="px-4 py-3 text-center">
                          <svg class="w-4 h-4 text-gray-500 transition-transform duration-150" :class="{ 'rotate-90': expandedClassId === 'preview_' + cls.class_name }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ cls.class_name }}</td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ cls.students.length }}</td>
                        <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ cls.students.filter(s => s.projected_status === 'promoted').length }}</td>
                        <td class="px-4 py-3 text-center text-red-600 font-semibold">{{ cls.students.filter(s => s.projected_status === 'repeated').length }}</td>
                        <td class="px-4 py-3 text-center text-blue-600 font-semibold">{{ cls.students.filter(s => s.projected_status === 'graduated').length }}</td>
                      </tr>
                      <tr v-if="expandedClassId === 'preview_' + cls.class_name">
                        <td colspan="6" class="p-0">
                          <div class="bg-gray-50 border-t border-b border-gray-200 px-6 py-4">
                            <StudentDetailTable
                              :loading="false"
                              :students="cls.students.map(s => ({ ...s, avg_score: null, grade_index: '-', status: s.projected_status }))"
                              :show-status="true"
                            />
                          </div>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </div>

              <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-sm text-gray-600">Total: <span class="font-bold">{{ classPreviewTotalStudents }}</span> siswa</p>
                <button @click="executeClassMigration" :disabled="isSaving || !classMigrateForm.to_academic_year_id"
                  class="px-6 py-2.5 bg-brand-red hover:bg-brand-orange text-white font-semibold rounded-lg disabled:opacity-70 flex items-center transition-colors">
                  <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                  {{ isSaving ? 'Memigrasi...' : 'Eksekusi Kenaikan Kelas' }}
                </button>
              </div>
            </div>

            <div v-else-if="classPreviewLoaded && !classPreviewClasses.length && !isLoadingClassPreview" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
              <p class="text-sm text-gray-500">Tidak ada siswa ditemukan di tahun ajaran ini.</p>
            </div>
          </template>
        </section>
      </template>
    </template>

    <!-- ============================================ -->
    <!-- MIGRATION HISTORY                            -->
    <!-- ============================================ -->
    <section v-if="selectedYearId" class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 font-serif">Riwayat Migrasi</h2>
        <button @click="loadHistory" :disabled="isLoadingHistory" class="text-sm text-brand-red hover:text-brand-orange font-semibold flex items-center gap-1 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
          Refresh
        </button>
      </div>

      <div v-if="isLoadingHistory" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
        <div class="inline-flex items-center text-gray-500">
          <svg class="animate-spin h-5 w-5 mr-3 text-brand-red" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          Memuat riwayat...
        </div>
      </div>

      <div v-else-if="historyData.length === 0" class="bg-white rounded-2xl border border-gray-200 p-8 text-center">
        <div class="w-12 h-12 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        </div>
        <p class="text-sm text-gray-500">Belum ada riwayat migrasi.</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="log in historyData" :key="log.id" class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition-shadow">
          <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
              <div :class="[
                'w-10 h-10 rounded-lg flex items-center justify-center shrink-0',
                log.action === 'Migrasi Semester' ? 'bg-blue-100' : 'bg-amber-100'
              ]">
                <svg v-if="log.action === 'Migrasi Semester'" class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                <svg v-else class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span :class="[
                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide',
                    log.action === 'Migrasi Semester' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'
                  ]">
                    {{ log.action === 'Migrasi Semester' ? 'Semester' : 'Kenaikan Kelas' }}
                  </span>
                  <span class="text-sm font-semibold text-gray-800 truncate">
                    {{ log.new_values?.from_year_name }} → {{ log.new_values?.to_year_name }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">
                  {{ log.user?.name || '-' }} · {{ formatDateTime(log.created_at) }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-4 text-xs text-gray-600 shrink-0">
              <template v-if="log.action === 'Migrasi Semester'">
                <span><span class="font-bold text-gray-800">{{ log.new_values?.classes_migrated ?? 0 }}</span> kelas</span>
                <span><span class="font-bold text-gray-800">{{ log.new_values?.students_migrated ?? 0 }}</span> siswa</span>
              </template>
              <template v-else>
                <span class="text-green-600"><span class="font-bold">{{ log.new_values?.students_promoted ?? 0 }}</span> naik</span>
                <span v-if="(log.new_values?.students_graduated ?? 0) > 0" class="text-blue-600"><span class="font-bold">{{ log.new_values?.students_graduated }}</span> lulus</span>
                <span v-if="(log.new_values?.students_repeated ?? 0) > 0" class="text-red-600"><span class="font-bold">{{ log.new_values?.students_repeated }}</span> ulang</span>
              </template>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="historyLastPage > 1" class="flex justify-center gap-2 pt-2">
          <button @click="loadHistory(historyPage - 1)" :disabled="historyPage <= 1" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            Sebelumnya
          </button>
          <span class="px-3 py-1.5 text-sm text-gray-600">{{ historyPage }} / {{ historyLastPage }}</span>
          <button @click="loadHistory(historyPage + 1)" :disabled="historyPage >= historyLastPage" class="px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-200 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            Selanjutnya
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, h } from "vue";
import { useToastStore } from "../../stores/toast";
import { useGlobalDropdownsStore } from "../../stores/globalDropdowns";
import { classService } from "../../services/modules/admin/classService";
import BaseSelect from "../../components/BaseSelect.vue";

const toastStore = useToastStore();
const dropdowns = useGlobalDropdownsStore();

// Inline sub-component for student detail table
const StudentDetailTable = {
  props: {
    loading: Boolean,
    students: { type: Array, default: () => [] },
    showStatus: { type: Boolean, default: false },
  },
  setup(props) {
    const gradeIndexClass = (index) => ({
      "bg-green-100 text-green-700": index === "A",
      "bg-blue-100 text-blue-700": index === "B",
      "bg-amber-100 text-amber-700": index === "C",
      "bg-red-100 text-red-700": index === "D",
      "bg-gray-100 text-gray-500": index === "-",
    });
    const statusBadgeClass = (status) => ({
      "bg-green-100 text-green-700": status === "promoted",
      "bg-red-100 text-red-700": status === "repeated",
      "bg-blue-100 text-blue-700": status === "graduated",
    });
    const statusLabel = (status) => {
      if (status === "promoted") return "Naik";
      if (status === "graduated") return "Lulus";
      if (status === "repeated") return "Ulang";
      return "-";
    };

    return () => {
      if (props.loading) {
        return h("div", { class: "flex items-center justify-center py-4 text-gray-500 text-sm" }, [
          h("svg", { class: "animate-spin w-4 h-4 mr-2 text-brand-red", fill: "none", viewBox: "0 0 24 24" }, [
            h("circle", { class: "opacity-25", cx: "12", cy: "12", r: "10", stroke: "currentColor", "stroke-width": "4" }),
            h("path", { class: "opacity-75", fill: "currentColor", d: "M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" }),
          ]),
          "Memuat data siswa...",
        ]);
      }
      if (!props.students?.length) {
        return h("div", { class: "text-center py-4 text-gray-500 text-sm" }, "Tidak ada siswa di kelas ini.");
      }
      return h("div", { class: "overflow-x-auto" }, [
        h("table", { class: "w-full text-sm" }, [
          h("thead", { class: "bg-gray-100" }, [
            h("tr", [
              h("th", { class: "px-4 py-2 text-left font-semibold text-gray-700 w-10" }, "No"),
              h("th", { class: "px-4 py-2 text-left font-semibold text-gray-700" }, "Nama Siswa"),
              h("th", { class: "px-4 py-2 text-center font-semibold text-gray-700" }, "Rata-rata"),
              h("th", { class: "px-4 py-2 text-center font-semibold text-gray-700" }, "Indeks"),
              props.showStatus ? h("th", { class: "px-4 py-2 text-center font-semibold text-gray-700" }, "Status") : null,
            ]),
          ]),
          h("tbody", { class: "divide-y divide-gray-100" },
            props.students.map((s, idx) =>
              h("tr", { key: s.student_id, class: "hover:bg-gray-50" }, [
                h("td", { class: "px-4 py-2 text-gray-600" }, idx + 1),
                h("td", { class: "px-4 py-2 font-medium text-gray-800" }, s.name),
                h("td", { class: "px-4 py-2 text-center text-gray-700" }, s.avg_score ?? "-"),
                h("td", { class: "px-4 py-2 text-center" }, [
                  h("span", { class: `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${gradeIndexClass(s.grade_index)}` }, s.grade_index),
                ]),
                props.showStatus
                  ? h("td", { class: "px-4 py-2 text-center" }, [
                      h("span", { class: `inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${statusBadgeClass(s.status)}` }, statusLabel(s.status)),
                    ])
                  : null,
              ])
            )
          ),
        ]),
      ]);
    };
  },
};

// --- STATE ---
const selectedYearId = ref("");
const isLoadingYears = ref(true);
const isLoadingPreview = ref(false);
const isLoadingClassPreview = ref(false);
const isSaving = ref(false);
const classPreviewLoaded = ref(false);
const expandedClassId = ref(null);

const academicYears = ref([]);
const semesterPreviewClasses = ref([]);
const classPreviewClasses = ref([]);
const migrationResult = ref(null);

const classDetailLoading = reactive({});
const classDetailCache = reactive({});

const semesterMigrateForm = reactive({ to_academic_year_id: "" });
const classMigrateForm = reactive({ to_academic_year_id: "" });

const isLoadingHistory = ref(false);
const historyData = ref([]);
const historyPage = ref(1);
const historyLastPage = ref(1);

// --- COMPUTED ---
const yearOptions = computed(() =>
  academicYears.value.map((y) => ({
    label: `${y.name} (${y.semester === "odd" ? "Ganjil" : "Genap"})${y.is_active ? " — Aktif" : ""}`,
    value: y.id,
  }))
);
const activeYear = computed(() => academicYears.value.find((y) => y.id === selectedYearId.value) || null);
const isOddYear = computed(() => activeYear.value?.semester === "odd");
const isYearPublished = computed(() => activeYear.value?.is_report_published === true);

const semesterTargetOptions = computed(() =>
  academicYears.value.filter((y) => y.semester === "even" && y.id !== selectedYearId.value).map((y) => ({ label: `${y.name} (Genap)`, value: y.id }))
);
const classTargetOptions = computed(() =>
  academicYears.value.filter((y) => !y.is_active && y.id !== selectedYearId.value).map((y) => ({ label: `${y.name} (${y.semester === "odd" ? "Ganjil" : "Genap"})`, value: y.id }))
);

const semesterPreviewStudentCount = computed(() =>
  semesterPreviewClasses.value.reduce((sum, c) => sum + (c.students_count || 0), 0)
);
const classPreviewTotalStudents = computed(() => classPreviewClasses.value.reduce((sum, c) => sum + c.students.length, 0));
const classPreviewTotalPromoted = computed(() => classPreviewClasses.value.reduce((sum, c) => sum + c.students.filter((s) => s.projected_status === "promoted").length, 0));
const classPreviewTotalRepeated = computed(() => classPreviewClasses.value.reduce((sum, c) => sum + c.students.filter((s) => s.projected_status === "repeated").length, 0));
const classPreviewTotalGraduated = computed(() => classPreviewClasses.value.reduce((sum, c) => sum + c.students.filter((s) => s.projected_status === "graduated").length, 0));

// --- METHODS ---
const projectClassName = (name) => {
  const trimmed = name.trim();
  const digitMatch = trimmed.match(/^(\d)(.*)/);
  if (digitMatch) {
    const grade = parseInt(digitMatch[1]);
    if (grade === 9) return { next: "Lulus", status: "graduated" };
    return { next: `${grade + 1}${digitMatch[2]}`, status: "promoted" };
  }
  const romanMap = [
    { roman: "VII", next: "VIII", grade: 7 },
    { roman: "VIII", next: "IX", grade: 8 },
  ];
  for (const { roman, next, grade } of romanMap) {
    if (trimmed.toUpperCase().startsWith(roman)) {
      if (grade === 9) return { next: "Lulus", status: "graduated" };
      return { next: `${next}${trimmed.substring(roman.length)}`, status: "promoted" };
    }
  }
  return { next: "-", status: "promoted" };
};

const toggleClassDetail = async (key) => {
  if (expandedClassId.value === key) {
    expandedClassId.value = null;
    return;
  }
  expandedClassId.value = key;

  // Lazy-load semester class students with grades
  if (key.startsWith("semester_")) {
    const classId = key.replace("semester_", "");
    if (classDetailCache[key] || classDetailLoading[key]) return;
    classDetailLoading[key] = true;
    try {
      const res = await classService.getMigrationStudents(classId);
      classDetailCache[key] = res.data.data || [];
    } catch {
      classDetailCache[key] = [];
    } finally {
      classDetailLoading[key] = false;
    }
  }
};

const loadYearData = async () => {
  if (!selectedYearId.value) return;
  migrationResult.value = null;
  classPreviewLoaded.value = false;
  classPreviewClasses.value = [];
  expandedClassId.value = null;
  Object.keys(classDetailCache).forEach((k) => delete classDetailCache[k]);
  Object.keys(classDetailLoading).forEach((k) => delete classDetailLoading[k]);

  if (isOddYear.value) {
    await loadSemesterPreview();
  }
};

const loadSemesterPreview = async () => {
  isLoadingPreview.value = true;
  try {
    const res = await classService.getAll({ academic_year_id: selectedYearId.value, per_page: 200 });
    semesterPreviewClasses.value = res.data.data || [];
  } catch {
    toastStore.error("Gagal memuat data kelas.");
  } finally {
    isLoadingPreview.value = false;
  }
};

const loadClassPreview = async () => {
  if (!selectedYearId.value) return;
  isLoadingClassPreview.value = true;
  classPreviewLoaded.value = true;
  try {
    const res = await classService.getAll({ academic_year_id: selectedYearId.value, per_page: 200 });
    const classes = res.data.data || [];
    const allClasses = [];
    for (const cls of classes) {
      try {
        const studentRes = await classService.getStudentOptions(cls.id);
        const classStudents = studentRes.data.class_students || [];
        const students = classStudents.map((s) => {
          const proj = projectClassName(cls.name);
          return { student_id: s.id, name: s.name, projected_class: proj.next, projected_status: proj.status };
        });
        allClasses.push({ class_name: cls.name, students });
      } catch {
        // Skip
      }
    }
    allClasses.sort((a, b) => a.class_name.localeCompare(b.class_name));
    classPreviewClasses.value = allClasses;
  } catch {
    toastStore.error("Gagal memuat preview kenaikan kelas.");
  } finally {
    isLoadingClassPreview.value = false;
  }
};

const executeSemesterMigration = async () => {
  if (!semesterMigrateForm.to_academic_year_id) { toastStore.error("Pilih tahun ajaran tujuan."); return; }
  if (semesterMigrateForm.to_academic_year_id === selectedYearId.value) { toastStore.error("Tahun ajaran asal dan tujuan tidak boleh sama."); return; }
  isSaving.value = true;
  try {
    const res = await classService.migrateSemester({ from_academic_year_id: selectedYearId.value, to_academic_year_id: semesterMigrateForm.to_academic_year_id });
    toastStore.success(res.data.message || "Migrasi semester berhasil!");
    migrationResult.value = res.data;
    semesterMigrateForm.to_academic_year_id = "";
    await refreshYears();
    loadHistory();
  } catch (e) {
    toastStore.error(e.response?.data?.message || "Gagal melakukan migrasi semester.");
  } finally {
    isSaving.value = false;
  }
};

const executeClassMigration = async () => {
  if (!classMigrateForm.to_academic_year_id) { toastStore.error("Pilih tahun ajaran tujuan."); return; }
  isSaving.value = true;
  try {
    const res = await classService.migrateClass({ to_academic_year_id: classMigrateForm.to_academic_year_id });
    toastStore.success(res.data.message || "Kenaikan kelas berhasil!");
    migrationResult.value = res.data;
    classPreviewLoaded.value = false;
    classPreviewClasses.value = [];
    expandedClassId.value = null;
    await refreshYears();
    loadHistory();
  } catch (e) {
    toastStore.error(e.response?.data?.message || "Gagal melakukan kenaikan kelas.");
  } finally {
    isSaving.value = false;
  }
};

const refreshYears = async () => {
  await dropdowns.ensureAcademicYears();
  academicYears.value = [...dropdowns.academicYearsRaw];
};

const loadHistory = async (page = 1) => {
  isLoadingHistory.value = true;
  try {
    const res = await classService.getMigrationHistory({ page, per_page: 10 });
    historyData.value = res.data.data || [];
    historyPage.value = res.data.current_page || 1;
    historyLastPage.value = res.data.last_page || 1;
  } catch {
    historyData.value = [];
  } finally {
    isLoadingHistory.value = false;
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
  const hours = String(d.getHours()).padStart(2, "0");
  const minutes = String(d.getMinutes()).padStart(2, "0");
  return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} ${hours}:${minutes}`;
};

// --- WATCHERS ---
watch(selectedYearId, () => { loadYearData(); });

// --- INIT ---
onMounted(async () => {
  isLoadingYears.value = true;
  try {
    await dropdowns.ensureAcademicYears();
    academicYears.value = [...dropdowns.academicYearsRaw];
    const active = academicYears.value.find((y) => y.is_active);
    if (active) selectedYearId.value = active.id;
    loadHistory();
  } catch {
    toastStore.error("Gagal memuat data tahun ajaran.");
  } finally {
    isLoadingYears.value = false;
  }
});
</script>
