<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 font-serif tracking-wide">
          Jadwal Pelajaran
        </h1>
        <p class="text-gray-500 text-sm mt-1">
          Kelola alokasi waktu mengajar guru dan mata pelajaran untuk setiap
          kelas.
        </p>
      </div>

      <div
        class="flex flex-col lg:flex-row justify-between items-end gap-4 w-full"
      >
        <div class="flex flex-col sm:flex-row flex-wrap gap-3 w-full lg:w-auto">
          <div class="w-full sm:w-56">
            <label
              class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5"
            >
              Pilih Tahun Ajaran
            </label>
            <BaseSelect
              v-model="selectedAcademicYearId"
              :options="academicYearOptions"
              placeholder="Pilih Tahun Ajaran"
            />
          </div>

          <div class="w-full sm:w-56">
            <label
              class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5"
            >
              Pilih Kelas
            </label>
            <BaseSelect
              v-model="selectedClassFilter"
              :options="classOptions"
              placeholder="Semua Kelas"
              :searchable="true"
            />
          </div>

          <div class="w-full sm:w-56">
            <label
              class="block text-xs font-semibold tracking-wide text-gray-600 mb-1.5"
            >
              Pilih Hari
            </label>
            <BaseSelect
              v-model="selectedDayFilter"
              :options="dayFilterOptions"
              placeholder="Semua Hari"
            />
          </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
          <button
            @click="openHolidayModal"
            class="h-10 w-full sm:w-auto bg-blue-500 border border-blue-400 hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition-colors flex items-center justify-center whitespace-nowrap"
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
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              ></path>
            </svg>
            Atur Hari Libur
          </button>

          <button
            @click="openModal()"
            class="bg-brand-red h-10 w-full sm:w-auto hover:bg-brand-orange text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition-colors flex items-center justify-center whitespace-nowrap"
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
            Buat Jadwal
          </button>
        </div>
      </div>
    </div>

    <BaseTable
      :columns="tableColumns"
      :data="schedules"
      :isLoading="isLoading"
      :pagination="paginationMeta"
      @page-change="fetchSchedules"
      emptyMessage="Belum ada jadwal yang terdaftar untuk filter ini."
    >
      <template #cell(day_time)="{ item }">
        <div class="flex flex-col gap-1">
          <span class="text-sm font-medium text-gray-900">
            {{ translateDay(item.day_of_week) }}
          </span>
          <div
            class="flex items-center gap-1.5 text-xs text-gray-500 tabular-nums"
          >
            <svg
              class="w-3.5 h-3.5 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              ></path>
            </svg>
            <span
              >{{ formatTime(item.start_time) }} -
              {{ formatTime(item.end_time) }}</span
            >
          </div>
        </div>
      </template>

      <template #cell(class_subject)="{ item }">
        <div class="flex flex-col gap-0.5">
          <span class="text-sm font-medium text-gray-900">
            {{ item.subject?.name }}
          </span>
          <span class="text-xs font-normal text-gray-500">
            Kelas {{ item.school_class?.name }}
          </span>
        </div>
      </template>

      <template #cell(teacher)="{ item }">
        <div class="flex items-center">
          <div
            class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs mr-2"
          >
            {{ item.teacher?.user?.name.charAt(0) }}
          </div>
          <router-link
            v-if="item.teacher?.user?.id"
            :to="{
              name: 'Detail Pengguna',
              params: { id: item.teacher.user.id },
            }"
            class="text-brand-red hover:text-brand-orange font-semibold hover:underline cursor-pointer transition-colors"
          >
            {{ item.teacher?.user?.name }}
          </router-link>
          <span v-else class="text-sm font-medium text-gray-700">{{
            item.teacher?.user?.name || "-"
          }}</span>
        </div>
      </template>

      <template #cell(meetings)="{ item }">
        <div class="flex items-center justify-center">
          <span
            class="inline-flex items-center px-2 py-0.5 bg-gray-50 border border-gray-200 rounded-md text-sm font-medium tabular-nums shadow-sm"
          >
            <span class="text-gray-900">{{ item.meeting_completed || 0 }}</span>
            <span class="text-gray-400 font-normal mx-0.5">/</span>
            <span class="text-gray-500">{{ item.meeting_total || 0 }}</span>
          </span>
        </div>
      </template>

      <template #cell(actions)="{ item }">
        <div class="flex justify-center items-center gap-1.5">
          <button
            @click="openMeetingModal(item)"
            class="p-2 bg-white border border-gray-200 hover:bg-teal-50 text-teal-600 rounded-lg transition-colors shadow-sm"
            title="Lihat Pertemuan"
          >
            <Icon icon="mdi:eye-outline" class="w-4 h-4" />
          </button>
          <button
            @click="openSwapModal(item)"
            class="p-2 bg-white border border-gray-200 hover:bg-amber-50 text-amber-600 rounded-lg transition-colors shadow-sm"
            title="Tukar Jadwal"
          >
            <Icon icon="mdi:swap-horizontal" class="w-4 h-4" />
          </button>
          <button
            @click="openModal(item)"
            :class="[
              'p-2 rounded-lg shadow-sm transition-colors',
              item.has_data
                ? 'bg-amber-50 border border-amber-200 hover:bg-amber-100 text-amber-700'
                : 'bg-white border border-gray-200 hover:bg-blue-50 text-blue-600',
            ]"
            :title="
              item.has_data
                ? 'Edit Guru: jadwal sudah memiliki data'
                : 'Edit Jadwal'
            "
          >
            <Icon icon="mdi:pencil-outline" class="w-4 h-4" />
          </button>
          <button
            @click="promptDelete(item)"
            :disabled="item.has_data"
            :class="[
              'p-2 rounded-lg shadow-md transition-colors',
              item.has_data
                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                : 'bg-brand-red hover:bg-brand-orange text-white',
            ]"
            :title="
              item.has_data
                ? 'Tidak bisa hapus: jadwal sudah memiliki data'
                : 'Hapus Jadwal'
            "
          >
            <Icon icon="mdi:trash-can-outline" class="w-4 h-4" />
          </button>
        </div>
      </template>
    </BaseTable>

    <BaseModal
      :isOpen="isModalOpen"
      :title="isEditing ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran'"
      @close="isModalOpen = false"
    >
      <form id="scheduleForm" @submit.prevent="saveSchedule" class="space-y-4">
        <div v-if="!isEditing">
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Tahun Ajaran <span class="text-red-500">*</span></label
          >
          <BaseSelect
            v-model="form.academic_year_id"
            :options="academicYearOptions"
            placeholder="Pilih Tahun Ajaran"
            :searchable="true"
          />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5"
              >Kelas <span class="text-red-500">*</span></label
            >
            <BaseSelect
              v-model="form.class_id"
              :options="classOptions"
              placeholder="Pilih Kelas"
              :searchable="true"
              :disabled="isEditing && meetingHasData"
            />
          </div>
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1.5"
              >Mata Pelajaran <span class="text-red-500">*</span></label
            >
            <BaseSelect
              v-model="form.subject_id"
              :options="subjectOptions"
              placeholder="Pilih Mapel"
              :searchable="true"
              :disabled="isEditing && meetingHasData"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Guru Pengajar <span class="text-red-500">*</span></label
          >
          <BaseSelect
            v-model="form.teacher_id"
            :options="teacherOptions"
            placeholder="Pilih Guru"
            :searchable="true"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5"
            >Hari <span class="text-red-500">*</span></label
          >
          <div class="flex flex-wrap gap-2">
            <label
              v-for="day in dayOptions"
              :key="day.value"
              :class="[
                'flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium cursor-pointer transition-colors select-none',
                form.days.includes(day.value)
                  ? 'bg-brand-red text-white border-brand-red'
                  : 'bg-white text-gray-700 border-gray-200 hover:border-brand-red/50',
                isEditing && meetingHasData
                  ? 'opacity-60 cursor-not-allowed'
                  : '',
              ]"
            >
              <input
                type="checkbox"
                :value="day.value"
                v-model="form.days"
                :disabled="isEditing && meetingHasData"
                class="sr-only"
              />
              {{ day.label }}
            </label>
          </div>
        </div>

        <div v-if="form.day_slots.length > 0" class="space-y-3">
          <label class="block text-sm font-semibold text-gray-700"
            >Jam Mengajar per Hari <span class="text-red-500">*</span></label
          >
          <div
            v-for="slot in form.day_slots"
            :key="slot.day"
            class="flex flex-col gap-2 p-3 bg-gray-50 rounded-lg border border-gray-200"
          >
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
              <span
                class="text-sm font-bold text-gray-800 uppercase w-20 shrink-0"
              >
                {{ translateDay(slot.day) }}
              </span>
              <div class="flex items-center gap-2 flex-1">
                <BaseTimePicker
                  v-model="slot.start_time"
                  placeholder="Mulai"
                  :disabled="isEditing && meetingHasData"
                  max="16:00"
                />
                <span class="text-gray-400">—</span>
                <BaseTimePicker
                  v-model="slot.end_time"
                  placeholder="Selesai"
                  :disabled="(isEditing && meetingHasData) || !slot.start_time"
                  :min="slot.start_time || ''"
                  max="16:00"
                />
              </div>
            </div>
            <p
              v-if="getSlotClash(slot)"
              class="text-xs text-amber-600 flex items-center gap-1 sm:pl-23"
            >
              <svg
                class="w-3.5 h-3.5 shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
              {{ getSlotClash(slot) }}
            </p>
          </div>
        </div>

        <div
          v-if="isEditing && meetingHasData"
          class="bg-amber-50 border border-amber-200 rounded-lg p-3"
        >
          <div class="flex items-start">
            <svg
              class="w-4 h-4 text-amber-600 mt-0.5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
              ></path>
            </svg>
            <p class="text-xs text-amber-700">
              Jadwal ini sudah memiliki data absensi. Hanya <strong>Guru Pengajar</strong> yang dapat diganti. Hari, jam, kelas, dan mapel terkunci.
            </p>
          </div>
        </div>

        <div
          v-if="formError"
          class="bg-red-50 border border-red-200 rounded-lg p-3"
        >
          <div class="flex items-start">
            <svg
              class="w-4 h-4 text-red-500 mt-0.5 mr-2 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              ></path>
            </svg>
            <p class="text-xs text-red-700">{{ formError }}</p>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            type="submit"
            form="scheduleForm"
            :disabled="isSaving"
            :class="[
              'px-5 py-2.5 font-semibold rounded-lg shadow-sm',
              isSaving
                ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                : 'bg-brand-red hover:bg-brand-orange text-white',
            ]"
          >
            {{ isSaving ? "Menyimpan..." : "Simpan Jadwal" }}
          </button>
        </div>
      </template>
    </BaseModal>

    <ConfirmModal
      :isOpen="confirmModal.isOpen"
      :isLoading="confirmModal.isLoading"
      title="Hapus Jadwal?"
      :message="confirmModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDelete"
      @cancel="confirmModal.isOpen = false"
    />

    <!-- Swap Schedule Modal -->
    <BaseModal
      :isOpen="isSwapModalOpen"
      title="Tukar Jadwal"
      maxWidth="lg"
      @close="isSwapModalOpen = false"
    >
      <div v-if="swapScheduleA" class="space-y-5">
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
          <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-2">Jadwal A (Dipilih)</p>
          <div class="flex items-center gap-3">
            <span class="px-2 py-0.5 bg-white border border-blue-200 rounded-md text-xs font-bold text-blue-700">
              {{ swapScheduleA.school_class?.name }}
            </span>
            <span class="text-sm font-semibold text-gray-800">{{ swapScheduleA.subject?.name }}</span>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            {{ translateDay(swapScheduleA.day_of_week) }} &middot; {{ formatTime(swapScheduleA.start_time) }} - {{ formatTime(swapScheduleA.end_time) }}
          </p>
        </div>

        <div class="flex justify-center">
          <div class="w-10 h-10 rounded-full bg-brand-red/10 flex items-center justify-center">
            <Icon icon="mdi:swap-horizontal" class="w-5 h-5 text-brand-red" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-1.5">
            Pilih Jadwal Tujuan <span class="text-red-500">*</span>
          </label>
          <p class="text-xs text-gray-500 mb-2">
            Waktu akan ditukar. Mata pelajaran, guru, dan kelas tetap.
          </p>
          <BaseSelect
            v-model="swapScheduleBId"
            :options="swapTargetOptions"
            placeholder="Pilih jadwal untuk ditukar..."
            searchable
          />
        </div>

        <div v-if="swapPreview" class="bg-gray-50 border border-gray-200 rounded-xl p-4 space-y-3">
          <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Hasil Pertukaran</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="bg-white rounded-lg p-3 border border-gray-200">
              <p class="text-[11px] font-bold text-gray-500 uppercase mb-1">{{ swapScheduleA.school_class?.name }} — {{ swapScheduleA.subject?.name }}</p>
              <p class="text-sm font-semibold text-gray-800">
                {{ translateDay(swapPreview.newA.day_of_week) }} {{ formatTime(swapPreview.newA.start_time) }} - {{ formatTime(swapPreview.newA.end_time) }}
              </p>
            </div>
            <div class="bg-white rounded-lg p-3 border border-gray-200">
              <p class="text-[11px] font-bold text-gray-500 uppercase mb-1">{{ swapPreview.scheduleB.subject?.name }}</p>
              <p class="text-sm font-semibold text-gray-800">
                {{ translateDay(swapPreview.newB.day_of_week) }} {{ formatTime(swapPreview.newB.start_time) }} - {{ formatTime(swapPreview.newB.end_time) }}
              </p>
            </div>
          </div>
        </div>

        <div v-if="swapError" class="bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-700 flex items-center gap-2">
          <Icon icon="mdi:alert-circle-outline" class="w-4 h-4 shrink-0" />
          {{ swapError }}
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-3">
          <button
            @click="isSwapModalOpen = false"
            class="px-5 py-2.5 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-lg transition-colors text-sm"
          >
            Batal
          </button>
          <button
            @click="executeSwap"
            :disabled="!swapScheduleBId || isSavingSwap"
            class="px-5 py-2.5 font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm flex items-center"
            :class="!swapScheduleBId || isSavingSwap ? 'bg-gray-300 text-gray-500' : 'bg-brand-red hover:bg-brand-orange text-white'"
          >
            <svg v-if="isSavingSwap" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSavingSwap ? 'Menukar...' : 'Ya, Tukar Jadwal' }}
          </button>
        </div>
      </template>
    </BaseModal>

    <!-- Holiday Management Modal -->
    <BaseModal
      :isOpen="isHolidayModalOpen"
      title="Atur Hari Libur"
      maxWidth="400px"
      @close="isHolidayModalOpen = false"
    >
      <div class="space-y-5">
        <!-- Add Holiday Form -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">
            Tambah Hari Libur Baru
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1"
                >Tanggal <span class="text-red-500">*</span></label
              >
              <input
                v-model="holidayForm.date"
                type="date"
                :min="tomorrowDate"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1"
                >Keterangan <span class="text-red-500">*</span></label
              >
              <input
                v-model="holidayForm.description"
                type="text"
                maxlength="255"
                placeholder="Masukkan keterangan hari libur"
                class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-brand-red focus:border-brand-red outline-none"
              />
            </div>
          </div>
          <div class="flex justify-end mt-3">
            <button
              @click="saveHoliday"
              :disabled="
                isSavingHoliday || !holidayForm.date || !holidayForm.description
              "
              :class="[
                'px-4 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm',
                isSavingHoliday || !holidayForm.date || !holidayForm.description
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-brand-red hover:bg-brand-orange text-white',
              ]"
            >
              {{ isSavingHoliday ? "Menyimpan..." : "Tambah" }}
            </button>
          </div>
        </div>

        <!-- Holiday List -->
        <div>
          <h4 class="text-sm font-semibold text-gray-700 mb-3">
            Daftar Hari Libur
          </h4>
          <div v-if="isLoadingHolidays" class="flex justify-center py-6">
            <svg
              class="animate-spin h-6 w-6 text-brand-red"
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
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
          </div>
          <div
            v-else-if="holidays.length === 0"
            class="text-center py-6 text-gray-400 text-sm"
          >
            Belum ada hari libur yang ditetapkan.
          </div>
          <div
            v-else
            class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar"
          >
            <div
              v-for="holiday in holidays"
              :key="holiday.id"
              class="flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center"
                >
                  <svg
                    class="w-5 h-5 text-blue-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    ></path>
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-800">
                    {{ formatDate(holiday.date) }}
                  </p>
                  <p class="text-xs text-gray-500">{{ holiday.description }}</p>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button
                  @click="editHoliday(holiday)"
                  class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                  title="Edit"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    ></path>
                  </svg>
                </button>
                <button
                  @click="promptDeleteHoliday(holiday)"
                  class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                  title="Hapus"
                >
                  <svg
                    class="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    ></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Confirm Delete Holiday -->
    <ConfirmModal
      :isOpen="confirmHolidayModal.isOpen"
      :isLoading="confirmHolidayModal.isLoading"
      title="Hapus Hari Libur?"
      :message="confirmHolidayModal.message"
      confirmText="Ya, Hapus!"
      @confirm="executeDeleteHoliday"
      @cancel="confirmHolidayModal.isOpen = false"
    />

    <!-- Meeting Sessions Detail Modal -->
    <BaseModal
      :isOpen="isMeetingModalOpen"
      :title="
        selectedScheduleForMeeting
          ? `Detail Pertemuan — ${selectedScheduleForMeeting.subject?.name} (${selectedScheduleForMeeting.school_class?.name})`
          : 'Detail Pertemuan'
      "
      maxWidth="3xl"
      @close="isMeetingModalOpen = false"
    >
      <div class="space-y-4">
        <!-- Stats Cards -->
        <div
          class="grid grid-cols-2 sm:grid-cols-4 bg-white border border-gray-200 rounded-xl shadow-sm divide-y sm:divide-y-0 sm:divide-x divide-gray-100"
        >
          <div class="p-4 sm:p-5">
            <div class="text-sm font-medium text-gray-500 mb-1">Total</div>
            <div class="text-2xl font-semibold text-gray-900">
              {{ meetingSessionStats.total }}
            </div>
          </div>
          <div class="p-4 sm:p-5">
            <div class="text-sm font-medium text-gray-500 mb-1">Terjadwal</div>
            <div class="text-2xl font-semibold text-blue-600">
              {{ meetingSessionStats.scheduled }}
            </div>
          </div>
          <div class="p-4 sm:p-5">
            <div class="text-sm font-medium text-gray-500 mb-1">Selesai</div>
            <div class="text-2xl font-semibold text-green-600">
              {{ meetingSessionStats.completed }}
            </div>
          </div>
          <div class="p-4 sm:p-5">
            <div class="text-sm font-medium text-gray-500 mb-1">Libur</div>
            <div class="text-2xl font-semibold text-yellow-600">
              {{ meetingSessionStats.holiday }}
            </div>
          </div>
        </div>

        <!-- Sessions Table -->
        <div v-if="meetingSessionsLoading" class="flex justify-center py-8">
          <svg
            class="animate-spin h-8 w-8 text-brand-red"
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
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
        </div>
        <div
          v-else-if="meetingSessions.length === 0"
          class="text-center py-8 text-gray-400 text-sm"
        >
          Belum ada data pertemuan.
        </div>
        <BaseTable
          v-else
          :columns="meetingSessionColumns"
          :data="meetingSessions"
          :isLoading="false"
          emptyMessage="Tidak ada data pertemuan."
        >
          <template #cell(meeting_number)="{ item }">
            <span class="font-mono font-bold text-gray-800">{{
              item.meeting_number
            }}</span>
          </template>

          <template #cell(date)="{ item }">
            <span class="text-sm text-gray-700">{{
              formatDate(item.date)
            }}</span>
          </template>

          <template #cell(status)="{ item }">
            <span
              :class="[
                'px-2.5 py-1 rounded-full text-xs font-semibold inline-block',
                getMeetingStatusClass(item),
              ]"
            >
              {{ getMeetingStatusLabel(item) }}
            </span>
          </template>
        </BaseTable>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onActivated, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useToastStore } from "../../stores/toast";
