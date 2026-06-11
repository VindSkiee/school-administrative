import { defineStore } from 'pinia';
import { studentScheduleService } from '../services/modules/student/scheduleService';

export const useStudentScheduleDetailStore = defineStore('studentScheduleDetail', {
  state: () => ({
    scheduleInfo: {},
    // Tempat penampungan panel child di masa depan (Phase berikutnya)
    attendance: null,
    materials: [],
    assignments: []
  }),
  actions: {
    async prefetchAllData(scheduleId, selectedDate) {
      // Untuk saat ini, kita fetch data header jadwal dulu
      const [resSchedule] = await Promise.all([
        studentScheduleService.getScheduleDetail(scheduleId),
        // Di sini nanti tempat menaruh API milik child panel (absensi, materi, tugas)
      ]);

      this.scheduleInfo = resSchedule.data.data;
    },
    clearData() {
      this.scheduleInfo = {};
      this.attendance = null;
      this.materials = [];
      this.assignments = [];
    }
  }
});