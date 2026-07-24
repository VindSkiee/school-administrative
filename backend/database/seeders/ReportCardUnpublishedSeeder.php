<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Eskul;
use App\Models\Grade;
use App\Models\GradingSetting;
use App\Models\MeetingSession;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentEskul;
use App\Models\Subject;
use App\Models\SubjectCompetencySetting;
use App\Models\Submission;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ReportCardUnpublishedSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'password123';

    protected bool $reportPublished = false;

    private const TOTAL_MEETINGS = 10;

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
        ['day' => 'monday', 'start' => '07:00'],
        ['day' => 'monday', 'start' => '08:30'],
        ['day' => 'monday', 'start' => '10:00'],
        ['day' => 'tuesday', 'start' => '07:00'],
        ['day' => 'tuesday', 'start' => '08:30'],
        ['day' => 'tuesday', 'start' => '10:00'],
        ['day' => 'wednesday', 'start' => '07:00'],
        ['day' => 'wednesday', 'start' => '08:30'],
        ['day' => 'wednesday', 'start' => '10:00'],
        ['day' => 'thursday', 'start' => '07:00'],
        ['day' => 'thursday', 'start' => '08:30'],
    ];

    /** Class 9A's last schedule (INF - Thursday 08:30) will have NO attendance. */
    private string $incompleteAttendanceClass = '9A';

    private string $incompleteAttendanceSubjectCode = 'INF';

    /** The single student who remains incomplete across all grading areas. */
    private string $incompleteStudentName = 'Siswa 060 Kelas 9A';

    public function run(): void
    {
        $today = Carbon::today();
        $endDate = Carbon::parse('2026-07-27');
        $startDate = $endDate->copy()->subWeeks(self::TOTAL_MEETINGS - 1)->startOfWeek(Carbon::MONDAY);

        $this->command->warn("⚠️  Memulai ReportCardUnpublishedSeeder — SEMUA pertemuan selesai, {$startDate->format('d M')} — {$endDate->format('d M Y')}.");

        $this->clearData();

        $academicYear = $this->createAcademicYear($startDate, $endDate);
        $this->createGradingSetting($academicYear);

        $subjects = $this->createSubjects();
        $this->createCompetencySettings($subjects, $academicYear);

        $classes = $this->createClasses($academicYear);
        $teachers = $this->createTeachers();
        $this->assignHomerooms($teachers, $classes);

        $studentsByClass = $this->createStudents($classes, $academicYear);
        $schedulesByClass = $this->createSchedulesAndAssignments($classes, $subjects, $teachers, $academicYear);

        $this->createMeetingSessions($schedulesByClass, $academicYear);
        $this->createHolidays();

        $this->createSubmissionsAndGrades($studentsByClass, $schedulesByClass, $teachers);
        $this->createRemedialAssignments($studentsByClass, $schedulesByClass, $teachers);
        $this->createAttendanceRecords($studentsByClass, $schedulesByClass);
        $this->createEskulEnrollments($studentsByClass, $teachers);
        $this->assignStudentNotes($studentsByClass);

        $this->printSummary($startDate, $endDate);
    }

    private function clearData(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('grades')->truncate();
        DB::table('submissions')->truncate();
        DB::table('student_eskuls')->truncate();
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
        DB::table('subject_competency_settings')->truncate();
        DB::table('subjects')->truncate();
        DB::table('grading_settings')->truncate();
        DB::table('academic_years')->truncate();

        User::whereIn('role', ['teacher', 'student'])->forceDelete();

        Schema::enableForeignKeyConstraints();

        $this->command->info('   Data lama dibersihkan.');
    }

    private function createAcademicYear(Carbon $startDate, Carbon $endDate): AcademicYear
    {
        $year = AcademicYear::create([
            'name' => '2026/2027',
            'semester' => 'odd',
            'is_active' => true,
            'is_report_published' => $this->reportPublished,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        $this->command->info('   ✅ Tahun Ajaran: 2026/2027 Ganjil');
        $this->command->info("      Periode: {$startDate->format('d M Y')} — {$endDate->format('d M Y')}");
        $this->command->info('      Status: is_report_published = '.($this->reportPublished ? 'true' : 'false'));

        return $year;
    }

    private function createGradingSetting(AcademicYear $year): void
    {
        GradingSetting::create([
            'academic_year_id' => $year->id,
            'task_weight' => 30,
            'daily_exam_weight' => 10,
            'uts_weight' => 25,
            'uas_weight' => 25,
            'attendance_weight' => 10,
            'min_score_to_pass' => 60,
        ]);

        $this->command->info('   ✅ GradingSetting: Task 30% + UH 10% + UTS 25% + UAS 25% + Kehadiran 10%');
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

    private function createCompetencySettings(Collection $subjects, AcademicYear $year): void
    {
        $kkmMap = [
            'PAI' => 60,
            'PPKn' => 55,
            'B.IND' => 65,
            'MTK' => 60,
            'IPA' => 60,
            'IPS' => 55,
            'B.ING' => 65,
            'PJOK' => 55,
            'SBdP' => 55,
            'MULOK' => 50,
            'INF' => 60,
        ];

        foreach ($subjects as $subject) {
            SubjectCompetencySetting::create([
                'subject_id' => $subject->id,
                'academic_year_id' => $year->id,
                'min_score' => $kkmMap[$subject->code] ?? 60,
                'sangat_baik_min' => 85,
                'sangat_baik_text' => 'Mencapai kompetensi dengan sangat baik dalam memahami, menerapkan, dan menganalisis materi pembelajaran.',
                'baik_min' => 75,
                'baik_text' => 'Mencapai kompetensi dengan baik dalam memahami dan menerapkan materi pembelajaran.',
                'kurang_min' => 60,
                'kurang_text' => 'Perlu peningkatan dalam hal memahami dan menerapkan materi pembelajaran.',
                'sangat_kurang_min' => 0,
                'sangat_kurang_text' => 'Perlu bimbingan intensif untuk mencapai ketuntasan belajar.',
            ]);
        }

        $this->command->info('   ✅ '.count(self::SUBJECTS).' Capaian Kompetensi dibuat (KKM per mapel).');
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
                'is_active' => true,
                'must_change_password' => false,
            ]);
            $user->role = 'teacher';
            $user->save();

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
                    'is_active' => true,
                    'must_change_password' => false,
                ]);
                $user->role = 'student';
                $user->save();

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
                    ['type' => 'ujian_harian', 'label' => 'Ujian Harian'],
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
        $this->command->info("   ✅ {$totalAssignments} Assignment dibuat (4 per jadwal: task + uh + uts + uas).");

        return $schedulesByClass;
    }

    private function createMeetingSessions(array $schedulesByClass, AcademicYear $year): void
    {
        $endDate = $year->end_date->copy()->endOfDay();
        $sessionCount = 0;

        foreach ($schedulesByClass as $schedules) {
            foreach ($schedules as $schedule) {
                $dayOfWeek = $schedule->day_of_week;
                $meetingNumber = 1;

                $current = $endDate->copy()->modify("last {$dayOfWeek}");

                $sessionsToInsert = [];

                while ($meetingNumber <= self::TOTAL_MEETINGS) {
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
                    $current->subWeek();
                }

                if ($sessionsToInsert !== []) {
                    DB::table('meeting_sessions')->insert($sessionsToInsert);
                    $sessionCount += count($sessionsToInsert);
                }
            }
        }

        $this->command->info("   ✅ {$sessionCount} Meeting Sessions dibuat (".self::TOTAL_MEETINGS.' per jadwal, SEMUA selesai).');
    }

    private function createHolidays(): void
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
        $incompleteUserId = $this->getIncompleteStudentUserId();

        foreach ($studentsByClass as $classId => $students) {
            $schedules = $schedulesByClass[$classId] ?? [];

            foreach ($students as $student) {
                $isIncomplete = $student->user_id === $incompleteUserId;

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

                        if (! $isIncomplete) {
                            if ($assignment->type === 'ujian_harian') {
                                $studentIndex = array_search($student, $studentsByClass[$classId]);
                                $score = ($studentIndex % 10 < 3)
                                    ? rand(40, 58)
                                    : rand(75, 98);
                            } else {
                                $score = rand(75, 98);
                            }

                            Grade::create([
                                'submission_id' => $submission->id,
                                'score' => $score,
                                'feedback' => null,
                                'graded_by' => $graderUserId,
                            ]);
                            $gradeCount++;
                        }
                    }
                }
            }
        }

        $this->command->info("   ✅ {$submissionCount} Submission dibuat.");
        $this->command->info("   ✅ {$gradeCount} Grade dibuat (nilai 75-98).");
        $this->command->info("   ⚠️  Siswa '{$this->incompleteStudentName}' TIDAK memiliki grade (belum dinilai).");
    }

    private function createRemedialAssignments(
        array $studentsByClass,
        array $schedulesByClass,
        Collection $teachers
    ): void {
        $graderUserId = $teachers->first()->user_id;
        $minScore = 60;
        $remedialCount = 0;
        $remedialSubmissionCount = 0;
        $incompleteUserId = $this->getIncompleteStudentUserId();

        foreach ($studentsByClass as $classId => $students) {
            $schedules = $schedulesByClass[$classId] ?? [];

            foreach ($schedules as $schedule) {
                $uhAssignment = Assignment::where('schedule_id', $schedule->id)
                    ->where('type', 'ujian_harian')
                    ->first();

                if (! $uhAssignment) {
                    continue;
                }

                $belowKKMStudents = [];
                foreach ($students as $student) {
                    if ($student->user_id === $incompleteUserId) {
                        continue;
                    }

                    $submission = Submission::where('assignment_id', $uhAssignment->id)
                        ->where('student_id', $student->user_id)
                        ->first();

                    if ($submission && $submission->grade && $submission->grade->score < $minScore) {
                        $belowKKMStudents[] = [
                            'student' => $student,
                            'original_score' => $submission->grade->score,
                            'grade' => $submission->grade,
                        ];
                    }
                }

                if (empty($belowKKMStudents)) {
                    continue;
                }

                $subject = $schedule->subject;
                $class = $schedule->schoolClass;
                $today = Carbon::today();

                $remedialAssignment = Assignment::create([
                    'schedule_id' => $schedule->id,
                    'type' => 'ujian_harian',
                    'date' => $today->toDateString(),
                    'title' => "Remedial Ujian Harian {$subject->name} Kelas {$class->name}",
                    'description' => "Remedial Ujian Harian {$subject->name} untuk siswa yang belum tuntas.",
                    'due_date' => $today->copy()->addDays(14)->format('Y-m-d H:i:s'),
                    'is_remedial' => true,
                    'remedial_for_type' => 'ujian_harian',
                    'linked_assignment_id' => $uhAssignment->id,
                ]);
                $remedialCount++;

                foreach ($belowKKMStudents as $item) {
                    $student = $item['student'];
                    $originalScore = $item['original_score'];
                    $originalGrade = $item['grade'];

                    $remedialScore = min(95, $originalScore + rand(10, 25));

                    $submission = Submission::create([
                        'assignment_id' => $remedialAssignment->id,
                        'student_id' => $student->user_id,
                        'file_path' => null,
                        'submitted_at' => now(),
                    ]);
                    $remedialSubmissionCount++;

                    Grade::create([
                        'submission_id' => $submission->id,
                        'score' => $remedialScore,
                        'feedback' => 'Nilai remedial dari skor awal '.$originalScore,
                        'graded_by' => $graderUserId,
                        'remedial_mode' => 'replace',
                    ]);

                    $originalGrade->update([
                        'remedial_mode' => 'replace',
                    ]);
                }
            }
        }

        $this->command->info("   ✅ {$remedialCount} Remedial Assignment dibuat.");
        $this->command->info("   ✅ {$remedialSubmissionCount} Remedial Submission + Grade dibuat (skor improved).");
    }

    private function createAttendanceRecords(
        array $studentsByClass,
        array $schedulesByClass
    ): void {
        $attendanceCount = 0;
        $skippedSchedules = 0;

        foreach ($studentsByClass as $classId => $students) {
            $schedules = $schedulesByClass[$classId] ?? [];

            foreach ($students as $student) {
                foreach ($schedules as $schedule) {
                    $subject = $schedule->subject;
                    $class = SchoolClass::find($classId);

                    if ($class && $class->name === $this->incompleteAttendanceClass
                        && $subject->code === $this->incompleteAttendanceSubjectCode) {
                        $skippedSchedules++;

                        continue;
                    }

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
        $this->command->info("   ⚠️  Kelas {$this->incompleteAttendanceClass}, mapel {$this->incompleteAttendanceSubjectCode} TANPA absensi (menunggu guru input).");
    }

    private function createEskulEnrollments(array $studentsByClass, Collection $teachers): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (! $activeYear) {
            return;
        }

        $eskulData = [
            ['name' => 'Pramuka', 'description' => 'Kegiatan Kepramukaan untuk membina karakter.'],
            ['name' => 'Paskibra', 'description' => 'Latihan baris-berbaris dan kepemimpinan.'],
            ['name' => 'Basket', 'description' => 'Latihan dan pertandingan bola basket.'],
            ['name' => 'Futsal', 'description' => 'Latihan dan pertandingan futsal.'],
            ['name' => 'English Club', 'description' => 'Klub bahasa Inggris.'],
        ];

        $eskuls = collect();
        foreach ($eskulData as $data) {
            $eskul = Eskul::firstOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'teacher_id' => $teachers->first()->user_id,
                    'is_active' => true,
                ]
            );
            $eskuls->push($eskul);
        }

        $graderUserId = $teachers->first()->user_id;
        $enrollmentCount = 0;
        $gradedCount = 0;
        $incompleteUserId = $this->getIncompleteStudentUserId();

        foreach ($studentsByClass as $classId => $students) {
            foreach ($students as $student) {
                $eskul = $eskuls->random();

                $exists = StudentEskul::where('student_id', $student->user_id)
                    ->where('eskul_id', $eskul->id)
                    ->where('academic_year_id', $activeYear->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $isIncomplete = $student->user_id === $incompleteUserId;

                StudentEskul::create([
                    'student_id' => $student->user_id,
                    'eskul_id' => $eskul->id,
                    'academic_year_id' => $activeYear->id,
                    'score' => $isIncomplete ? null : rand(70, 95),
                    'graded_at' => $isIncomplete ? null : now(),
                    'graded_by' => $isIncomplete ? null : $graderUserId,
                ]);

                $enrollmentCount++;
                if (! $isIncomplete) {
                    $gradedCount++;
                }
            }
        }

        $ungradedCount = $enrollmentCount - $gradedCount;
        $this->command->info("   ✅ {$enrollmentCount} Student Eskul enrollments dibuat.");
        $this->command->info("   → {$gradedCount} sudah dinilai, {$ungradedCount} belum dinilai (siswa '{$this->incompleteStudentName}').");
    }

    private function assignStudentNotes(array $studentsByClass): void
    {
        $noteCount = 0;

        foreach ($studentsByClass as $classId => $students) {
            foreach ($students as $student) {
                $isIncomplete = $student->user_id === $this->getIncompleteStudentUserId();

                if (! $isIncomplete) {
                    DB::table('class_student')
                        ->where('student_id', $student->user_id)
                        ->where('class_id', $classId)
                        ->update(['note' => 'Catatan wali kelas untuk '.$student->user->name]);
                    $noteCount++;
                }
            }
        }

        $this->command->info("   ✅ {$noteCount} Catatan wali kelas diisi.");
        $this->command->info("   ⚠️  Siswa '{$this->incompleteStudentName}' TIDAK memiliki catatan.");
    }

    private function getIncompleteStudentUserId(): int
    {
        $user = User::where('name', $this->incompleteStudentName)->first();

        if (! $user) {
            throw new \RuntimeException("Siswa tidak ditemukan: {$this->incompleteStudentName}");
        }

        return $user->id;
    }

    private function printSummary(Carbon $startDate, Carbon $endDate): void
    {
        $teacherCount = count(self::TEACHERS);
        $subjectCount = count(self::SUBJECTS);
        $scheduleCount = $subjectCount * count(self::CLASS_NAMES);
        $sessionCount = MeetingSession::count();
        $assignmentTotal = Assignment::where('is_remedial', false)->count();
        $remedialTotal = Assignment::where('is_remedial', true)->count();
        $submissionTotal = Submission::count();
        $gradeTotal = Grade::count();
        $attendanceCount = Attendance::count();
        $eskulCount = StudentEskul::count();
        $eskulGraded = StudentEskul::whereNotNull('score')->count();

        $this->command->newLine();
        $this->command->info('✅ ReportCardUnpublishedSeeder selesai!');
        $this->command->info('   Mode: SEMUA pertemuan selesai, is_report_published = false');
        $this->command->info("   • 1 Tahun Ajaran 2026/2027 ({$startDate->format('d M Y')} — {$endDate->format('d M Y')})");
        $this->command->info('   • 3 Kelas (7A, 8A, 9A)');
        $this->command->info("   • {$teacherCount} Guru (3 Wali Kelas)");
        $this->command->info('   • 60 Siswa (20 per kelas)');
        $this->command->info("   • {$scheduleCount} Jadwal ({$subjectCount} mapel × 3 kelas)");
        $this->command->info("   • {$sessionCount} Meeting Sessions (SEMUA selesai, ".self::TOTAL_MEETINGS.' per jadwal)');
        $this->command->info("   • {$assignmentTotal} Assignment (task + uh + uts + uas) + {$remedialTotal} Remedial");
        $this->command->info("   • {$submissionTotal} Submission + {$gradeTotal} Grade");
        $this->command->info("   • {$attendanceCount} Attendance (SEMUA present, KECUALI kelas {$this->incompleteAttendanceClass} mapel {$this->incompleteAttendanceSubjectCode})");
        $this->command->info("   • {$eskulCount} Eskul enrollments ({$eskulGraded} graded)");
        $this->command->newLine();
        $this->command->info('   ⚠️  DEMO DATA — Kelas 9A TIDAK SIAP dipublikasikan:');
        $this->command->info("      • Absensi mapel {$this->incompleteAttendanceSubjectCode} belum diisi");
        $this->command->info("      • Siswa '{$this->incompleteStudentName}' belum dinilai (tugas, ujian, remedial, eskul, catatan)");
        $this->command->newLine();
        $this->command->info('Login guru: password123');
        $this->command->info('Login siswa: password123');
    }
}