import { useGlobalDropdownsStore } from "../../stores/globalDropdowns";
import { scheduleService } from "../../services/modules/admin/scheduleService";
import { holidayService } from "../../services/modules/admin/holidayService";

import BaseTable from "../../components/BaseTable.vue";
import BaseSelect from "../../components/BaseSelect.vue";
import BaseModal from "../../components/BaseModal.vue";
import BaseTimePicker from "../../components/BaseTimePicker.vue";
import ConfirmModal from "../../components/ConfirmModal.vue";

const toastStore = useToastStore();
const dropdowns = useGlobalDropdownsStore();

const tableColumns = [
  { key: "day_time", label: "Hari & Waktu" },
  { key: "meetings", label: "Pertemuan", align: "center" },
  { key: "class_subject", label: "Pelajaran" },
  { key: "teacher", label: "Guru Pengajar" },
  { key: "actions", label: "Aksi", align: "center" },
];

const schedules = ref([]);
const paginationMeta = reactive({
  total: 0,
  current_page: 1,
  last_page: 1,
  per_page: 100,
});
const isLoading = ref(true);
const isSaving = ref(false);
const formError = ref("");

const selectedClassFilter = ref(""); // Filter untuk List Table
const selectedAcademicYearId = ref(""); // Tahun Ajaran terpilih (default: aktif)
const selectedDayFilter = ref(""); // Filter Hari

