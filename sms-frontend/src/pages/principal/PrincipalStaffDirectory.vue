<template>
  <div class="space-y-6">
    <div class="bg-gradient-to-r from-brand-red to-brand-orange rounded-3xl p-6 md:p-8 text-white shadow-md">
      <h1 class="text-2xl md:text-3xl font-bold font-serif">Direktori Guru & Admin</h1>
      <p class="text-orange-100 text-sm mt-1 max-w-xl font-medium">
        Daftar seluruh guru dan staf administrasi beserta peran dan tugasnya.
      </p>
    </div>

    <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-200 flex flex-col md:flex-row gap-4">
      <div class="relative flex-1">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <input
          type="text"
          v-model="searchQuery"
          placeholder="Cari nama guru atau NIP..."
          class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-brand-red/20 focus:border-brand-red outline-none transition-all"
        />
      </div>
      <div class="flex gap-2">
        <button
          v-for="tab in filterTabs"
          :key="tab.id"
          @click="activeFilter = tab.id"
          class="px-4 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all border"
          :class="activeFilter === tab.id
            ? 'bg-brand-red border-brand-red text-white shadow-sm'
            : 'bg-white border-gray-200 text-gray-500 hover:bg-gray-50'"
        >
          {{ tab.label }}
          <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px]" :class="activeFilter === tab.id ? 'bg-white/30' : 'bg-gray-100'">
            {{ getCount(tab.id) }}
          </span>
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="flex flex-col items-center justify-center py-16">
      <div class="animate-spin rounded-full h-10 w-10 border-4 border-gray-100 border-t-brand-red mb-3"></div>
      <p class="text-gray-500 font-medium text-sm">Memuat data guru...</p>
    </div>

    <div v-else-if="filteredStaff.length === 0" class="bg-white rounded-3xl p-12 text-center shadow-sm border border-gray-200">
      <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800">Tidak Ada Data</h3>
      <p class="text-gray-500 mt-1 text-sm">Tidak ada guru yang cocok dengan pencarian atau filter Anda.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div
        v-for="teacher in filteredStaff"
        :key="teacher.id"
        class="bg-white border border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all"
      >
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 font-bold text-lg"
               :class="teacher.role === 'admin' ? 'bg-purple-50 text-purple-600' : 'bg-brand-red/10 text-brand-red'">
            {{ teacher.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <h4 class="text-base font-bold text-gray-800 truncate">{{ teacher.name }}</h4>
            <p class="text-xs text-gray-500 font-mono">NIP: {{ teacher.nip }}</p>
            <div class="flex flex-wrap gap-1.5 mt-2">
              <span
                v-if="teacher.role === 'admin'"
                class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded-lg"
              >
                Admin
              </span>
              <span
                v-else-if="teacher.is_homeroom"
                class="px-2 py-0.5 bg-blue-50 text-blue-700 text-[10px] font-bold rounded-lg"
              >
                Wali Kelas: {{ teacher.homeroom_class_name }}
              </span>
              <span
                v-else
                class="px-2 py-0.5 bg-gray-100 text-gray-500 text-[10px] font-bold rounded-lg"
              >
                Guru Mata Pelajaran
              </span>
            </div>
          </div>
        </div>

        <div v-if="teacher.subjects && teacher.subjects.length > 0" class="mt-4 pt-3 border-t border-gray-100">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">Mata Pelajaran</p>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="subject in teacher.subjects"
              :key="subject.id"
              class="px-2 py-0.5 bg-orange-50 text-brand-orange text-[10px] font-bold rounded-md"
            >
              {{ subject.name }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { principalService } from '../../services/modules/principal/principalService';
import { useToastStore } from '../../stores/toast';

const toastStore = useToastStore();

const isLoading = ref(true);
const staff = ref([]);
const searchQuery = ref('');
const activeFilter = ref('all');

const filterTabs = [
  { id: 'all', label: 'Semua' },
  { id: 'homeroom', label: 'Wali Kelas' },
  { id: 'subject', label: 'Guru Mapel' },
  { id: 'admin', label: 'Admin' },
];

const filteredStaff = computed(() => {
  return staff.value.filter(teacher => {
    const q = searchQuery.value.toLowerCase();
    const matchesSearch = !q ||
      teacher.name.toLowerCase().includes(q) ||
      (teacher.nip && teacher.nip.toLowerCase().includes(q));

    const matchesFilter =
      activeFilter.value === 'all' ||
      (activeFilter.value === 'homeroom' && teacher.is_homeroom) ||
      (activeFilter.value === 'subject' && teacher.role === 'teacher' && !teacher.is_homeroom) ||
      (activeFilter.value === 'admin' && teacher.role === 'admin');

    return matchesSearch && matchesFilter;
  });
});

const getCount = (tabId) => {
  if (tabId === 'all') return staff.value.length;
  if (tabId === 'homeroom') return staff.value.filter(t => t.is_homeroom).length;
  if (tabId === 'admin') return staff.value.filter(t => t.role === 'admin').length;
  return staff.value.filter(t => t.role === 'teacher' && !t.is_homeroom).length;
};

const fetchStaff = async () => {
  isLoading.value = true;
  try {
    const res = await principalService.getStaff();
    staff.value = res.data?.data || res.data || [];
  } catch (error) {
    toastStore.error('Gagal memuat data guru.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchStaff);
</script>
