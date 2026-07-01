import { defineStore } from 'pinia';
import { attendanceService } from '../services/modules/teacher/attendanceService';

export const useAttendanceDetailStore = defineStore('attendanceDetail', {
  state: () => ({
    scheduleInfo: {},
    students: [],
    existingAttendances: [],
    pendingRequests: [],
    // PERF FIX: track which schedule's data is loaded to prevent duplicate fetches
    loadedScheduleId: null,
    isLoading: false,
  }),
  actions: {
    // PERF FIX: returns true if data for this schedule is already loaded
    isDataLoaded(scheduleId) {
      return String(this.loadedScheduleId) === String(scheduleId);
    },

    async prefetchAllData(scheduleId, selectedDate) {
      // PERF FIX: skip if already loaded or currently loading — prevents duplicate requests
      if (this.isDataLoaded(scheduleId) || this.isLoading) return;

      this.isLoading = true;
      try {
        const [resSchedule, resStudents, resAttendances, resRequests] = await Promise.all([
          attendanceService.getScheduleDetail(scheduleId, selectedDate),
          attendanceService.getStudentsForAttendance(scheduleId),
          attendanceService.getExistingAttendances(scheduleId, selectedDate),
          attendanceService.getAttendanceRequests(),
        ]);

        this.scheduleInfo = resSchedule.data;
        this.students = resStudents.data;
        this.existingAttendances = resAttendances.data;

        this.pendingRequests = resRequests.data.data.filter(
          (req) =>
            req.status === 'pending' &&
            String(req.schedule_id) === String(scheduleId) &&
            req.date === selectedDate,
        );

        // PERF FIX: mark this schedule as loaded
        this.loadedScheduleId = String(scheduleId);
      } finally {
        this.isLoading = false;
      }
    },
    clearData() {
      this.scheduleInfo = {};
      this.students = [];
      this.existingAttendances = [];
      this.pendingRequests = [];
      // PERF FIX: reset loaded tracking on clear
      this.loadedScheduleId = null;
    },
  },
});