// Data Master for Dropdown — sourced from global cache store
const academicYearOptions = computed(() => dropdowns.academicYearOptions);
const classOptions = computed(() => dropdowns.classDropdownOptions);
const subjectOptions = computed(() => dropdowns.subjectDropdownOptions);
const teacherOptions = computed(() => dropdowns.teacherDropdownOptions);

const dayOptions = [
  { value: "monday", label: "Senin" },
  { value: "tuesday", label: "Selasa" },
  { value: "wednesday", label: "Rabu" },
  { value: "thursday", label: "Kamis" },
  { value: "friday", label: "Jumat" },
];

const dayFilterOptions = [
  { value: "monday", label: "Senin" },
  { value: "tuesday", label: "Selasa" },
  { value: "wednesday", label: "Rabu" },
  { value: "thursday", label: "Kamis" },
  { value: "friday", label: "Jumat" },
];

const isModalOpen = ref(false);
const isEditing = ref(false);
const selectedId = ref(null);
const meetingStats = ref(null);
const meetingHasData = ref(false);

const form = reactive({
  academic_year_id: "",
  class_id: "",
  subject_id: "",
  teacher_id: "",
  day_of_week: "",
  days: [],
  day_slots: [],
});

const confirmModal = reactive({
  isOpen: false,
  isLoading: false,
  message: "",
  targetId: null,
});

