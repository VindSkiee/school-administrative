<template>
  <div class="space-y-6">
    <section
      class="rounded-2xl bg-white p-6 shadow-sm border border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif">
          Laporan &amp; Rapor Semester
        </h1>
        <p class="text-sm text-gray-500 mt-1">
          Analitik kehadiran, performa akademik, dan distribusi rapor siswa.
        </p>
      </div>

      <div class="w-full lg:w-[320px]">
        <label
          class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5"
        >
          Pilih Tahun Ajaran
        </label>
        <BaseSelect
          v-model="selectedAcademicYearId"
          :options="academicYearOptions"
          placeholder="Pilih Tahun Ajaran"
          :disabled="isLoadingInitial || isLoadingYears"
        />
      </div>
    </section>

    <section class="bg-white rounded-2xl border border-gray-200 p-2">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="px-4 py-3 rounded-xl text-sm font-semibold transition-colors"
          :class="
            activeTab === tab.key
              ? 'bg-brand-red text-white shadow-sm'
              : 'bg-gray-50 text-gray-700 hover:bg-gray-100'
          "
        >
          {{ tab.label }}
        </button>
      </div>
    </section>

    <section
      v-if="isLoadingInitial"
      class="bg-white rounded-2xl border border-gray-200 p-10 text-center"
    >
      <div class="inline-flex items-center text-gray-600">
        <svg
          class="animate-spin h-5 w-5 mr-3 text-brand-red"
          viewBox="0 0 24 24"
          fill="none"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
          />
        </svg>
        Memuat data laporan...
      </div>
    </section>

    <template v-else>
      <!-- No year selected: show prompt to select year -->
      <section
        v-if="!selectedAcademicYearId"
        class="rounded-2xl border-2 border-dashed border-gray-300 bg-gray-50 p-10 text-center"
      >
        <div
          class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4"
        >
          <svg
            class="w-8 h-8"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2">Pilih Tahun Ajaran</h3>
        <p class="text-sm text-gray-500 max-w-md mx-auto">
          Silakan pilih tahun ajaran terlebih dahulu pada dropdown di atas untuk
          menampilkan data laporan dan distribusi rapor.
        </p>
      </section>

      <template v-else>
        <section v-if="activeTab === 'attendance'" class="space-y-4">
          <div
            v-if="isLoadingReports"
            class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-600"
          >
            Memuat ringkasan kehadiran...
          </div>

          <template v-else>
            <div
              v-if="attendanceRows.length"
              class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"
            >
              <article
                v-for="card in attendanceCards"
                :key="card.label"
                class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm"
              >
                <p
                  class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                  {{ card.label }}
                </p>
                <p class="text-3xl font-bold text-gray-800 mt-2">
                  {{ card.value }}
                </p>
              </article>
            </div>

            <div
              class="bg-white rounded-2xl border border-gray-200 overflow-hidden"
            >
              <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Ringkasan Per Kelas</h2>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-brand-red text-white">
                    <tr>
                      <th class="px-4 py-3 text-left">Kelas</th>
                      <th class="px-4 py-3 text-center">Total Rekaman</th>
                      <th class="px-4 py-3 text-center">Hadir</th>
                      <th class="px-4 py-3 text-center">Izin</th>
                      <th class="px-4 py-3 text-center">Sakit</th>
                      <th class="px-4 py-3 text-center">Alpa</th>
                      <th class="px-4 py-3 text-center">Terlambat</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr
                      v-for="row in attendanceRows"
                      :key="row.class_id"
                      class="hover:bg-gray-50"
                    >
                      <td class="px-4 py-3 font-medium text-gray-800">
                        {{ row.class_name }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_records) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_present) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_permission) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_sick) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_alpa) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_late) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div
              v-if="!attendanceRows.length"
              class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500"
            >
              Data kehadiran belum tersedia untuk Tahun Ajaran yang dipilih.
            </div>
          </template>
        </section>

        <section v-if="activeTab === 'academic'" class="space-y-4">
          <div
            v-if="isLoadingReports"
            class="bg-white rounded-2xl border border-gray-200 p-8 text-center text-gray-600"
          >
            Memuat ringkasan akademik...
          </div>

          <template v-else>
            <div
              v-if="academicRows.length"
              class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4"
            >
              <article
                v-for="card in academicCards"
                :key="card.label"
                class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm"
              >
                <p
                  class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                >
                  {{ card.label }}
                </p>
                <p class="text-3xl font-bold text-gray-800 mt-2">
                  {{ card.value }}
                </p>
              </article>
            </div>

            <div
              class="bg-white rounded-2xl border border-gray-200 overflow-hidden"
            >
              <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">
                  Statistik Nilai Per Kelas
                </h2>
              </div>
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead class="bg-brand-red text-white">
                    <tr>
                      <th class="px-4 py-3 text-left">Kelas</th>
                      <th class="px-4 py-3 text-center">Total Tugas</th>
                      <th class="px-4 py-3 text-center">Submission Dinilai</th>
                      <th class="px-4 py-3 text-center">Rata-rata Nilai</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-100">
                    <tr
                      v-for="row in academicRows"
                      :key="row.class_id"
                      class="hover:bg-gray-50"
                    >
                      <td class="px-4 py-3 font-medium text-gray-800">
                        {{ row.class_name }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_assignments) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ toNumber(row.total_graded_submissions) }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        {{ formatScore(row.average_class_score) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div
              v-if="!academicRows.length"
              class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center text-gray-500"
            >
              Data akademik belum tersedia untuk Tahun Ajaran yang dipilih.
            </div>
          </template>
        </section>

        <section v-if="activeTab === 'distribution'" class="space-y-6">
          <!-- CLASS READINESS SECTION -->
          <div
            class="rounded-2xl border border-gray-200 bg-white overflow-hidden"
          >
            <div
              class="px-4 py-3 border-b border-gray-100 flex items-center justify-between"
            >
              <div class="flex items-center gap-2">
                <h2 class="font-semibold text-gray-800">
                  Kesiapan Kelas untuk Publikasi
                </h2>
                <BasePopoverInfo>
                  <p class="mb-1.5"><strong>Kolom Kesiapan:</strong></p>
                  <ul class="list-disc list-inside mb-1.5 space-y-0.5">
                    <li><strong>Kehadiran</strong> = Status pencatatan absensi untuk semua pertemuan yang sudah terjadwal.</li>
                    <li><strong>Nilai</strong> = Status pengisian nilai untuk tugas, UTS, dan UAS yang sudah dibuat.</li>
                    <li><strong>Siswa (X/Y)</strong> = X = jumlah siswa yang sudah lengkap data absensi dan nilainya; Y = total siswa di kelas ini.</li>
                    <li><strong>Status</strong> = "Siap" jika semua kriteria terpenuhi, "Sudah Diterbitkan" jika sudah diterbitkan, atau "Belum Siap".</li>
                  </ul>
                  <p><strong>Catatan:</strong> Kelas baru bisa dipublikasikan jika semua kriteria kesiapan terpenuhi (Lengkap/Siap).</p>
                </BasePopoverInfo>
              </div>
              <div class="flex items-center gap-3">
                <span
                  v-if="classesSummary.total_classes > 0"
                  class="text-xs text-gray-500"
                >
                  {{ classesSummary.published_classes }}/{{
                    classesSummary.total_classes
                  }}
                  kelas sudah dipublikasikan
                </span>
                <button
                  v-if="
                    classesSummary.total_classes > 0 &&
                    !classesSummary.can_publish_all &&
                    classesSummary.ready_classes >
                      classesSummary.published_classes
                  "
                  @click="handlePublishAllClasses"
                  :disabled="isLoadingClassesReadiness"
                  class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand-red hover:bg-red-700 text-white text-xs font-semibold transition-colors disabled:opacity-50"
                >
                  Publikasikan Semua Kelas Siap
                </button>
              </div>
            </div>

            <div
              v-if="isLoadingClassesReadiness"
              class="p-8 text-center text-gray-500"
            >
              Memuat status kesiapan kelas...
            </div>

            <div
              v-else-if="!classesReadiness.length"
              class="p-8 text-center text-gray-500"
            >
              Tidak ada kelas ditemukan untuk tahun ajaran ini.
            </div>

            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-brand-red text-white">
                  <tr>
                    <th class="px-4 py-3 text-center w-8"></th>
                    <th class="px-4 py-3 text-left">Kelas</th>
                    <th class="px-4 py-3 text-left">Wali Kelas</th>
                    <th class="px-4 py-3 text-center">Kehadiran</th>
                    <th class="px-4 py-3 text-center">Nilai</th>
                    <th class="px-4 py-3 text-center">Siswa</th>
                    <th class="px-4 py-3 text-center">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <template v-for="cls in classesReadiness" :key="cls.class_id">
                    <!-- Main row -->
                    <tr
                      class="hover:bg-gray-50 cursor-pointer"
                      @click="toggleClassDetail(cls.class_id)"
                    >
                      <td class="px-4 py-3 text-center">
                        <svg
                          class="w-4 h-4 text-gray-500 transition-transform duration-150"
                          :class="{
                            'rotate-90': expandedClassId === cls.class_id,
                          }"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                          />
                        </svg>
                      </td>
                      <td class="px-4 py-3 font-medium text-gray-800">
                        {{ cls.class_name }}
                      </td>
                      <td class="px-4 py-3 text-gray-600">
                        {{ cls.homeroom_teacher }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        <span
                          v-if="cls.readiness.attendance.is_complete"
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"
                        >
                          Lengkap
                        </span>
                        <span
                          v-else
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                        >
                          Belum
                        </span>
                      </td>
                      <td class="px-4 py-3 text-center">
                        <span
                          v-if="cls.readiness.grades.is_complete"
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"
                        >
                          Lengkap
                        </span>
                        <span
                          v-else
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                        >
                          Belum
                        </span>
                      </td>
                      <td class="px-4 py-3 text-center text-gray-600">
                        {{ cls.readiness.students.ready }}/{{
                          cls.readiness.students.total
                        }}
                      </td>
                      <td class="px-4 py-3 text-center">
                        <span
                          v-if="cls.is_published"
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"
                        >
                          Sudah Diterbitkan
                        </span>
                        <span
                          v-else-if="cls.is_ready"
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700"
                        >
                          Siap
                        </span>
                        <span
                          v-else
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500"
                        >
                          Belum Siap
                        </span>
                      </td>
                      <td class="px-4 py-3 text-center" @click.stop>
                        <button
                          v-if="!cls.is_published && cls.is_ready"
                          @click="
                            handlePublishClass(cls.class_id, cls.class_name)
                          "
                          :disabled="isPublishingClass[cls.class_id]"
                          class="inline-flex items-center px-3 py-1.5 rounded-lg bg-brand-red hover:bg-red-700 text-white text-xs font-semibold transition-colors disabled:opacity-50"
                        >
                          {{
                            isPublishingClass[cls.class_id]
                              ? "Memproses..."
                              : "Publish"
                          }}
                        </button>
                        <span
                          v-else-if="cls.is_published"
                          class="text-xs text-green-600 font-semibold"
                        >
                          {{
                            cls.published_at
                              ? new Date(cls.published_at).toLocaleDateString(
                                  "id-ID",
                                )
                              : ""
                          }}
                        </span>
                        <span v-else class="text-xs text-gray-400">-</span>
                      </td>
                    </tr>

                    <!-- Expanded detail row -->
                    <tr v-if="expandedClassId === cls.class_id">
                      <td colspan="8" class="p-0">
                        <div
                          class="bg-gray-50 border-t border-b border-gray-200 px-6 py-4 relative"
                        >
                          <div
                            v-if="
                              classDetailLoading[cls.class_id] &&
                              !classDetailCache[cls.class_id]
                            "
                            class="flex items-center justify-center py-6 text-gray-500 text-sm"
                          >
                            <svg
                              class="animate-spin w-4 h-4 mr-2 text-brand-red"
                              fill="none"
                              viewBox="0 0 24 24"
                            >
                              <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                              ></circle>
                              <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                              ></path>
                            </svg>
                            Memuat detail kesiapan...
                          </div>

                          <div
                            v-else-if="
                              !classDetailLoading[cls.class_id] &&
                              !classDetailCache[cls.class_id]
                            "
                            class="text-center py-6 text-gray-500 text-sm"
                          >
                            Tidak ada data detail.
                          </div>

                          <div v-else>
                            <div
                              v-if="classDetailLoading[cls.class_id]"
                              class="absolute inset-0 z-10 bg-gray-50/60 backdrop-blur-[1px] flex items-center justify-center transition-all duration-200"
                            >
                              <div
                                class="bg-white px-4 py-2 rounded-full shadow-md border border-gray-100 flex items-center text-sm font-medium text-gray-600"
                              >
                                <svg
                                  class="animate-spin w-4 h-4 mr-2 text-brand-red"
                                  fill="none"
                                  viewBox="0 0 24 24"
                                >
                                  <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                  ></circle>
                                  <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                  ></path>
                                </svg>
                                Memperbarui data...
                              </div>
                            </div>

                            <div class="flex items-center justify-between mb-3">
                              <div class="flex items-center gap-2">
                                <h4 class="text-sm font-semibold text-gray-700">
                                  Detail Kesiapan — {{ cls.class_name }}
                                </h4>
                                <BasePopoverInfo>
                                  <p class="mb-1.5"><strong>Kehadiran (X/Y):</strong></p>
                                  <ul class="list-disc list-inside mb-1.5 space-y-0.5">
                                    <li><strong>X</strong> = jumlah pertemuan yang sudah memiliki data absensi (sudah direkam guru).</li>
                                    <li><strong>Y</strong> = jumlah pertemuan yang sudah terjadwal sampai hari ini (belum termasuk pertemuan masa depan dan hari libur).</li>
                                  </ul>
                                  <p class="mb-1.5"><strong>Tugas / UTS / UAS (X/Y):</strong></p>
                                  <ul class="list-disc list-inside mb-1.5 space-y-0.5">
                                    <li><strong>X</strong> = jumlah assignment yang sudah dinilai (memiliki grade).</li>
                                    <li><strong>Y</strong> = jumlah assignment yang sudah dibuat untuk mapel ini.</li>
                                  </ul>
                                  <p class="mb-1.5"><strong>Capaian Kompetensi:</strong></p>
                                  <ul class="list-disc list-inside mb-1.5 space-y-0.5">
                                    <li><strong>Terkonfigurasi</strong> = Pengaturan capaian kompetensi sudah diatur di menu Mata Pelajaran → Detail.</li>
                                    <li><strong>Belum</strong> = Pengaturan capaian kompetensi belum diatur. Wajib diatur sebelum download rapor.</li>
                                  </ul>
                                  <p><strong>Status:</strong> "Siap" jika kehadiran, tugas, UTS, UAS, dan capaian kompetensi untuk mapel ini sudah lengkap (X = Y).</p>
                                </BasePopoverInfo>
                              </div>
                              <button
                                @click="refreshClassDetail(cls.class_id)"
                                :disabled="classDetailLoading[cls.class_id]"
                                class="inline-flex items-center px-2.5 py-1 rounded-md bg-white border border-gray-200 hover:bg-gray-100 text-gray-600 text-xs font-medium transition-colors disabled:opacity-50"
                                title="Muat ulang data"
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
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                  />
                                </svg>
                                Refresh
                              </button>
                            </div>

                            <div
                              class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4"
                            >
                              <div
                                class="bg-white rounded-lg border border-gray-200 px-3 py-2 text-center"
                              >
                                <div class="text-lg font-bold text-gray-800">
                                  {{
                                    classDetailCache[cls.class_id]?.summary
                                      ?.ready_subjects || 0
                                  }}/{{
                                    classDetailCache[cls.class_id]?.summary
                                      ?.total_subjects || 0
                                  }}
                                </div>
                                <div class="text-xs text-gray-500">
                                  Mapel Siap
                                </div>
                              </div>
                              <div
                                class="bg-white rounded-lg border border-gray-200 px-3 py-2 text-center"
                              >
                                <div class="text-lg font-bold text-gray-800">
                                  {{
                                    classDetailCache[cls.class_id]?.summary
                                      ?.students_ready || 0
                                  }}/{{
                                    classDetailCache[cls.class_id]?.summary
                                      ?.students_total || 0
                                  }}
                                </div>
                                <div class="text-xs text-gray-500">
                                  Siswa Siap
                                </div>
                              </div>
                            </div>

                            <div
                              class="bg-white rounded-lg border border-gray-200 overflow-hidden"
                            >
                              <table class="w-full text-xs">
                                <thead>
                                  <tr class="border-b border-gray-200">
                                    <th
                                      class="px-3 py-2 text-left font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      Mapel
                                    </th>
                                    <th
                                      class="px-3 py-2 text-left font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      Guru
                                    </th>
                                    <th
                                      class="px-3 py-2 text-right font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      Kehadiran
                                    </th>
                                    <th
                                      class="px-3 py-2 text-right font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      Tugas
                                    </th>
                                    <th
                                      class="px-3 py-2 text-right font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      UTS
                                    </th>
                                    <th
                                      class="px-3 py-2 text-right font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      UAS
                                    </th>
                                    <th
                                      class="px-3 py-2 text-center font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      Capaian Kompetensi
                                    </th>
                                    <th
                                      class="px-3 py-2 text-right font-medium text-gray-400 text-[11px] uppercase tracking-wide"
                                    >
                                      Status
                                    </th>
                                  </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                  <tr
                                    v-for="subj in classDetailCache[
                                      cls.class_id
                                    ]?.subjects || []"
                                    :key="subj.schedule_id"
                                    class="hover:bg-gray-50"
                                  >
                                    <td
                                      class="px-3 py-2.5 font-medium text-gray-900"
                                    >
                                      {{ subj.subject }}
                                    </td>
                                    <td class="px-3 py-2.5 text-gray-500">
                                      {{ subj.teacher }}
                                    </td>

                                    <td class="px-3 py-2.5">
                                      <div
                                        class="flex items-center justify-end gap-2"
                                      >
                                        <span
                                          class="tabular-nums"
                                          :class="
                                            subj.attendance.is_complete
                                              ? 'text-gray-700'
                                              : 'text-red-600 font-medium'
                                          "
                                        >
                                          {{ subj.attendance.recorded }}/{{
                                            subj.attendance.total_meetings
                                          }}
                                        </span>
                                        <div
                                          class="w-7 h-[3px] rounded-full bg-gray-100 overflow-hidden shrink-0"
                                        >
                                          <div
                                            class="h-full rounded-full transition-all duration-300"
                                            :class="
                                              subj.attendance.is_complete
                                                ? 'bg-green-300'
                                                : 'bg-red-400'
                                            "
                                            :style="{
                                              width:
                                                subj.attendance.percentage +
                                                '%',
                                            }"
                                          ></div>
                                        </div>
                                      </div>
                                    </td>

                                    <td
                                      class="px-3 py-2.5 text-right tabular-nums"
                                    >
                                      <template v-if="subj.grades.task.exists">
                                        <span
                                          :class="
                                            subj.grades.task.is_complete
                                              ? 'text-gray-700'
                                              : 'text-red-600 font-medium'
                                          "
                                        >
                                          {{ subj.grades.task.graded }}/{{
                                            subj.grades.task.total
                                          }}
                                        </span>
                                        <span
                                          v-if="
                                            subj.grades.task.average !== null
                                          "
                                          class="text-gray-400 ml-1"
                                          >·
                                          {{ subj.grades.task.average }}</span
                                        >
                                      </template>
                                      <span v-else class="text-gray-300"
                                        >—</span
                                      >
                                    </td>

                                    <td
                                      class="px-3 py-2.5 text-right tabular-nums"
                                    >
                                      <template v-if="subj.grades.uts.exists">
                                        <span
                                          :class="
                                            subj.grades.uts.is_complete
                                              ? 'text-gray-700'
                                              : 'text-red-600 font-medium'
                                          "
                                        >
                                          {{ subj.grades.uts.graded }}/{{
                                            subj.grades.uts.total
                                          }}
                                        </span>
                                        <span
                                          v-if="
                                            subj.grades.uts.average !== null
                                          "
                                          class="text-gray-400 ml-1"
                                          >· {{ subj.grades.uts.average }}</span
                                        >
                                      </template>
                                      <span v-else class="text-gray-300"
                                        >—</span
                                      >
                                    </td>

                                    <td
                                      class="px-3 py-2.5 text-right tabular-nums"
                                    >
                                      <template v-if="subj.grades.uas.exists">
                                        <span
                                          :class="
                                            subj.grades.uas.is_complete
                                              ? 'text-gray-700'
                                              : 'text-red-600 font-medium'
                                          "
                                        >
                                          {{ subj.grades.uas.graded }}/{{
                                            subj.grades.uas.total
                                          }}
                                        </span>
                                        <span
                                          v-if="
                                            subj.grades.uas.average !== null
                                          "
                                          class="text-gray-400 ml-1"
                                          >· {{ subj.grades.uas.average }}</span
                                        >
                                      </template>
                                      <span v-else class="text-gray-300"
                                        >—</span
                                      >
                                    </td>

                                    <td class="px-3 py-2.5 text-center">
                                      <span
                                        class="inline-flex items-center gap-1.5"
                                      >
                                        <span
                                          class="w-1.5 h-1.5 rounded-full shrink-0"
                                          :class="
                                            subj.competency_configured
                                              ? 'bg-emerald-500'
                                              : 'bg-red-400'
                                          "
                                        ></span>
                                        <span class="text-gray-600">{{
                                          subj.competency_configured
                                            ? "Terkonfigurasi"
                                            : "Belum"
                                        }}</span>
                                      </span>
                                    </td>

                                    <td class="px-3 py-2.5 text-right">
                                      <span
                                        class="inline-flex items-center gap-1.5"
                                      >
                                        <span
                                          class="w-1.5 h-1.5 rounded-full shrink-0"
                                          :class="
                                            subj.is_subject_ready
                                              ? 'bg-emerald-500'
                                              : 'bg-red-400'
                                          "
                                        ></span>
                                        <span class="text-gray-600">{{
                                          subj.is_subject_ready
                                            ? "Siap"
                                            : "Belum"
                                        }}</span>
                                      </span>
                                    </td>
                                  </tr>

                                    <tr
                                      v-if="
                                        !classDetailCache[cls.class_id]?.subjects
                                          ?.length
                                      "
                                    >
                                      <td
                                        colspan="8"
                                        class="px-3 py-8 text-center text-gray-400"
                                      >
                                      Tidak ada jadwal untuk kelas ini.
                                    </td>
                                  </tr>
                                </tbody>
                              </table>
                            </div>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </template>
                </tbody>
              </table>
            </div>
          </div>

          <!-- STUDENT DISTRIBUTION TABLE (for PDF download) -->
          <div
            class="bg-white rounded-2xl border border-gray-200 overflow-hidden"
          >
            <div
              class="px-4 py-3 border-b border-gray-100 flex items-center justify-between"
            >
              <h2 class="font-semibold text-gray-800">
                Distribusi Rapor Siswa
              </h2>
              <span class="text-xs text-gray-500"
                >{{ filteredDistributionStudents.length }} siswa</span
              >
            </div>

            <div class="p-4 border-b border-gray-100 bg-gray-50">
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <div class="lg:col-span-2">
                  <label
                    class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5"
                  >
                    Pencarian Siswa
                  </label>
                  <div class="relative">
                    <svg
                      class="w-5 h-5 absolute left-3 top-3 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                      />
                    </svg>
                    <input
                      v-model="distributionSearchQuery"
                      type="text"
                      placeholder="Cari berdasarkan nama siswa, NIS, atau NISN..."
                      class="w-full pl-10 pr-3 py-2 border border-gray-200 rounded-lg focus:ring-1 focus:ring-brand-red outline-none transition-colors"
                    />
                  </div>
                </div>

                <div>
                  <label
                    class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5"
                  >
                    Filter Kelas
                  </label>
                  <BaseSelect
                    v-model="selectedDistributionClassId"
                    :options="distributionClassOptions"
                    placeholder="Semua Kelas"
                    :disabled="
                      isLoadingStudents || isLoadingDistributionClasses
                    "
                  />
                </div>
              </div>
            </div>

            <!-- Prompt: no filter active -->
            <div v-if="!hasActiveFilter" class="px-6 py-10 text-center">
              <div
                class="w-14 h-14 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-3"
              >
                <svg
                  class="w-7 h-7"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>
              </div>
              <p class="text-sm text-gray-500">
                Ketik minimal 2 karakter untuk mencari siswa, atau pilih kelas
                untuk melihat semua siswa.
              </p>
            </div>

            <!-- Table: only when filter active -->
            <div v-else class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-brand-red text-white">
                  <tr>
                    <th class="px-4 py-3 text-left">NIS</th>
                    <th class="px-4 py-3 text-left">Nama Siswa</th>
                    <th class="px-4 py-3 text-left">Kelas</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-if="isLoadingStudents">
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                      Memuat data siswa...
                    </td>
                  </tr>

                  <tr v-else-if="!filteredDistributionStudents.length">
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                      Data siswa tidak ditemukan untuk kombinasi filter saat
                      ini.
                    </td>
                  </tr>

                  <tr
                    v-else
                    v-for="student in distributionStudentsPage"
                    :key="student.student_id"
                    class="hover:bg-gray-50"
                  >
                    <td class="px-4 py-3 font-medium text-gray-800">
                      {{ student.nis || "-" }}
                    </td>
                    <td class="px-4 py-3 text-gray-700">{{ student.name }}</td>
                    <td class="px-4 py-3 text-gray-600">
                      {{ getStudentClassName(student) }}
                    </td>
                    <td class="px-4 py-3 text-center">
                      <div class="flex flex-col items-center gap-1">
                        <button
                          @click="handleDownloadPdf(student)"
                          :disabled="
                            downloadLoadingMap[student.student_id] ||
                            !selectedAcademicYearId ||
                            !student.is_ready
                          "
                          class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-brand-orange hover:bg-brand-red text-white font-semibold transition-colors disabled:opacity-70 disabled:cursor-not-allowed"
                        >
                          <svg
                            v-if="downloadLoadingMap[student.student_id]"
                            class="animate-spin h-4 w-4 mr-2"
                            viewBox="0 0 24 24"
                            fill="none"
                          >
                            <circle
                              class="opacity-25"
                              cx="12"
                              cy="12"
                              r="10"
                              stroke="currentColor"
                              stroke-width="4"
                            />
                            <path
                              class="opacity-75"
                              fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                            />
                          </svg>
                          {{
                            downloadLoadingMap[student.student_id]
                              ? "Menyiapkan PDF..."
                              : "Download PDF"
                          }}
                        </button>

                        <p
                          v-if="!student.is_ready && student.missing_info"
                          class="text-xs text-red-600 leading-snug"
                        >
                          {{ student.missing_info }}
                        </p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div
              v-if="filteredDistributionStudents.length"
              class="px-4 py-3 border-t border-gray-100 bg-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
            >
              <p class="text-sm text-gray-600">
                Menampilkan
                <span class="font-semibold">{{ distributionStartItem }}</span>
                -
                <span class="font-semibold">{{ distributionEndItem }}</span>
                dari
                <span class="font-semibold">{{
                  filteredDistributionStudents.length
                }}</span>
                siswa
              </p>

              <div class="flex items-center gap-2">
                <button
                  @click="
                    changeDistributionPage(
                      distributionPagination.currentPage - 1,
                    )
                  "
                  :disabled="distributionPagination.currentPage === 1"
                  class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Sebelumnya
                </button>

                <span class="text-sm font-semibold text-gray-700">
                  Halaman {{ distributionPagination.currentPage }} /
                  {{ distributionTotalPages }}
                </span>

                <button
                  @click="
                    changeDistributionPage(
                      distributionPagination.currentPage + 1,
                    )
                  "
                  :disabled="
                    distributionPagination.currentPage ===
                    distributionTotalPages
                  "
                  class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Selanjutnya
                </button>
              </div>
            </div>
          </div>
        </section>
      </template>
    </template>

    <ConfirmModal
      :isOpen="publishAllConfirmModal.isOpen"
      :isLoading="publishAllConfirmModal.isLoading"
      title="Publikasikan Semua Kelas"
      :message="`Publikasikan semua kelas yang siap? Kelas yang sudah dipublikasikan akan dilewati. Siap: ${classesSummary.ready_classes - classesSummary.published_classes} kelas.`"
      confirmText="Ya, Publikasikan Semua!"
      @confirm="executePublishAllClasses"
      @cancel="publishAllConfirmModal.isOpen = false"
    />
  </div>
