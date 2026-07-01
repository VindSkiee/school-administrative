import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

// MainLayout is statically imported — it wraps all authenticated routes
import MainLayout from "../layouts/MainLayout.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: () => import("../pages/Login.vue"),
    meta: { requiresGuest: true },
  },
  {
    path: "/force-change-password",
    name: "ForceChangePassword",
    component: () => import("../pages/auth/ForceChangePassword.vue"),
    meta: { requiresAuth: true },
  },
  {
    path: "/dashboard",
    redirect: () => {
      const authStore = useAuthStore();
      if (!authStore.isAuthenticated) return "/login";
      if (authStore.mustChangePassword) return "/force-change-password";
      return `/${authStore.userRole}/dashboard`;
    },
  },
  {
    path: "/",
    redirect: () => {
      // Auto-redirect user ke dashboard masing-masing jika akses root '/'
      const authStore = useAuthStore();
      if (!authStore.isAuthenticated) return "/login";
      if (authStore.mustChangePassword) return "/force-change-password";
      return `/${authStore.userRole}/dashboard`;
    },
  },
  {
    path: "/account",
    component: MainLayout,
    meta: { requiresAuth: true }, // <-- Tanpa meta.role
    children: [
      {
        path: "profile",
        name: "MyProfile",
        component: () => import("../pages/UserProfile.vue"),
      },
    ],
  },
  // --- ADMIN ROUTES ---
  {
    path: "/admin",
    component: MainLayout,
    meta: { requiresAuth: true, role: "admin" },
    children: [
      {
        path: "dashboard",
        name: "AdminDashboard",
        component: () => import("../pages/admin/Dashboard.vue"),
      },
      {
        path: "users",
        name: "Manajemen Pengguna",
        component: () => import("../pages/admin/UserManagement.vue"),
      },
      {
        path: "users/:id",
        name: "Detail Pengguna",
        component: () => import("../pages/UserProfile.vue"),
      },
      {
        path: "classes",
        name: "Data Kelas",
        component: () => import("../pages/admin/ClassManagement.vue"),
      },
      {
        path: "classes/:id",
        name: "Detail Kelas",
        component: () => import("../pages/admin/ClassDetail.vue"),
      },
      {
        path: "academic-years",
        name: "Tahun Ajaran",
        component: () => import("../pages/admin/AcademicYearManagement.vue"),
      },
      {
        path: "subjects",
        name: "Manajemen Mapel",
        component: () => import("../pages/admin/SubjectManagement.vue"),
      },
      {
        path: "subjects/:id",
        name: "AdminSubjectDetail",
        component: () => import("../pages/admin/SubjectDetail.vue"),
      },
      {
        path: "schedules",
        name: "Manajemen Jadwal",
        component: () => import("../pages/admin/ScheduleManagement.vue"),
      },
      {
        path: "activity-logs",
        name: "Log Aktivitas",
        component: () => import("../pages/admin/ActivityLogManagement.vue"),
      },
      {
        path: "reports",
        name: "Laporan Akademik",
        component: () => import("../pages/admin/ReportManagement.vue"),
      },
    ],
  },
  // --- TEACHER ROUTES ---
  {
    path: "/teacher",
    component: MainLayout,
    meta: { requiresAuth: true, role: "teacher" },
    children: [
      {
        path: "dashboard",
        name: "TeacherDashboard",
        component: () => import("../pages/teacher/Dashboard.vue"),
      },
      // Di dalam children rute /teacher
      {
        path: "classes/:id",
        name: "TeacherClassDetail",
        // Mengarah ke folder teacher, bukan admin
        component: () => import("../pages/teacher/ClassDetail.vue"),
      },
      {
        path: "students/:id",
        name: "TeacherStudentProfile",
        component: () => import("../pages/teacher/StudentProfile.vue"), // Arahkan ke file baru
      },
      {
        path: "schedules/today",
        name: "TeacherAttendance",
        component: () => import("../pages/teacher/AttendanceSchedule.vue"),
      },
      {
        path: "classes/:schedule_id/detail",
        name: "TeacherScheduleDetail",
        component: () => import("../pages/teacher/ScheduleDetail.vue"),
      },
      {
        path: "/teacher/assignments",
        name: "TeacherAssignments",
        component: () => import("../pages/teacher/AssignmentList.vue"),
      },
      {
        path: "/teacher/assignments/:id",
        name: "TeacherAssignmentDetail",
        component: () => import("../pages/teacher/TeacherAssignmentDetail.vue"),
      },
      {
        path: "gradebook",
        name: "TeacherGradebook",
        component: () => import("../pages/teacher/TeacherGradebook.vue"),
      },
      {
        path: "attendance-recap",
        name: "TeacherAttendanceRecap",
        component: () => import("../pages/teacher/AttendanceRecap.vue"),
      },
    ],
  },
  // --- STUDENT ROUTES ---
  {
    path: "/student",
    component: MainLayout,
    meta: { requiresAuth: true, role: "student" },
    children: [
      {
        path: "dashboard",
        name: "StudentDashboard",
        component: () => import("../pages/student/Dashboard.vue"),
      },
      {
        path: "schedules",
        name: "StudentSchedules",
        component: () => import("../pages/student/StudentSchedule.vue"),
      },
      {
        path: "schedules/:id/detail",
        name: "StudentScheduleDetail",
        component: () => import("../pages/student/StudentScheduleDetail.vue"),
      },
      {
        path: "materials",
        name: "StudentMaterials",
        component: () =>
          import("../pages/student/panel/StudentMaterialPanel.vue"),
      },
      {
        path: "assignments",
        name: "StudentAssignments",
        component: () =>
          import("../pages/student/panel/StudentAssignmentPanel.vue"),
      },
      {
        path: "assignmentsList",
        name: "StudentAssignmentsList",
        component: () => import("../pages/student/StudentAssignments.vue"),
      },
      {
        path: "class-detail",
        name: "StudentClassDetail",
        component: () => import("../pages/student/StudentClassDetail.vue"),
        meta: { title: "Ruang Kelas Aktif" },
      },
      {
        path: "report",
        name: "StudentReport",
        component: () => import("../pages/student/StudentReport.vue"),
        meta: { title: "Nilai & Rapor Semester" },
      },
    ],
  },
  // --- PRINCIPAL ROUTES ---
  {
    path: "/principal",
    component: MainLayout,
    meta: { requiresAuth: true, role: "principal" },
    children: [
      {
        path: "dashboard",
        name: "PrincipalDashboard",
        component: () => import("../pages/principal/PrincipalDashboard.vue"),
      },
      {
        path: "staff",
        name: "PrincipalStaffDirectory",
        component: () => import("../pages/principal/PrincipalStaffDirectory.vue"),
      },
      {
        path: "settings/grading",
        name: "PrincipalGradingSettings",
        component: () => import("../pages/principal/PrincipalGradingSettings.vue"),
      },
      {
        path: "academic-trends",
        name: "PrincipalAcademicTrends",
        component: () => import("../pages/principal/AcademicTrendIndex.vue"),
      },
    ],
  },
  {
    path: "/unauthorized",
    name: "Unauthorized",
    component: () => import("../pages/Unauthorized.vue"),
  },
  {
    path: "/server-down",
    name: "ServerDown",
    component: () => import("../pages/ServerDown.vue"),
  },
  {
    path: "/:pathMatch(.*)*",
    name: "NotFound",
    component: () => import("../pages/NotFound.vue"),
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// NAVIGATION GUARD (The Bouncer)
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  const isAuthenticated = authStore.isAuthenticated;
  const userRole = authStore.userRole;
  const mustChangePassword = authStore.mustChangePassword;

  // Jika halaman butuh auth tapi user belum login
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next("/login");
  }

  // Jika user sudah login tapi mencoba akses halaman login
  if (to.meta.requiresGuest && isAuthenticated) {
    return next(`/${userRole}/dashboard`);
  }

  if (
    isAuthenticated &&
    mustChangePassword &&
    to.path !== "/force-change-password"
  ) {
    return next("/force-change-password");
  }

  if (
    isAuthenticated &&
    !mustChangePassword &&
    to.path === "/force-change-password"
  ) {
    return next(`/${userRole}/dashboard`);
  }

  if (to.meta.role && authStore.user) {
    const userRole = authStore.user.role.toLowerCase();
    const requiredRole = to.meta.role.toLowerCase();

    if (userRole !== requiredRole) {
      return next("/unauthorized"); // Di sinilah Anda ditendang tadi!
    }
  }

  next();
});

export default router;