// --- SWAP STATE ---
const isSwapModalOpen = ref(false);
const isSavingSwap = ref(false);
const swapScheduleA = ref(null);
const swapScheduleBId = ref("");
const swapError = ref("");

// --- HOLIDAY STATE ---
const isHolidayModalOpen = ref(false);
const holidays = ref([]);
const isLoadingHolidays = ref(false);
const isSavingHoliday = ref(false);
const editingHolidayId = ref(null);
const holidayForm = reactive({ date: "", description: "" });
const confirmHolidayModal = reactive({
  isOpen: false,
  isLoading: false,
  message: "",
  targetId: null,
});

// --- MEETING DETAIL STATE ---
const isMeetingModalOpen = ref(false);
const selectedScheduleForMeeting = ref(null);
const meetingSessions = ref([]);
const meetingSessionsLoading = ref(false);
const meetingSessionStats = reactive({
  total: 0,
  scheduled: 0,
  completed: 0,
  holiday: 0,
});

const meetingSessionColumns = [
  { key: "meeting_number", label: "Pertemuan", align: "center" },
  { key: "date", label: "Tanggal" },
  { key: "status", label: "Status", align: "center" },
];

const tomorrowDate = computed(() => {
  const d = new Date();
  d.setDate(d.getDate() + 1);
  return d.toISOString().split("T")[0];
});