</template>

<script setup>
import {
  computed,
  onMounted,
  onUnmounted,
  onActivated,
  reactive,
  ref,
  watch,
} from "vue";
import BaseSelect from "../../components/BaseSelect.vue";
import BasePopoverInfo from "../../components/BasePopoverInfo.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";
import { useToastStore } from "../../stores/toast";
import { useGlobalDropdownsStore } from "../../stores/globalDropdowns";
import { academicYearService } from "../../services/modules/admin/academicYearService";
import { reportService } from "../../services/modules/admin/reportService";
import api from "../../services/api"; // PERF FIX: direct api for lightweight /classes/options

const toastStore = useToastStore();
const dropdowns = useGlobalDropdownsStore();

const tabs = [
  { key: "attendance", label: "Ringkasan Kehadiran" },
  { key: "academic", label: "Ringkasan Akademik" },
  { key: "distribution", label: "Distribusi Rapor" },
];

const activeTab = ref("attendance");
const selectedAcademicYearId = ref("");

const academicYears = ref([]);
const attendanceRows = ref([]);
const academicRows = ref([]);
const students = ref([]);
const distributionClassOptions = ref([]);
const isAllStudentsReady = ref(false);

// Per-class readiness state
const classesReadiness = ref([]);
const classesSummary = ref({
  total_classes: 0,
  ready_classes: 0,
  published_classes: 0,
  can_publish_all: false,
  is_report_published: false,
});
const isLoadingClassesReadiness = ref(false);
const isPublishingClass = reactive({});

