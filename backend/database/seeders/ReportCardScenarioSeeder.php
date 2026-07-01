<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\GradingSetting;
use App\Models\Holiday;
use App\Models\MeetingSession;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ReportCardScenarioSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password123';

    protected bool $reportPublished = false;

    private const SUBJECTS = [
        ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam dan Budi Pekerti'],
        ['code' => 'PPKn', 'name' => 'Pendidikan Pancasila'],
        ['code' => 'B.IND', 'name' => 'Bahasa Indonesia'],
        ['code' => 'MTK', 'name' => 'Matematika (Umum)'],
        ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam (IPA)'],
        ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial (IPS)'],
        ['code' => 'B.ING', 'name' => 'Bahasa Inggris'],
        ['code' => 'PJOK', 'name' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan'],
        ['code' => 'SBdP', 'name' => 'Seni, Budaya dan Prakarya'],
        ['code' => 'MULOK', 'name' => 'Muatan Lokal Bahasa Daerah'],
        ['code' => 'INF', 'name' => 'Informatika'],
    ];

    private const CLASS_NAMES = ['7A', '8A', '9A'];

    private const TEACHERS = [
        ['name' => 'Ahmad Hidayat, S.Ag.', 'email' => 'ahmad.hidayat@guru.sekolah.com', 'nip' => '198501012010011001'],
        ['name' => 'Siti Nurhaliza, S.Pd.', 'email' => 'siti.nurhaliza@guru.sekolah.com', 'nip' => '198602022011012002'],
        ['name' => 'Dewi Kartika, S.Pd.', 'email' => 'dewi.kartika@guru.sekolah.com', 'nip' => '198703032012012003'],
        ['name' => 'Budi Santoso, M.Pd.', 'email' => 'budi.santoso@guru.sekolah.com', 'nip' => '198804042013011004'],
        ['name' => 'Rina Wulandari, S.Si.', 'email' => 'rina.wulandari@guru.sekolah.com', 'nip' => '198905052014012005'],
        ['name' => 'Agus Setiawan, S.Pd.', 'email' => 'agus.setiawan@guru.sekolah.com', 'nip' => '199006062015011006'],
        ['name' => 'Linda Permata, S.Pd.', 'email' => 'linda.permata@guru.sekolah.com', 'nip' => '199107072016012007'],
        ['name' => 'Eko Prasetyo, S.Pd.', 'email' => 'eko.prasetyo@guru.sekolah.com', 'nip' => '198808082012011008'],
        ['name' => 'Maya Anggraini, S.Sn.', 'email' => 'maya.anggraini@guru.sekolah.com', 'nip' => '199209092017012009'],
        ['name' => 'Hendra Gunawan, S.Pd.', 'email' => 'hendra.gunawan@guru.sekolah.com', 'nip' => '198510102011011010'],
        ['name' => 'Fitri Handayani, S.Kom.', 'email' => 'fitri.handayani@guru.sekolah.com', 'nip' => '199311112018012011'],
    ];

    private const STUDENTS_PER_CLASS = 20;

    private const DAYS = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

    private const TIME_SLOTS = [
        ['day' => 'monday',    'start' => '07:00'],
        ['day' => 'monday',    'start' => '08:30'],
        ['day' => 'monday',    'start' => '10:00'],
        ['day' => 'tuesday',   'start' => '07:00'],
        ['day' => 'tuesday',   'start' => '08:30'],
        ['day' => 'tuesday',   'start' => '10:00'],
        ['day' => 'wednesday', 'start' => '07:00'],
        ['day' => 'wednesday', 'start' => '08:30'],
        ['day' => 'wednesday', 'start' => '10:00'],
        ['day' => 'thursday',  'start' => '07:00'],
        ['day' => 'thursday',  'start' => '08:30'],
    ];

    public function run(): void
    {
        $this->command->warn('⚠️  Memulai ReportCardScenarioSeeder — SEMUA pertemuan selesai, semester pendek.');

        $this->clearData();

        $academicYear = $this->createAcademicYear();
        $this->createGradingSetting($academicYear);

        $subjects = $this->createSubjects();
        $classes = $this->createClasses($academicYear);

        $teachers = $this->createTeachers();
        $this->assignHomerooms($teachers, $classes);

        $studentsByClass = $this->createStudents($classes, $academicYear);

        $schedulesByClass = $this->createSchedulesAndAssignments($classes, $subjects, $teachers, $academicYear);

        $this->createMeetingSessions($schedulesByClass, $academicYear);

        $this->createHolidays($academicYear);

        $this->createSubmissionsAndGrades($studentsByClass, $schedulesByClass, $teachers);

        $this->createAttendanceRecords($studentsByClass, $schedulesByClass);

        $this->printSummary();
    }

    private function clearData(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('grades')->truncate();
        DB::table('submissions')->truncate();
        DB::table('attendances')->truncate();
        DB::table('attendance_requests')->truncate();
        DB::table('assignments')->truncate();
        DB::table('meeting_sessions')->truncate();
        DB::table('holidays')->truncate();
        DB::table('schedules')->truncate();
        DB::table('class_student')->truncate();
        DB::table('students')->truncate();
        DB::table('teachers')->truncate();
        DB::table('classes')->truncate();
        DB::table('subjects')->truncate();
        DB::table('grading_settings')->truncate();
        DB::table('academic_years')->truncate();

        User::whereIn('role', ['teacher', 'student'])->forceDelete();

        Schema::enableForeignKeyConstraints();

        $this->command->info('   Data lama dibersihkan.');
    }

    private function createAcademicYear(): AcademicYear
    {
        $today = Carbon::today();
        $endOfWeek = $today->copy()->endOfWeek(Carbon::FRIDAY);

        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'odd',
            'is_active' => true,
            'is_report_published' => $this->reportPublished,
            'start_date' => '2026-06-01',
            'end_date' => $endOfWeek->toDateString(),
        ]);

        $this->command->info('   ✅ Tahun Ajaran: 2026/2027 Ganjil');
        $this->command->info("      Periode: 01 Jun 2026 — {$endOfWeek->format('d M Y')} (semester pendek)");
        $this->command->info('      Status: is_report_published = '.($this->reportPublished ? 'true' : 'false'));

        return $year;
    }

    private function createGradingSetting(AcademicYear $year): void
    {
        GradingSetting::create([
            'academic_year_id' => $year->id,
            'task_weight' => 40,
            'uts_weight' => 25,
            'uas_weight' => 25,
            'attendance_weight' => 10,
        ]);

        $this->command->info('   ✅ GradingSetting: Task 40% + UTS 25% + UAS 25% + Kehadiran 10%');
    }

    private function createSubjects(): Collection
    {
        $subjects = collect();

        foreach (self::SUBJECTS as $subjectData) {
            $subjects->push(Subject::create($subjectData));
        }

        $this->command->info('   ✅ '.count(self::SUBJECTS).' Mata Pelajaran dibuat.');

        return $subjects;
    }

    private function createClasses(AcademicYear $year): Collection
    {
        $classes = collect();

        foreach (self::CLASS_NAMES as $name) {
            $classes->push(SchoolClass::create([
                'name' => $name,
                'academic_year_id' => $year->id,
            ]));
        }

        $this->command->info('   ✅ 3 Kelas dibuat: 7A, 8A, 9A.');

        return $classes;
    }

    private function createTeachers(): Collection
    {
        $teachers = collect();
        $password = Hash::make(self::DEFAULT_PASSWORD);

        foreach (self::TEACHERS as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $password,
                'role' => 'teacher',
                'is_active' => true,
                'must_change_password' => false,
            ]);

            $teacher = Teacher::create([
                'user_id' => $user->id,
                'nip' => $data['nip'],
            ]);

            $teachers->push($teacher);
        }

        $this->command->info('   ✅ '.count(self::TEACHERS).' Guru dibuat.');

        return $teachers;
    }

    private function assignHomerooms(Collection $teachers, Collection $classes): void
    {
        foreach ($classes as $index => $class) {
            $class->update([
                'homeroom_teacher_id' => $teachers[$index]->user_id,
            ]);
        }

        $this->command->info('   ✅ 3 Wali Kelas ditetapkan (Guru 1→7A, 2→8A, 3→9A).');
    }

    private function createStudents(Collection $classes, AcademicYear $year): array
    {
        $password = Hash::make(self::DEFAULT_PASSWORD);
        $studentsByClass = [];
        $studentCounter = 1;

        foreach ($classes as $class) {
            $studentsByClass[$class->id] = [];

            for ($i = 1; $i <= self::STUDENTS_PER_CLASS; $i++) {
                $number = str_pad($studentCounter, 3, '0', STR_PAD_LEFT);

                $user = User::create([
                    'name' => "Siswa {$number} Kelas {$class->name}",
                    'email' => "siswa{$number}@student.sekolah.com",
                    'password' => $password,
                    'role' => 'student',
                    'is_active' => true,
                    'must_change_password' => false,
                ]);

                $student = Student::create([
                    'user_id' => $user->id,
                    'nisn' => '00'.str_pad($studentCounter, 8, '0', STR_PAD_LEFT),
                    'nis' => str_pad($studentCounter, 5, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'P' : 'L',
                    'status' => 'active',
                ]);

                $student->classes()->attach($class->id, [
                    'academic_year_id' => $year->id,
                ]);

                $studentsByClass[$class->id][] = $student;
                $studentCounter++;
            }
        }

        $totalStudents = array_sum(array_map('count', $studentsByClass));
        $this->command->info("   ✅ {$totalStudents} Siswa dibuat (20 per kelas).");

        return $studentsByClass;
    }

    private function createSchedulesAndAssignments(
        Collection $classes,
        Collection $subjects,
        Collection $teachers,
        AcademicYear $year
    ): array {
        $schedulesByClass = [];
        $today = Carbon::today();

        foreach ($classes as $class) {
            $schedulesByClass[$class->id] = [];

            foreach ($subjects as $subjectIndex => $subject) {
                $slot = self::TIME_SLOTS[$subjectIndex] ?? [
                    'day' => self::DAYS[$subjectIndex % count(self::DAYS)],
                    'start' => sprintf('%02d:00', 7 + $subjectIndex),
                ];

                $teacher = $teachers[$subjectIndex % $teachers->count()];

                $startTime = $slot['start'].':00';
                $endHour = (int) substr($slot['start'], 0, 2);
                $endMin = (int) substr($slot['start'], 3, 2) + 30;
                if ($endMin >= 60) {
                    $endHour++;
                    $endMin -= 60;
                }
                $endTime = sprintf('%02d:%02d:00', $endHour, $endMin);

                $schedule = Schedule::updateOrCreate(
                    [
                        'class_id' => $class->id,
                        'day_of_week' => $slot['day'],
                        'start_time' => $startTime,
                        'academic_year_id' => $year->id,
                    ],
                    [
                        'subject_id' => $subject->id,
                        'teacher_id' => $teacher->user_id,
                        'end_time' => $endTime,
                    ]
                );

                $types = [
                    ['type' => 'task', 'label' => 'Tugas Harian'],
                    ['type' => 'uts', 'label' => 'UTS'],
                    ['type' => 'uas', 'label' => 'UAS'],
                ];

                foreach ($types as $typeIndex => $typeData) {
                    $dueDate = $today->copy()->addDays(7 * ($typeIndex + 1));

                    Assignment::create([
                        'schedule_id' => $schedule->id,
                        'type' => $typeData['type'],
                        'date' => $today->toDateString(),
                        'title' => "{$typeData['label']} {$subject->name} Kelas {$class->name}",
                        'description' => "Kerjakan {$typeData['label']} {$subject->name} dengan sungguh-sungguh.",
                        'due_date' => $dueDate->format('Y-m-d H:i:s'),
                    ]);
                }

                $schedulesByClass[$class->id][] = $schedule;
            }
        }

        $totalSchedules = array_sum(array_map('count', $schedulesByClass));
        $totalAssignments = Assignment::count();
        $mapelCount = count(self::SUBJECTS);
        $this->command->info("   ✅ {$totalSchedules} Jadwal dibuat ({$mapelCount} mapel × 3 kelas).");
        $this->command->info("   ✅ {$totalAssignments} Assignment dibuat (3 per jadwal: task + uts + uas).");

        return $schedulesByClass;
    }

    private function createMeetingSessions(array $schedulesByClass, AcademicYear $year): void
    {
        $startDate = $year->start_date->copy()->startOfDay();
        $endDate = $year->end_date->copy()->endOfDay();
        $sessionCount = 0;

        foreach ($schedulesByClass as $schedules) {
            foreach ($schedules as $schedule) {
                $dayOfWeek = $schedule->day_of_week;
                $meetingNumber = 1;
                $current = $startDate->copy()->modify("next {$dayOfWeek}");

                if ($current->lt($startDate)) {
                    $current->addWeek();
                }

                $sessionsToInsert = [];

                while ($current->lte($endDate)) {
                    $sessionsToInsert[] = [
                        'schedule_id' => $schedule->id,
                        'meeting_number' => $meetingNumber,
                        'date' => $current->toDateString(),
                        'status' => 'scheduled',
                        'notes' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $meetingNumber++;
                    $current->addWeek();
                }

                if ($sessionsToInsert !== []) {
                    DB::table('meeting_sessions')->insert($sessionsToInsert);
                    $sessionCount += count($sessionsToInsert);
                }
            }
        }

        $this->command->info("   ✅ {$sessionCount} Meeting Sessions dibuat (SEMUA terjadwal dalam semester pendek).");
    }

    private function createHolidays(AcademicYear $year): void
    {
        $this->command->info('   ✅ Tidak ada hari libur (semester pendek).');
    }

    private function createSubmissionsAndGrades(
        array $studentsByClass,
        array $schedulesByClass,
        Collection $teachers
    ): void {
        $graderUserId = $teachers->first()->user_id;
        $submissionCount = 0;
        $gradeCount = 0;

        foreach ($studentsByClass as $classId => $students) {
            $schedules = $schedulesByClass[$classId] ?? [];

            foreach ($students as $student) {
                foreach ($schedules as $schedule) {
                    $assignments = Assignment::where('schedule_id', $schedule->id)->get();

                    foreach ($assignments as $assignment) {
                        $submission = Submission::create([
                            'assignment_id' => $assignment->id,
                            'student_id' => $student->user_id,
                            'file_path' => null,
                            'submitted_at' => now(),
                        ]);
                        $submissionCount++;

                        Grade::create([
                            'submission_id' => $submission->id,
                            'score' => rand(75, 98),
                            'feedback' => null,
                            'graded_by' => $graderUserId,
                        ]);
                        $gradeCount++;
                    }
                }
            }
        }

        $this->command->info("   ✅ {$submissionCount} Submission + {$gradeCount} Grade dibuat (nilai 75-98).");
    }

    private function createAttendanceRecords(
        array $studentsByClass,
        array $schedulesByClass
    ): void {
        $attendanceCount = 0;

        foreach ($studentsByClass as $classId => $students) {
            $schedules = $schedulesByClass[$classId] ?? [];

            foreach ($students as $student) {
                foreach ($schedules as $schedule) {
                    $sessions = MeetingSession::query()
                        ->where('schedule_id', $schedule->id)
                        ->orderBy('meeting_number')
                        ->get();

                    foreach ($sessions as $session) {
                        Attendance::create([
                            'schedule_id' => $schedule->id,
                            'meeting_session_id' => $session->id,
                            'student_id' => $student->user_id,
                            'date' => $session->date,
                            'status' => 'present',
                        ]);
                        $attendanceCount++;
                    }
                }
            }
        }

        $this->command->info("   ✅ {$attendanceCount} Attendance records dibuat (SEMUA pertemuan selesai, status: present).");
    }

    private function printSummary(): void
    {
        $teacherCount = count(self::TEACHERS);
        $subjectCount = count(self::SUBJECTS);
        $scheduleCount = $subjectCount * count(self::CLASS_NAMES);
        $sessionCount = MeetingSession::count();
        $holidayCount = Holiday::count();
        $submissionTotal = 60 * $subjectCount * 3;
        $attendanceCount = Attendance::count();

        $this->command->newLine();
        $this->command->info('✅ ReportCardScenarioSeeder selesai!');
        $this->command->info('   Mode: SEMUA pertemuan selesai, semester pendek, is_report_published = false');
        $this->command->info('   • 1 Tahun Ajaran 2026/2027 (01 Jun — '.Carbon::today()->endOfWeek(Carbon::FRIDAY)->format('d M Y').')');
        $this->command->info('   • 3 Kelas (7A, 8A, 9A)');
        $this->command->info("   • {$teacherCount} Guru (3 Wali Kelas)");
        $this->command->info('   • 60 Siswa (20 per kelas)');
        $this->command->info("   • {$scheduleCount} Jadwal ({$subjectCount} mapel × 3 kelas)");
        $this->command->info("   • {$sessionCount} Meeting Sessions (SEMUA selesai)");
        $this->command->info("   • {$holidayCount} Hari Libur");
        $this->command->info("   • {$attendanceCount} Attendance (SEMUA present)");
        $this->command->info("   • {$submissionTotal} Submission + Grade");
        $this->command->newLine();
        $this->command->info('Login guru: password123');
        $this->command->info('Login siswa: password123');
    }
}