const formatDate = (dateStr) => {
  if (!dateStr) return "";
  const d = new Date(dateStr);
  const days = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
  const months = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
  ];
  return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
};

// --- UTILITIES ---
const translateDay = (day) => {
  const days = {
    monday: "Senin",
    tuesday: "Selasa",
    wednesday: "Rabu",
    thursday: "Kamis",
    friday: "Jumat",
    saturday: "Sabtu",
  };
  return days[day] || day;
};

const formatTime = (time) => {
  if (!time) return "";
  return time.substring(0, 5); // Mengubah '08:00:00' menjadi '08:00'
};

// --- API CALLS ---
const fetchMasterData = async () => {
  try {
    // Use global dropdown store — fetches only if cache is empty
    await Promise.all([
      dropdowns.ensureAcademicYears(),
      dropdowns.ensureClasses(),
      dropdowns.ensureSubjects(),
      dropdowns.ensureTeacherOptions(),
    ]);

    // Do NOT auto-select year — default to all years shown (no selection)
  } catch (error) {
    toastStore.error(
      "Gagal memuat data pendukung (Kelas/Mapel/Guru/Tahun Ajaran).",
    );
  }
};

const fetchSchedules = async (page = 1) => {
  isLoading.value = true;
  try {
    const params = { page: page, per_page: paginationMeta.per_page };
    if (selectedClassFilter.value) {
      params.class_id = selectedClassFilter.value;
    }
    if (selectedAcademicYearId.value) {
      params.academic_year_id = selectedAcademicYearId.value;
    }
    if (selectedDayFilter.value) {
      params.day_of_week = selectedDayFilter.value;
    }

    const response = await scheduleService.getAll(params);
    schedules.value = response.data.data;
    paginationMeta.total = response.data.total;
    paginationMeta.current_page = response.data.current_page;
    paginationMeta.last_page = response.data.last_page;
    paginationMeta.per_page = response.data.per_page;
  } catch (error) {
    toastStore.error("Gagal memuat jadwal pelajaran.");
  } finally {
    isLoading.value = false;
  }
};