// Expandable detail state (per-class dropdown)
const expandedClassId = ref(null);
const classDetailCache = reactive({});
const classDetailLoading = reactive({});

const selectedDistributionClassId = ref("");
const distributionSearchQuery = ref("");
const debouncedSearchQuery = ref("");
let searchDebounceTimer = null;
const distributionPagination = reactive({
  currentPage: 1,
  perPage: 100,
});

const isLoadingInitial = ref(true);
const isLoadingYears = ref(false);
const isLoadingReports = ref(false);
const isLoadingStudents = ref(false);
const isLoadingDistributionClasses = ref(false);

const downloadLoadingMap = reactive({});

const selectedAcademicYear = computed(
  () =>
    academicYears.value.find(
      (item) => String(item.id) === String(selectedAcademicYearId.value),
    ) || null,
);

const academicYearOptions = computed(() => dropdowns.academicYearOptions);

const toNumber = (value) => {
  const parsed = Number(value);
  return Number.isNaN(parsed) ? 0 : parsed;
};

const formatScore = (value) => {
  const score = Number(value);
  if (Number.isNaN(score)) {
    return "0.00";
  }
  return score.toFixed(2);
};

const unwrapArrayPayload = (response) => {
  const payload = response?.data;
  if (Array.isArray(payload)) {
    return payload;
  }
  if (Array.isArray(payload?.data)) {
    return payload.data;
  }
  return [];
};

