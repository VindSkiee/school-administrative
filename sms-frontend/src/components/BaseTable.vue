<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex items-center justify-between text-sm">
      <div class="font-semibold text-gray-700">
        Total Data: {{ pagination ? pagination.total : data.length }}
      </div>
      <div v-if="pagination" class="text-gray-500">
        Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}
      </div>
      <div v-else class="text-gray-500">{{ data.length }} baris</div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full min-w-full text-left border-collapse">
        <thead class="bg-brand-red text-white">
          <tr class="text-sm tracking-wide">
            <th class="p-0 font-semibold w-16 align-middle border-b border-red-300 bg-brand-red">
              <div class="relative px-4 py-4 flex items-center justify-center h-full w-full">
                No
              </div>
            </th>

            <th
              v-for="col in columns"
              :key="col.key"
              class="p-0 font-semibold align-middle border-b border-red-300 bg-brand-red"
            >
              <div
                class="relative px-4 py-4 flex items-center h-full w-full"
                :class="{
                  'justify-start': !col.align || col.align === 'left',
                  'justify-center': col.align === 'center',
                  'justify-end': col.align === 'right',
                }"
              >
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-px h-5 bg-white/40"></div>
                {{ col.label }}
              </div>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-200 text-sm">
          <tr v-if="isLoading" class="bg-white">
            <td :colspan="columns.length + 1" class="p-8 text-center text-gray-500">
              <div class="flex items-center justify-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-brand-red" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memuat data...
              </div>
            </td>
          </tr>

          <tr v-else-if="data.length === 0" class="bg-white">
            <td :colspan="columns.length + 1" class="p-8 text-center text-gray-500">
              {{ emptyMessage }}
            </td>
          </tr>

          <tr
            v-else
            v-for="(item, index) in data"
            :key="item.id || index"
            class="hover:bg-gray-100 transition-colors bg-white"
          >
            <td class="p-1.5 px-2 text-center font-medium text-gray-600">
              {{ getRowNumber(index) }}
            </td>

            <td
              v-for="col in columns"
              :key="col.key"
              class="p-1.5 px-1.5 whitespace-nowrap"
              :class="`text-${col.align || 'left'}`"
            >
              <slot :name="`cell(${col.key})`" :item="item">
                {{ item[col.key] }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div 
      v-if="pagination && pagination.last_page > 1" 
      class="px-4 py-3 border-t border-gray-200 bg-white flex items-center justify-between sm:px-6"
    >
      <div class="flex items-center justify-between w-full sm:hidden">
        <button
          @click="changePage(pagination.current_page - 1)"
          :disabled="pagination.current_page === 1"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Sebelumnya
        </button>
        <span class="text-sm text-gray-700 font-medium">
          Hal {{ pagination.current_page }}
        </span>
        <button
          @click="changePage(pagination.current_page + 1)"
          :disabled="pagination.current_page === pagination.last_page"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Selanjutnya
        </button>
      </div>

      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            Menampilkan data <span class="font-semibold">{{ getStartData() }}</span> sampai <span class="font-semibold">{{ getEndData() }}</span> dari <span class="font-semibold">{{ pagination.total }}</span>
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span class="sr-only">Previous</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
            
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-bold text-brand-red">
              {{ pagination.current_page }}
            </span>

            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              <span class="sr-only">Next</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  columns: { type: Array, required: true }, 
  data: { type: Array, required: true },
  isLoading: { type: Boolean, default: false },
  emptyMessage: { type: String, default: "Tidak ada data ditemukan." },
  
  // PROPS BARU UNTUK PAGINASI
  pagination: {
    type: Object,
    default: null, // Berisi: { current_page, last_page, per_page, total }
  }
});

// EMIT UNTUK MEMBERITAHU PARENT JIKA HALAMAN BERUBAH
const emit = defineEmits(['page-change']);

const changePage = (page) => {
  if (props.pagination && page >= 1 && page <= props.pagination.last_page) {
    emit('page-change', page);
  }
};

// Fungsi agar Nomor Urut tidak reset ke 1 di halaman 2
const getRowNumber = (index) => {
  if (!props.pagination) return index + 1;
  const { current_page, per_page } = props.pagination;
  return (current_page - 1) * per_page + index + 1;
};

// Fungsi teks informasi data
const getStartData = () => {
  if (!props.pagination || props.pagination.total === 0) return 0;
  return (props.pagination.current_page - 1) * props.pagination.per_page + 1;
};

const getEndData = () => {
  if (!props.pagination) return 0;
  const end = props.pagination.current_page * props.pagination.per_page;
  return end > props.pagination.total ? props.pagination.total : end;
};
</script>