const openModal = async (schedule = null) => {
  isEditing.value = !!schedule;
  meetingStats.value = null;
  meetingHasData.value = false;
  formError.value = "";

  if (schedule) {
    selectedId.value = schedule.id;
    form.academic_year_id = schedule.academic_year_id || "";
    form.class_id = schedule.class_id;
    form.subject_id = schedule.subject_id;
    form.teacher_id = schedule.teacher_id;
    form.day_of_week = schedule.day_of_week;
    form.days = [schedule.day_of_week];
    form.day_slots = [
      {
        day: schedule.day_of_week,
        start_time: formatTime(schedule.start_time),
        end_time: formatTime(schedule.end_time),
      },
    ];

    meetingHasData.value = schedule.has_data || false;

    try {
      const response = await scheduleService.getMeetingSessions(schedule.id);
      meetingStats.value = response.data.stats;
    } catch (error) {
      // Silently fail - meeting stats is optional info
    }
  } else {
    selectedId.value = null;
    form.academic_year_id =
      selectedAcademicYearId.value || dropdowns.activeAcademicYear?.id || "";
    form.class_id = selectedClassFilter.value || "";
    form.subject_id = "";
    form.teacher_id = "";
    form.day_of_week = "";
    form.days = [];
    form.day_slots = [];
  }
  isModalOpen.value = true;
};

const saveSchedule = async () => {
  // Validasi wajib diisi
  if (!isEditing.value && !form.academic_year_id) {
    toastStore.error("Tahun ajaran wajib dipilih.");
    return;
  }
  if (
    !form.class_id ||
    !form.subject_id ||
    !form.teacher_id ||
    form.days.length === 0
  ) {
    toastStore.error("Lengkapi semua field wajib terlebih dahulu.");
    return;
  }
  if (form.day_slots.length === 0) {
    toastStore.error("Pilih minimal satu hari untuk jadwal.");
    return;
  }

  // Validate each slot has times
  for (const slot of form.day_slots) {
    if (!slot.start_time || !slot.end_time) {
      toastStore.error(
        `Jam mulai dan jam selesai wajib diisi untuk ${translateDay(slot.day)}.`,
      );
      return;
    }
    if (slot.start_time >= slot.end_time) {
      toastStore.error(
        `Jam selesai harus lebih lambat dari jam mulai untuk ${translateDay(slot.day)}!`,
      );
      return;
    }
    if (slot.end_time > "16:00") {
      toastStore.error(
        `Jam selesai maksimal pukul 16.00 untuk ${translateDay(slot.day)}.`,
      );
      return;
    }
    // Client-side clash check
    const clash = getSlotClash(slot);
    if (clash) {
      toastStore.error(`Bentrok di ${translateDay(slot.day)}: ${clash}`);
      return;
    }
  }

  isSaving.value = true;
  try {
    if (isEditing.value) {
      if (meetingHasData.value) {
        // Teacher-only update mode
        await scheduleService.update(selectedId.value, {
          teacher_id: form.teacher_id,
        });
        toastStore.success("Guru pengajar berhasil diganti.");
      } else {
        const slot = form.day_slots[0];
        await scheduleService.update(selectedId.value, {
          academic_year_id: form.academic_year_id,
          class_id: form.class_id,
          subject_id: form.subject_id,
          teacher_id: form.teacher_id,
          day_of_week: slot.day,
          start_time: slot.start_time,
          end_time: slot.end_time,
        });
        toastStore.success("Jadwal diperbarui.");
      }
    } else {
      let created = 0;
      for (const slot of form.day_slots) {
        await scheduleService.create({
          academic_year_id: form.academic_year_id,
          class_id: form.class_id,
          subject_id: form.subject_id,
          teacher_id: form.teacher_id,
          days: [slot.day],
          start_time: slot.start_time,
          end_time: slot.end_time,
        });
        created++;
      }
      toastStore.success(
        created > 1
          ? `${created} jadwal berhasil dibuat.`
          : "Jadwal baru ditambahkan.",
      );
    }
    isModalOpen.value = false;
    fetchSchedules(paginationMeta.current_page);
  } catch (error) {
    if (isEditing.value && meetingHasData.value) {
      formError.value = error.response?.data?.error || "Gagal mengganti guru pengajar.";
    } else {
      toastStore.error(error.response?.data?.error || "Gagal menyimpan jadwal.");
    }
  } finally {
    isSaving.value = false;
  }
};

const promptDelete = (schedule) => {
  confirmModal.targetId = schedule.id;
  confirmModal.message = `Hapus jadwal ${schedule.subject?.name} untuk kelas ${schedule.school_class?.name}?`;
  confirmModal.isOpen = true;
};