const unwrapDistributionPayload = (response) => {
  const payload = response?.data || {};

  return {
    isAllReady: Boolean(payload.is_all_ready),
    students: Array.isArray(payload.data) ? payload.data : [],
  };
};

const getStudentClassName = (student) => student?.class_name || "-";

const filteredDistributionStudents = computed(() => {
  const query = debouncedSearchQuery.value.trim().toLowerCase();
  const classFilterId = String(selectedDistributionClassId.value || "");

  return students.value.filter((student) => {
    const matchClass =
      !classFilterId || String(student?.class_id) === classFilterId;

    if (!matchClass) {
      return false;
    }

    if (!query) {
      return true;
    }

    const name = String(student.name || "").toLowerCase();
    const nis = String(student.nis || "").toLowerCase();
    const nisn = String(student.nisn || "").toLowerCase();

    return name.includes(query) || nis.includes(query) || nisn.includes(query);
  });
});

const distributionTotalPages = computed(() => {
  const total = filteredDistributionStudents.value.length;
  return total > 0 ? Math.ceil(total / distributionPagination.perPage) : 1;
});

const distributionStudentsPage = computed(() => {
  const start =
    (distributionPagination.currentPage - 1) * distributionPagination.perPage;
  const end = start + distributionPagination.perPage;
  return filteredDistributionStudents.value.slice(start, end);
});

