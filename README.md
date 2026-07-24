# EduPlatform — School Administrative System

Sistem Manajemen Sekolah (SMS) terpadu untuk administrasi akademik dan pembelajaran digital.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | Vue 3 (Composition API) + Vite + Tailwind CSS v4 |
| Backend | Laravel 13 + MySQL |
| Auth | JWT (tymon/jwt-Auth) |
| State | Pinia 3 |

## Features

- **Admin** — CRUD users, classes, academic years, subjects, schedules; report management; activity logs
- **Teacher** — Dashboard, daily attendance, materials, assignments, grading, homeroom class
- **Student** — Dashboard, schedule, materials, assignments, grades, semester report (PDF)
- **Principal** — Executive dashboard, attendance trends, academic performance

## Roles

| Role | Description |
|------|-------------|
| `admin` | System administrator |
| `teacher` | Guru / homeroom teacher |
| `student` | Siswa |
| `principal` | Kepala sekolah |

## Quick Start

### Prerequisites

- PHP 8.3
- Composer
- Node.js + npm
- MySQL (via Laragon or other)

### 1. Clone

```bash
git clone https://github.com/VindSkiee/school-administrative.git
cd school-administrative
```

### 2. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

Backend runs at `http://localhost:8000`.

### 3. Frontend

```bash
cd sms-frontend
npm install
npm run dev
```

Frontend runs at `http://localhost:5173`.

### 4. Login

| Email | Password | Role |
|-------|----------|------|
| `euis.herlina@sekolah.com` | `password123` | admin |
| `principal@sekolah.com` | `password123` | principal |

Guru & siswa: lihat hasil seeder (password: `password123`).

## Seeders

| Seeder | Purpose |
|--------|---------|
| `ReportCardUnpublishedSeeder` | Demo — 1 class incomplete (cannot publish) |
| `ReportCardReadySeeder` | Full — all classes ready to publish |

```bash
# Demo (default)
php artisan migrate:fresh --seed

# Full ready
php artisan migrate:fresh --seed=ReportCardReadySeeder
```

## Project Structure

```
school-administrative/
├── backend/          # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/API/{Role}/
│   │   ├── Services/
│   │   └── Models/
│   ├── routes/api/v1/
│   └── database/seeders/
├── sms-frontend/     # Vue SPA
│   └── src/
│       ├── pages/{role}/
│       ├── services/modules/{role}/
│       ├── components/
│       └── stores/
└── README.md
```

## API Endpoints

All endpoints are prefixed with `/api/v1/{role}/`.

| Role | Prefix | Example |
|------|--------|---------|
| admin | `/api/v1/admin/` | `GET /users`, `POST /classes` |
| teacher | `/api/v1/teacher/` | `GET /schedules/today`, `POST /attendances/bulk` |
| student | `/api/v1/student/` | `GET /assignments`, `POST /assignments/{id}/submit` |
| principal | `/api/v1/principal/` | `GET /dashboard/overview` |

## Development

```bash
# Backend
cd backend
php artisan serve          # API server
php artisan test --compact # Run tests
vendor/bin/pint --dirty    # Format PHP

# Frontend
cd sms-frontend
npm run dev                # Vite dev server
npm run build              # Production build
```

## License

Private — For educational use only.