const executeDelete = async () => {
  confirmModal.isLoading = true;
  try {
    await scheduleService.delete(confirmModal.targetId);
    toastStore.success("Jadwal berhasil dihapus.");
    fetchSchedules(paginationMeta.current_page);
  } catch (error) {
    toastStore.error(error.response?.data?.error || "Gagal menghapus jadwal.");
  } finally {
    confirmModal.isLoading = false;
    confirmModal.isOpen = false;
  }
};

// --- SWAP FUNCTIONS ---
const swapTargetOptions = computed(() => {
  if (!swapScheduleA.value) return [];
  return schedules.value
    .filter((s) => s.id !== swapScheduleA.value.id && s.class_id === swapScheduleA.value.class_id)
    .map((s) => ({
      value: s.id,
      label: `${s.school_class?.name || ''} — ${s.subject?.name || ''} (${translateDay(s.day_of_week)} ${formatTime(s.start_time)}-${formatTime(s.end_time)})`,
    }));
});

const swapPreview = computed(() => {
  if (!swapScheduleBId.value || !swapScheduleA.value) return null;
  const scheduleB = schedules.value.find((s) => s.id === swapScheduleBId.value);
  if (!scheduleB) return null;
  return {
    scheduleB,
    newA: {
      day_of_week: scheduleB.day_of_week,
      start_time: scheduleB.start_time,
      end_time: scheduleB.end_time,
    },
    newB: {
      day_of_week: swapScheduleA.value.day_of_week,
      start_time: swapScheduleA.value.start_time,
      end_time: swapScheduleA.value.end_time,
    },
  };
});

const openSwapModal = (schedule) => {
  swapScheduleA.value = schedule;
  swapScheduleBId.value = "";
  swapError.value = "";
  isSwapModalOpen.value = true;
};

const executeSwap = async () => {
  if (!swapScheduleBId.value || !swapScheduleA.value) return;
  isSavingSwap.value = true;
  swapError.value = "";
  try {
    await scheduleService.swap(swapScheduleA.value.id, swapScheduleBId.value);
    toastStore.success("Jadwal berhasil ditukar.");
    isSwapModalOpen.value = false;
    fetchSchedules(paginationMeta.current_page);
  } catch (error) {
    swapError.value = error.response?.data?.error || "Gagal menukar jadwal.";
  } finally {
    isSavingSwap.value = false;
  }
};

// --- HOLIDAY FUNCTIONS ---
const openHolidayModal = () => {
  resetHolidayForm();
  isHolidayModalOpen.value = true;
  fetchHolidays();
};

const fetchHolidays = async () => {
  isLoadingHolidays.value = true;
  try {
    const response = await holidayService.getAll();
    holidays.value = response.data;
  } catch (error) {
    toastStore.error("Gagal memuat data hari libur.");
  } finally {
    isLoadingHolidays.value = false;
  }
};

const saveHoliday = async () => {
  if (!holidayForm.date || !holidayForm.description) {
    toastStore.error("Tanggal dan keterangan wajib diisi.");
    return;
  }

  // Validate: no weekends (Saturday = 6, Sunday = 0)
  const d = new Date(holidayForm.date + "T00:00:00");
  const day = d.getDay();
  if (day === 0 || day === 6) {
    toastStore.error(
      "Tidak dapat menetapkan hari libur pada hari Sabtu atau Minggu (sudah libur).",
    );
    return;
  }

  // Validate: not today or past
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  if (d <= today) {
    toastStore.error(
      "Tidak dapat menetapkan hari libur untuk tanggal hari ini atau sebelumnya.",
    );
    return;
  }

  isSavingHoliday.value = true;
  try {
    if (editingHolidayId.value) {
      // Edit = delete old + create new (backend has no update endpoint)
      await holidayService.delete(editingHolidayId.value);
      await holidayService.create({
        date: holidayForm.date,
        description: holidayForm.description,
      });
      toastStore.success("Hari libur berhasil diperbarui.");
    } else {
      await holidayService.create({
        date: holidayForm.date,
        description: holidayForm.description,
      });
      toastStore.success("Hari libur berhasil ditetapkan.");
    }
    resetHolidayForm();
    fetchHolidays();
  } catch (error) {
    toastStore.error(
      error.response?.data?.error || "Gagal menyimpan hari libur.",
    );
  } finally {
    isSavingHoliday.value = false;
  }
};

const editHoliday = (holiday) => {
  editingHolidayId.value = holiday.id;
  holidayForm.date = holiday.date.split("T")[0];
  holidayForm.description = holiday.description;
};

const resetHolidayForm = () => {
  editingHolidayId.value = null;
  holidayForm.date = "";
  holidayForm.description = "";
};

const promptDeleteHoliday = (holiday) => {
  confirmHolidayModal.targetId = holiday.id;
  confirmHolidayModal.message = `Hapus hari libur "${holiday.description}" pada ${formatDate(holiday.date)}? Sesi pertemuan yang terdampak akan dipulihkan.`;
  confirmHolidayModal.isOpen = true;
};