// Only show distribution table when search has 2+ chars or class is selected
const hasActiveFilter = computed(() => {
  return (
    debouncedSearchQuery.value.trim().length >= 2 ||
    !!selectedDistributionClassId.value
  );
});

const distributionStartItem = computed(() => {
  if (!filteredDistributionStudents.value.length) {
    return 0;
  }
  return (
    (distributionPagination.currentPage - 1) * distributionPagination.perPage +
    1
  );
});

const distributionEndItem = computed(() => {
  if (!filteredDistributionStudents.value.length) {
    return 0;
  }
  return Math.min(
    distributionPagination.currentPage * distributionPagination.perPage,
    filteredDistributionStudents.value.length,
  );
});

const changeDistributionPage = (page) => {
  if (page < 1 || page > distributionTotalPages.value) {
    return;
  }
  distributionPagination.currentPage = page;
};

const attendanceCards = computed(() => {
  const totals = attendanceRows.value.reduce(
    (acc, item) => {
      acc.totalRecords += toNumber(item.total_records);
      acc.totalPresent += toNumber(item.total_present);
      acc.totalPermission += toNumber(item.total_permission);
      acc.totalSick += toNumber(item.total_sick);
      acc.totalAlpa += toNumber(item.total_alpa);
      acc.totalLate += toNumber(item.total_late);
      return acc;
    },
    {
      totalRecords: 0,
      totalPresent: 0,
      totalPermission: 0,
      totalSick: 0,
      totalAlpa: 0,
      totalLate: 0,
    },
  );

  const attendanceRate =
    totals.totalRecords > 0
      ? `${((totals.totalPresent / totals.totalRecords) * 100).toFixed(1)}%`
      : "0.0%";

  return [
    { label: "Total Rekaman", value: totals.totalRecords },
    { label: "Total Hadir", value: totals.totalPresent },
    { label: "Total Alpha", value: totals.totalAlpa },
    { label: "Kehadiran", value: attendanceRate },
  ];
});

