import api from "../../api";

export const attendanceRecapService = {
  getAll(params) {
    return api.get("/v1/teacher/attendance-recap", { params });
  },
};