const executeDeleteHoliday = async () => {
  confirmHolidayModal.isLoading = true;
  try {
    await holidayService.delete(confirmHolidayModal.targetId);
    toastStore.success("Hari libur berhasil dihapus.");
    fetchHolidays();
  } catch (error) {
    toastStore.error(
      error.response?.data?.error || "Gagal menghapus hari libur.",
    );
  } finally {
    confirmHolidayModal.isLoading = false;
    confirmHolidayModal.isOpen = false;
  }
};

// --- MEETING DETAIL FUNCTIONS ---
const openMeetingModal = async (schedule) => {
  selectedScheduleForMeeting.value = schedule;
  meetingSessions.value = [];
  meetingSessionStats.total = 0;
  meetingSessionStats.scheduled = 0;
  meetingSessionStats.completed = 0;
  meetingSessionStats.holiday = 0;
  isMeetingModalOpen.value = true;
  meetingSessionsLoading.value = true;

  try {
    const response = await scheduleService.getMeetingSessions(schedule.id);
    meetingSessions.value = response.data.sessions;
    Object.assign(meetingSessionStats, response.data.stats);
  } catch (error) {
    toastStore.error("Gagal memuat data pertemuan.");
  } finally {
    meetingSessionsLoading.value = false;
  }
};

const getMeetingStatusClass = (item) => {
  if (item.attendances_count > 0) return "bg-green-100 text-green-700";
  switch (item.status) {
    case "holiday":
      return "bg-yellow-100 text-yellow-700";
    default:
      return "bg-blue-100 text-blue-700";
  }
};

const getMeetingStatusLabel = (item) => {
  if (item.attendances_count > 0) return "Selesai";
  switch (item.status) {
    case "holiday":
      return "Libur";
    default:
      return "Terjadwal";
  }
};

onMounted(() => {
  fetchMasterData();
  fetchSchedules();
});

// Refresh schedule data when component is re-activated from keep-alive cache
// Only re-fetches if data was actually mutated elsewhere (dirty flag check)
onActivated(async () => {
  const yearsDirty = dropdowns.consumeDirtyFlag("academicYears");
  const classesDirty = dropdowns.consumeDirtyFlag("classes");
  const subjectsDirty = dropdowns.consumeDirtyFlag("subjects");
  const teachersDirty = dropdowns.consumeDirtyFlag("teachers");

  // If nothing was mutated externally, skip all re-fetching — keep-alive cache is valid
  if (!yearsDirty && !classesDirty && !subjectsDirty && !teachersDirty) return;

  // Only refresh dropdowns that were actually mutated
  const refreshPromises = [];
  if (yearsDirty) refreshPromises.push(dropdowns.refreshAcademicYears());
  if (classesDirty) refreshPromises.push(dropdowns.refreshClasses());
  if (subjectsDirty) refreshPromises.push(dropdowns.refreshSubjects());
  if (teachersDirty) refreshPromises.push(dropdowns.refreshTeacherOptions());

  await Promise.all(refreshPromises);

  // Re-fetch schedules only when dropdown data changed
  fetchSchedules(paginationMeta.current_page);
});

watch(selectedClassFilter, () => {
  fetchSchedules(1);
});

watch(selectedAcademicYearId, () => {
  fetchSchedules(1);
});

watch(selectedDayFilter, () => {
  fetchSchedules(1);
});

// Sync day checkboxes ↔ day_slots
watch(
  () => form.days,
  (newDays) => {
    if (!isModalOpen.value) return;

    // Add slots for newly checked days
    newDays.forEach((day) => {
      if (!form.day_slots.find((s) => s.day === day)) {
        form.day_slots.push({ day, start_time: "", end_time: "" });
      }
    });
    // Remove slots for unchecked days
    form.day_slots = form.day_slots.filter((s) => newDays.includes(s.day));
  },
  { deep: true },
);

// Dynamic clash detection per slot
const getSlotClash = (slot) => {
  if (
    !slot.start_time ||
    !slot.end_time ||
    !form.class_id ||
    !form.teacher_id ||
    !form.academic_year_id
  ) {
    return null;
  }

  for (const s of schedules.value) {
    // Skip current schedule in edit mode
    if (isEditing.value && s.id === selectedId.value) continue;

    // Must be same academic year
    if (String(s.academic_year_id) !== String(form.academic_year_id)) continue;

    // Must be same day
    if (s.day_of_week !== slot.day) continue;

    // Time overlap check
    const sStart = s.start_time ? s.start_time.substring(0, 5) : "";
    const sEnd = s.end_time ? s.end_time.substring(0, 5) : "";
    if (!sStart || !sEnd) continue;
    if (!(sStart < slot.end_time && sEnd > slot.start_time)) continue;

    // Same class or same teacher
    const classClash = String(s.class_id) === String(form.class_id);
    const teacherClash = String(s.teacher_id) === String(form.teacher_id);

    if (classClash) {
      return `Kelas ini sudah punya jadwal ${s.subject?.name || ""} (${sStart}–${sEnd})`;
    }
    if (teacherClash) {
      return `${s.teacher?.user?.name || "Guru"} sudah mengajar ${s.school_class?.name || ""} ${s.subject?.name || ""} (${sStart}–${sEnd})`;
    }
  }
  return null;
};
</script>