const academicCards = computed(() => {
  const totals = academicRows.value.reduce(
    (acc, item) => {
      const gradedCount = toNumber(item.total_graded_submissions);
      const average = Number(item.average_class_score) || 0;

      acc.totalAssignments += toNumber(item.total_assignments);
      acc.totalGraded += gradedCount;
      acc.weightedScore += average * gradedCount;
      if (gradedCount > 0) {
        acc.activeClasses += 1;
      }
      return acc;
    },
    {
      totalAssignments: 0,
      totalGraded: 0,
      weightedScore: 0,
      activeClasses: 0,
    },
  );

  const overallAverage =
    totals.totalGraded > 0
      ? (totals.weightedScore / totals.totalGraded).toFixed(2)
      : "0.00";

  return [
    { label: "Total Tugas", value: totals.totalAssignments },
    { label: "Submission Dinilai", value: totals.totalGraded },
    { label: "Rata-rata Umum", value: overallAverage },
    { label: "Kelas Aktif Penilaian", value: totals.activeClasses },
  ];
});

// Ambil daftar Tahun Ajaran — does NOT auto-select (user must choose explicitly)
const fetchAcademicYears = async () => {
  isLoadingYears.value = true;
  try {
    await dropdowns.ensureAcademicYears();
    academicYears.value = dropdowns.academicYearsRaw;
  } catch (error) {
    toastStore.error("Gagal memuat Tahun Ajaran.");
  } finally {
    isLoadingYears.value = false;
  }
};

