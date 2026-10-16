<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleAttachment;
use App\Models\ModuleProgress;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WatchLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::getAllSettings();

        $courses = Course::withCount(['modules', 'enrollments'])
            ->latest()
            ->get();

        $totalStudents = User::where('role', 'student')->count();
        $pendingStudents = User::where('role', 'student')->where('status', 'pending')->count();
        $totalEnrollments = Enrollment::count();
        $eligibleCertificates = Enrollment::where('is_eligible_for_certificate', true)->count();
        $totalWatchedSeconds = (int) ModuleProgress::sum('watched_seconds');
        $totalAsyncHoursWatched = round($totalWatchedSeconds / 3600, 1);

        $recentActivities = ActivityLog::with(['user', 'course', 'module'])
            ->latest()
            ->limit(15)
            ->get();

        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->latest('updated_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact(
            'settings',
            'courses',
            'totalStudents',
            'pendingStudents',
            'totalEnrollments',
            'eligibleCertificates',
            'totalAsyncHoursWatched',
            'recentActivities',
            'recentEnrollments'
        ));
    }

    public function showCourse(Course $course)
    {
        $settings = PlatformSetting::getAllSettings();
        $course->load(['modules.attachments', 'enrollments.user']);

        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        $enrolledUserIds = $course->enrollments->pluck('user_id')->toArray();
        $availableStudents = User::where('role', 'student')
            ->whereNotIn('id', $enrolledUserIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $progressMatrix = ModuleProgress::where('course_id', $course->id)
            ->get()
            ->groupBy('user_id');

        $courseWatchLogs = WatchLog::with(['user', 'module'])
            ->where('course_id', $course->id)
            ->latest('last_heartbeat_at')
            ->limit(25)
            ->get();

        return view('admin.course-detail', compact(
            'settings',
            'course',
            'availableStudents',
            'progressMatrix',
            'courseWatchLogs'
        ));
    }

    public function storeCourse(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'unique:courses,code'],
            'title' => ['required', 'string', 'max:255'],
            'course_type' => ['required', 'string', 'max:40'],
            'thematic_area' => ['nullable', 'string', 'max:255'],
            'authorization_details' => ['nullable', 'string', 'max:255'],
            'training_center_name' => ['nullable', 'string', 'max:255'],
            'training_center_city' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'total_hours' => ['required', 'numeric', 'min:1'],
            'sync_required_hours' => ['required', 'numeric', 'min:0'],
            'async_required_hours' => ['required', 'numeric', 'min:0'],
            'min_attendance_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'presence_check_interval_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'invite_code' => ['nullable', 'string', 'max:40'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        $data['strict_tab_pause'] = $request->boolean('strict_tab_pause');
        $data['allow_self_enrollment'] = $request->boolean('allow_self_enrollment', true);
        $data['is_active'] = true;
        $data['presence_check_interval_minutes'] = (int) ($data['presence_check_interval_minutes'] ?? 0);

        $course = Course::create($data);

        ActivityLog::record(
            'course_create',
            "Creato nuovo corso \"{$course->title}\" ({$course->code})",
            auth()->id(),
            $course->id
        );

        return redirect()->route('admin.courses.show', $course)
            ->with('success', "Corso \"{$course->title}\" creato con successo! Ora puoi aggiungere i moduli video e iscrivere i corsisti.");
    }

    public function updateCourse(Request $request, Course $course)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'course_type' => ['required', 'string', 'max:40'],
            'thematic_area' => ['nullable', 'string', 'max:255'],
            'authorization_details' => ['nullable', 'string', 'max:255'],
            'training_center_name' => ['nullable', 'string', 'max:255'],
            'training_center_city' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'total_hours' => ['required', 'numeric', 'min:1'],
            'sync_required_hours' => ['required', 'numeric', 'min:0'],
            'async_required_hours' => ['required', 'numeric', 'min:0'],
            'min_attendance_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'presence_check_interval_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'invite_code' => ['nullable', 'string', 'max:40'],
        ]);

        $data['strict_tab_pause'] = $request->boolean('strict_tab_pause');
        $data['allow_self_enrollment'] = $request->boolean('allow_self_enrollment');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['presence_check_interval_minutes'] = (int) ($data['presence_check_interval_minutes'] ?? 0);

        $course->update($data);

        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        ActivityLog::record(
            'course_update',
            "Aggiornate impostazioni e regole tracciamento del corso \"{$course->title}\"",
            auth()->id(),
            $course->id
        );

        return back()->with('success', 'Impostazioni del corso e regole del player aggiornate.');
    }

    public function storeModule(Request $request, Course $course)
    {
        $data = $request->validate([
            'sort_order' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'instructor_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'video_source_type' => ['required', 'in:url,upload,youtube'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:512000'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        $videoUrl = $data['video_url'] ?? 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';

        if ($data['video_source_type'] === 'upload' && $request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('course-videos', 'public');
            $videoUrl = asset('storage/' . $path);
        }

        $module = CourseModule::create([
            'course_id' => $course->id,
            'sort_order' => (int) $data['sort_order'],
            'title' => $data['title'],
            'instructor_name' => $data['instructor_name'] ?? null,
            'description' => $data['description'] ?? null,
            'video_source_type' => $data['video_source_type'],
            'video_url' => $videoUrl,
            'duration_seconds' => (int) round($data['duration_minutes'] * 60),
            'is_published' => true,
        ]);

        ActivityLog::record(
            'module_create',
            "Aggiunto Modulo #{$module->sort_order} \"{$module->title}\" al corso {$course->code}",
            auth()->id(),
            $course->id,
            $module->id
        );

        return back()->with('success', "Modulo video \"{$module->title}\" aggiunto al corso.");
    }

    public function storeAttachment(Request $request, CourseModule $module)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'attachment_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,zip', 'max:51200'],
        ]);

        $filePath = 'demo-dispense/' . Str::slug($data['title']) . '.pdf';
        $fileSizeKb = 240;

        if ($request->hasFile('attachment_file')) {
            $file = $request->file('attachment_file');
            $filePath = $file->store('module-attachments', 'public');
            $fileSizeKb = max(1, (int) round($file->getSize() / 1024));
        }

        $attachment = ModuleAttachment::create([
            'course_module_id' => $module->id,
            'title' => $data['title'],
            'file_path' => $filePath,
            'file_size_kb' => $fileSizeKb,
        ]);

        ActivityLog::record(
            'attachment_create',
            "Caricata dispensa PDF \"{$attachment->title}\" per il modulo {$module->title}",
            auth()->id(),
            $module->course_id,
            $module->id
        );

        return back()->with('success', "Dispensa PDF \"{$attachment->title}\" allegata al modulo.");
    }

    public function usersIndex(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();
        $search = trim((string) $request->query('q'));
        $courseFilter = $request->query('course_id');

        $query = User::with(['enrollments.course'])
            ->where('role', 'student')
            ->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('fiscal_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($courseFilter) {
            $query->whereHas('enrollments', fn ($q) => $q->where('course_id', $courseFilter));
        }

        $students = $query->get();
        $courses = Course::orderBy('title')->get();

        return view('admin.users', compact('settings', 'students', 'courses', 'search', 'courseFilter'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'fiscal_code' => ['required', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'birth_city' => ['nullable', 'string', 'max:120'],
            'birth_province' => ['nullable', 'string', 'max:10'],
            'birth_country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_list_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
            'course_ids' => ['nullable', 'array'],
            'course_ids.*' => ['exists:courses,id'],
            'sync_hours_completed' => ['nullable', 'numeric', 'min:0'],
        ]);

        $plainPassword = $data['password'] ?: 'Corsista2026!';

        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'fiscal_code' => strtoupper(trim($data['fiscal_code'])),
            'birth_date' => $data['birth_date'] ?? '1985-05-15',
            'birth_city' => $data['birth_city'] ?? 'Roma',
            'birth_province' => strtoupper($data['birth_province'] ?? 'RM'),
            'birth_country' => $data['birth_country'] ?? 'Italia',
            'phone' => $data['phone'] ?? null,
            'national_list_number' => $data['national_list_number'] ?? null,
            'email' => strtolower(trim($data['email'])),
            'role' => 'student',
            'status' => 'active',
            'password' => Hash::make($plainPassword),
        ]);

        $courseIds = $data['course_ids'] ?? [];
        $syncHours = (float) ($data['sync_hours_completed'] ?? 0);

        foreach ($courseIds as $cid) {
            $enrollment = Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => (int) $cid],
                [
                    'status' => 'active',
                    'sync_hours_completed' => $syncHours,
                    'enrolled_at' => now(),
                    'notes' => 'Iscritto da pannello Admin',
                ]
            );
            $enrollment->recalculateProgress();
        }

        ActivityLog::record(
            'admin_user_create',
            "Inserito corsista {$user->full_name} ({$user->email}) e assegnato a " . count($courseIds) . " corsi",
            auth()->id()
        );

        return back()->with('success', "Corsista {$user->full_name} creato e iscritto con successo (Password: {$plainPassword}).");
    }

    public function importUsersCsv(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'default_sync_hours' => ['nullable', 'numeric', 'min:0'],
            'csv_content' => ['required', 'string'],
        ]);

        $course = Course::findOrFail($data['course_id']);
        $defaultSyncHours = (float) ($data['default_sync_hours'] ?? 0);
        $lines = preg_split('/\r\n|\r|\n/', trim($data['csv_content']));
        $importedCount = 0;

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with(strtolower($line), 'nome;') || str_starts_with(strtolower($line), 'nome,')) {
                continue;
            }

            $parts = str_contains($line, ';') ? str_getcsv($line, ';') : str_getcsv($line, ',');
            if (count($parts) < 4) {
                continue;
            }

            $firstName = trim($parts[0] ?? '');
            $lastName = trim($parts[1] ?? '');
            $fiscalCode = strtoupper(trim($parts[2] ?? ''));
            $email = strtolower(trim($parts[3] ?? ''));
            $birthDate = trim($parts[4] ?? '1982-04-12') ?: '1982-04-12';
            $birthCity = trim($parts[5] ?? 'Roma') ?: 'Roma';
            $birthProvince = strtoupper(trim($parts[6] ?? 'RM') ?: 'RM');
            $syncHours = isset($parts[7]) && is_numeric($parts[7]) ? (float) $parts[7] : $defaultSyncHours;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => trim($firstName . ' ' . $lastName),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'fiscal_code' => $fiscalCode,
                    'birth_date' => $birthDate,
                    'birth_city' => $birthCity,
                    'birth_province' => $birthProvince,
                    'birth_country' => 'Italia',
                    'role' => 'student',
                    'status' => 'active',
                    'password' => Hash::make('Corsista2026!'),
                ]
            );

            $enrollment = Enrollment::updateOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                [
                    'status' => 'active',
                    'sync_hours_completed' => $syncHours,
                    'enrolled_at' => now(),
                    'notes' => 'Importazione massiva CSV',
                ]
            );
            $enrollment->recalculateProgress();
            $importedCount++;
        }

        ActivityLog::record(
            'csv_import',
            "Importati e iscritti {$importedCount} corsisti via CSV sul corso \"{$course->title}\"",
            auth()->id(),
            $course->id
        );

        return back()->with('success', "Importazione completata: {$importedCount} corsisti iscritti al corso \"{$course->title}\".");
    }

    public function assignCourse(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'sync_hours_completed' => ['nullable', 'numeric', 'min:0'],
        ]);

        $enrollment = Enrollment::updateOrCreate(
            ['user_id' => $data['user_id'], 'course_id' => $data['course_id']],
            [
                'status' => 'active',
                'sync_hours_completed' => (float) ($data['sync_hours_completed'] ?? 0),
                'enrolled_at' => now(),
            ]
        );
        $enrollment->recalculateProgress();

        $student = User::find($data['user_id']);
        $course = Course::find($data['course_id']);

        ActivityLog::record(
            'admin_enroll',
            "Iscritto {$student->full_name} al corso \"{$course->title}\"",
            auth()->id(),
            $course->id
        );

        return back()->with('success', "Corsista {$student->full_name} iscritto al corso \"{$course->title}\".");
    }

    public function approveUser(User $user)
    {
        $user->update(['status' => 'active']);
        Enrollment::where('user_id', $user->id)->where('status', 'pending')->update(['status' => 'active']);

        ActivityLog::record(
            'user_approve',
            "Approvata registrazione corsista {$user->full_name}",
            auth()->id()
        );

        return back()->with('success', "Account di {$user->full_name} abilitato.");
    }

    public function updateSyncHours(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'sync_hours_completed' => ['required', 'numeric', 'min:0', 'max:200'],
        ]);

        $enrollment->sync_hours_completed = (float) $data['sync_hours_completed'];
        $enrollment->recalculateProgress();

        ActivityLog::record(
            'sync_hours_update',
            "Aggiornate ore sincrone (Zoom) di {$enrollment->user->full_name} a {$enrollment->sync_hours_completed}h",
            auth()->id(),
            $enrollment->course_id
        );

        return back()->with('success', "Ore sincrone aggiornate per {$enrollment->user->full_name} (Totale: {$enrollment->total_hours_completed}h - {$enrollment->attendance_percent}%).");
    }

    public function importZoomCsv(Request $request, Course $course)
    {
        $data = $request->validate([
            'zoom_csv_content' => ['required', 'string'],
        ]);

        $lines = preg_split('/\r\n|\r|\n/', trim($data['zoom_csv_content']));
        $updated = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with(strtolower($line), 'email')) {
                continue;
            }
            $parts = str_contains($line, ';') ? str_getcsv($line, ';') : str_getcsv($line, ',');
            if (count($parts) < 2) {
                continue;
            }
            $emailOrCf = strtolower(trim($parts[0]));
            $hours = (float) str_replace(',', '.', trim($parts[1]));

            $user = User::whereRaw('LOWER(email) = ?', [$emailOrCf])
                ->orWhereRaw('LOWER(fiscal_code) = ?', [$emailOrCf])
                ->first();

            if ($user) {
                $enrollment = Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->first();
                if ($enrollment) {
                    $enrollment->sync_hours_completed = $hours;
                    $enrollment->recalculateProgress();
                    $updated++;
                }
            }
        }

        ActivityLog::record(
            'zoom_csv_import',
            "Importato report ore sincrone Zoom per {$updated} iscritti sul corso {$course->code}",
            auth()->id(),
            $course->id
        );

        return back()->with('success', "Report Zoom elaborato: aggiornate le ore sincrone per {$updated} corsisti.");
    }

    public function logsIndex(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();
        $courseId = $request->query('course_id');
        $userId = $request->query('user_id');

        $watchQuery = WatchLog::with(['user', 'course', 'module'])->latest('last_heartbeat_at');
        $activityQuery = ActivityLog::with(['user', 'course', 'module'])->latest();

        if ($courseId) {
            $watchQuery->where('course_id', $courseId);
            $activityQuery->where('course_id', $courseId);
        }
        if ($userId) {
            $watchQuery->where('user_id', $userId);
            $activityQuery->where('user_id', $userId);
        }

        $watchLogs = $watchQuery->limit(60)->get();
        $activityLogs = $activityQuery->limit(60)->get();
        $courses = Course::orderBy('title')->get();
        $students = User::where('role', 'student')->orderBy('last_name')->get();

        return view('admin.logs', compact(
            'settings',
            'watchLogs',
            'activityLogs',
            'courses',
            'students',
            'courseId',
            'userId'
        ));
    }

    public function exportCourseRegisterPdf(Course $course)
    {
        $settings = PlatformSetting::getAllSettings();
        $course->load(['modules', 'enrollments.user']);

        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        $progressMatrix = ModuleProgress::where('course_id', $course->id)
            ->get()
            ->groupBy('user_id');

        ActivityLog::record(
            'register_pdf_export',
            "Esportato Registro Ufficiale Presenze PDF per il corso \"{$course->title}\"",
            auth()->id(),
            $course->id
        );

        $pdf = Pdf::loadView('pdf.course-register', [
            'settings' => $settings,
            'course' => $course,
            'progressMatrix' => $progressMatrix,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('Registro_Presenze_' . Str::slug($course->code) . '.pdf');
    }

    public function exportCourseRegisterCsv(Course $course)
    {
        $course->load(['modules', 'enrollments.user']);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Registro_' . Str::slug($course->code) . '.csv"',
        ];

        $callback = function () use ($course) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

            fputcsv($out, [
                'Cognome e Nome',
                'Codice Fiscale',
                'Data e Luogo di Nascita',
                'Email',
                'Ore Sincrone (Zoom)',
                'Ore Asincrone (FAD)',
                'Totale Ore Svolte',
                'Monte Ore Corso',
                '% Frequenza',
                'Idoneità Attestato (Min. ' . $course->min_attendance_percent . '%)',
                'N. Attestato',
            ], ';');

            foreach ($course->enrollments as $enrollment) {
                $enrollment->recalculateProgress();
                $u = $enrollment->user;
                fputcsv($out, [
                    $u->full_name,
                    $u->fiscal_code,
                    ($u->birth_date ? $u->birth_date->format('d/m/Y') : '') . ' - ' . $u->birth_city . ' (' . $u->birth_province . ')',
                    $u->email,
                    number_format($enrollment->sync_hours_completed, 2, ',', ''),
                    number_format($enrollment->async_hours_completed, 2, ',', ''),
                    number_format($enrollment->total_hours_completed, 2, ',', ''),
                    number_format($course->total_hours, 2, ',', ''),
                    number_format($enrollment->attendance_percent, 1, ',', '') . '%',
                    $enrollment->is_eligible_for_certificate ? 'IDONEO' : 'IN CORSO',
                    $enrollment->certificate_number ?? '-',
                ], ';');
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportStudentDossierPdf(Enrollment $enrollment)
    {
        $settings = PlatformSetting::getAllSettings();
        $enrollment->load(['user', 'course.modules']);
        $enrollment->recalculateProgress();

        $moduleProgress = ModuleProgress::where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->get()
            ->keyBy('course_module_id');

        $watchLogs = WatchLog::with('module')
            ->where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->orderBy('started_at')
            ->get();

        ActivityLog::record(
            'dossier_pdf_export',
            "Generato Fascicolo Probante Ministeriale PDF per {$enrollment->user->full_name}",
            auth()->id(),
            $enrollment->course_id
        );

        $pdf = Pdf::loadView('pdf.student-dossier', [
            'settings' => $settings,
            'enrollment' => $enrollment,
            'student' => $enrollment->user,
            'course' => $enrollment->course,
            'moduleProgress' => $moduleProgress,
            'watchLogs' => $watchLogs,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Fascicolo_Probante_' . Str::slug($enrollment->user->full_name) . '.pdf');
    }

    public function settingsIndex()
    {
        $settings = PlatformSetting::getAllSettings();
        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'platform_name' => ['required', 'string', 'max:120'],
            'platform_subtitle' => ['nullable', 'string', 'max:255'],
            'provider_name' => ['required', 'string', 'max:255'],
            'provider_legal_rep' => ['required', 'string', 'max:150'],
            'provider_address' => ['nullable', 'string', 'max:255'],
            'ateco_code' => ['required', 'string', 'max:30'],
            'cp_istat_code' => ['required', 'string', 'max:30'],
            'support_email' => ['required', 'email', 'max:150'],
        ]);

        $data['allow_public_registration'] = $request->boolean('allow_public_registration') ? '1' : '0';
        $data['auto_approve_registration'] = $request->boolean('auto_approve_registration') ? '1' : '0';

        PlatformSetting::setValues($data);

        ActivityLog::record(
            'settings_update',
            'Aggiornati parametri White-Label dell\'Ente Erogatore',
            auth()->id()
        );

        return back()->with('success', 'Impostazioni White-Label e dati Ente Erogatore salvati con successo.');
    }
}
