import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";

// Komponen Halaman
import Login from "../pages/Login.vue";
import MainLayout from "../layouts/MainLayout.vue";

const routes = [
  {
    path: "/login",
    name: "Login",
    component: Login,
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
        component: () => import("../pages/admin/UserProfile.vue"),
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
        component: () => import("../pages/principal/Dashboard.vue"),
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

  // Proteksi Role-Based (RBAC) di Frontend
  if (to.meta.role && to.meta.role !== userRole) {
    return next("/unauthorized");
  }

  next();
});

export default router;