// Ambil data ringkasan tab Kehadiran dan Akademik secara paralel.
const fetchReportSummaries = async () => {
  if (!selectedAcademicYearId.value) {
    attendanceRows.value = [];
    academicRows.value = [];
    return;
  }

  isLoadingReports.value = true;
  try {
    const [attendanceResponse, academicResponse] = await Promise.all([
      reportService.getAttendanceSummary(selectedAcademicYearId.value),
      reportService.getAcademicSummary(selectedAcademicYearId.value),
    ]);

    attendanceRows.value = unwrapArrayPayload(attendanceResponse);
    academicRows.value = unwrapArrayPayload(academicResponse);
  } catch (error) {
    toastStore.error(
      error.response?.data?.error || "Gagal memuat ringkasan laporan.",
    );
  } finally {
    isLoadingReports.value = false;
  }
};

// Ambil daftar distribusi kesiapan siswa untuk kebutuhan PDF rapor.
const fetchDistributionStudents = async () => {
  if (!selectedAcademicYearId.value) {
    students.value = [];
    isAllStudentsReady.value = false;
    return;
  }

  isLoadingStudents.value = true;
  try {
    const response = await reportService.getDistributionList(
      selectedAcademicYearId.value,
    );
    const payload = unwrapDistributionPayload(response);

    students.value = payload.students;
    isAllStudentsReady.value = payload.isAllReady;
  } catch (error) {
    students.value = [];
    isAllStudentsReady.value = false;
    toastStore.error(
      error.response?.data?.error || "Gagal memuat daftar distribusi rapor.",
    );
  } finally {
    isLoadingStudents.value = false;
  }
};

// Ambil opsi kelas untuk filter distribusi rapor — uses lightweight /classes/options endpoint
const fetchDistributionClasses = async () => {
  if (!selectedAcademicYearId.value) {
    distributionClassOptions.value = [];
    selectedDistributionClassId.value = "";
    return;
  }

  isLoadingDistributionClasses.value = true;
  try {
    // PERF FIX: use lightweight options endpoint instead of full classService.getAll
    const response = await api.get("/v1/admin/classes/options", {
      params: { academic_year_id: selectedAcademicYearId.value },
    });

    const classes = response.data.data || response.data || [];
    distributionClassOptions.value = classes.map((item) => ({
      value: String(item.id),
      label: item.name,
    }));

    const selectedStillExists = distributionClassOptions.value.some(
      (option) => option.value === selectedDistributionClassId.value,
    );
    if (!selectedStillExists) {
      selectedDistributionClassId.value = "";
    }
  } catch (error) {
    toastStore.error("Gagal memuat daftar kelas untuk filter rapor.");
  } finally {
    isLoadingDistributionClasses.value = false;
  }
};

// Fetch per-class readiness data
const fetchClassesReadiness = async () => {
  if (!selectedAcademicYearId.value) {
    classesReadiness.value = [];
    classesSummary.value = {
      total_classes: 0,
      ready_classes: 0,
      published_classes: 0,
      can_publish_all: false,
      is_report_published: false,
    };
    return;
  }

  isLoadingClassesReadiness.value = true;
  try {
    const response = await reportService.getClassesReadiness(
      selectedAcademicYearId.value,
    );
    const data = response.data?.data || {};
    classesReadiness.value = data.classes || [];
    classesSummary.value = data.summary || {
      total_classes: 0,
      ready_classes: 0,
      published_classes: 0,
      can_publish_all: false,
    };
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal memuat status kesiapan kelas.",
    );
  } finally {
    isLoadingClassesReadiness.value = false;
  }
};

// Toggle expand/collapse for class detail dropdown
const toggleClassDetail = async (classId) => {
  if (expandedClassId.value === classId) {
    expandedClassId.value = null;
    return;
  }

  expandedClassId.value = classId;

  // Fetch only if not cached
  if (!classDetailCache[classId]) {
    await fetchClassDetail(classId);
  }
};

// Fetch single class detail (with cache)
const fetchClassDetail = async (classId) => {
  if (!selectedAcademicYearId.value) return;

  classDetailLoading[classId] = true;
  try {
    const response = await reportService.getClassReadinessDetail(
      selectedAcademicYearId.value,
      classId,
    );
    classDetailCache[classId] = response.data?.data || null;
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal memuat detail kesiapan kelas.",
    );
    classDetailCache[classId] = null;
  } finally {
    classDetailLoading[classId] = false;
  }
};

