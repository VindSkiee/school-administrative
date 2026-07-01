import { defineStore } from 'pinia';
import { studentScheduleService } from '../services/modules/student/scheduleService';

export const useStudentScheduleDetailStore = defineStore('studentScheduleDetail', {
  state: () => ({
    scheduleInfo: {},
    attendance: null,
    materials: [],
    assignments: []
  }),
  actions: {
    async prefetchAllData(scheduleId, selectedDate) {
      const [resSchedule] = await Promise.all([
        studentScheduleService.getScheduleDetail(scheduleId, selectedDate),
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
