import { defineStore } from 'pinia';
import { attendanceService } from '../services/modules/teacher/attendanceService';

export const useAttendanceDetailStore = defineStore('attendanceDetail', {
  state: () => ({
    scheduleInfo: {}, // <-- TAMBAHKAN INI
    students: [],
    existingAttendances: [],
    pendingRequests: []
  }),
  actions: {
    async prefetchAllData(scheduleId, selectedDate) {
      // Tambahkan getScheduleDetail ke urutan pertama Promise.all
      const [resSchedule, resStudents, resAttendances, resRequests] = await Promise.all([
        attendanceService.getScheduleDetail(scheduleId), // <-- TAMBAHKAN INI
        attendanceService.getStudentsForAttendance(scheduleId),
        attendanceService.getExistingAttendances(scheduleId, selectedDate),
        attendanceService.getAttendanceRequests()
      ]);

      this.scheduleInfo = resSchedule.data; // <-- SIMPAN DATA HEADER
      this.students = resStudents.data;
      this.existingAttendances = resAttendances.data;
      
      this.pendingRequests = resRequests.data.data.filter(
        (req) => req.status === "pending" && 
                 String(req.schedule_id) === String(scheduleId) && 
                 req.date === selectedDate
      );
    },
    clearData() {
      this.scheduleInfo = {}; // <-- BERSIHKAN INI JUGA
      this.students = [];
      this.existingAttendances = [];
      this.pendingRequests = [];
    }
  }
});