// Force refetch single class detail + parent table (refresh button)
const refreshClassDetail = async (classId) => {
  delete classDetailCache[classId];
  await Promise.all([
    fetchClassesReadiness(),
    fetchClassDetail(classId),
  ]);
};

// Publish single class
const handlePublishClass = async (classId, className) => {
  isPublishingClass[classId] = true;
  try {
    await reportService.publishClass(selectedAcademicYearId.value, classId);
    toastStore.success(`Kelas ${className} berhasil dipublikasikan.`);
    await fetchClassesReadiness();
    await fetchDistributionStudents();
  } catch (error) {
    toastStore.error(
      error.response?.data?.error || "Gagal mempublikasi kelas.",
    );
  } finally {
    isPublishingClass[classId] = false;
  }
};

// Publish all ready classes
const publishAllConfirmModal = reactive({ isOpen: false, isLoading: false });

const handlePublishAllClasses = () => {
  if (!classesSummary.value.can_publish_all) {
    toastStore.error("Belum semua kelas siap dipublikasikan.");
    return;
  }
  publishAllConfirmModal.isOpen = true;
};

const executePublishAllClasses = async () => {
  publishAllConfirmModal.isLoading = true;
  try {
    const response = await reportService.publishAllClasses(
      selectedAcademicYearId.value,
    );
    const data = response.data?.data || {};
    const publishedCount = data.published?.length || 0;
    toastStore.success(`${publishedCount} kelas berhasil dipublikasikan.`);
    publishAllConfirmModal.isOpen = false;
    await fetchClassesReadiness();
    await fetchDistributionStudents();
  } catch (error) {
    toastStore.error(
      error.response?.data?.message || "Gagal mempublikasi kelas.",
    );
  } finally {
    publishAllConfirmModal.isLoading = false;
  }
};

const handleDownloadPdf = async (student) => {
  if (!selectedAcademicYearId.value) {
    toastStore.error("Pilih Tahun Ajaran terlebih dahulu.");
    return;
  }

  if (!student.is_ready) {
    toastStore.error(
      student.missing_info || "Data akademik siswa belum lengkap.",
    );
    return;
  }

  downloadLoadingMap[student.student_id] = true;
  try {
    await reportService.downloadStudentSemesterPdf(
      selectedAcademicYearId.value,
      student.student_id,
      student.name,
    );
    toastStore.success(`Rapor ${student.name} berhasil diunduh.`);
  } catch (error) {
    toastStore.error(
      error.message ||
        error.response?.data?.error ||
        "Gagal mengunduh PDF rapor.",
    );
  } finally {
    downloadLoadingMap[student.student_id] = false;
  }
};

// PERF FIX: skip watcher during initial load to prevent duplicate requests
watch(selectedAcademicYearId, async (newValue, oldValue) => {
  if (isInitialLoad.value || !newValue || newValue === oldValue) {
    return;
  }

  selectedDistributionClassId.value = "";
  distributionSearchQuery.value = "";
  debouncedSearchQuery.value = "";
  distributionPagination.currentPage = 1;
  expandedClassId.value = null;
  students.value = [];
  isAllStudentsReady.value = false;
  Object.keys(classDetailCache).forEach((k) => delete classDetailCache[k]);

  await Promise.all([
    fetchReportSummaries(),
    fetchDistributionClasses(),
    fetchClassesReadiness(),
  ]);
});

watch([distributionSearchQuery, selectedDistributionClassId], () => {
  distributionPagination.currentPage = 1;
});

// Debounce search input — 300ms after last keystroke
watch(distributionSearchQuery, (val) => {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    debouncedSearchQuery.value = val;
  }, 300);
});

// Fetch distribution data only when user activates a filter (search or class) — deferred load
watch(hasActiveFilter, (active) => {
  if (active && !students.value.length && !isLoadingStudents.value) {
    fetchDistributionStudents();
  }
});

watch(filteredDistributionStudents, () => {
  if (distributionPagination.currentPage > distributionTotalPages.value) {
    distributionPagination.currentPage = distributionTotalPages.value;
  }
});

// PERF FIX: single-pass initial load — watcher is suppressed via isInitialLoad flag
const isInitialLoad = ref(true);

onMounted(async () => {
  isLoadingInitial.value = true;
  isInitialLoad.value = true;
  try {
    await fetchAcademicYears();
    // Do NOT auto-fetch reports — wait for user to select a year
  } finally {
    isInitialLoad.value = false;
    isLoadingInitial.value = false;
  }
});

// Refresh report data when re-activated from keep-alive cache
// Only re-fetches if academic years or competencies were mutated externally (dirty flag only, no TTL)
onActivated(async () => {
  const yearsDirty = dropdowns.consumeDirtyFlag("academicYears");
  const competenciesDirty = dropdowns.consumeDirtyFlag("competencies");

  if (yearsDirty || competenciesDirty) {
    if (yearsDirty) {
      await dropdowns.refreshAcademicYears();
      academicYears.value = dropdowns.academicYearsRaw;
    }

    // Only re-fetch report data if a year is currently selected
    if (selectedAcademicYearId.value) {
      students.value = [];
      isAllStudentsReady.value = false;
      // Clear class detail cache when competency changes (competency status is embedded in detail)
      if (competenciesDirty) {
        Object.keys(classDetailCache).forEach((k) => delete classDetailCache[k]);
      }
      await Promise.all([
        fetchReportSummaries(),
        fetchDistributionClasses(),
        fetchClassesReadiness(),
      ]);
    }
  }
  // If not dirty, keep-alive cached data is preserved — no refetch
});

onUnmounted(() => {
  clearTimeout(searchDebounceTimer);
});
</script>
