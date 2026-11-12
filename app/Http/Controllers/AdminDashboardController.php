<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Mail\CertificateEarnedMail;
use App\Mail\CourseAssignedMail;
use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleAttachment;
use App\Models\ModuleProgress;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WatchLog;
use App\Models\ZoomRegistrant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $settings = PlatformSetting::getAllSettings();

        $courses = Course::withCount(['modules', 'enrollments'])
            ->latest()
            ->get();

        $totalCourses = $courses->count();
        $totalModules = CourseModule::count();
        $totalStudents = User::where('role', 'student')->count();
        $pendingStudents = User::where('role', 'student')->where('status', 'pending')->count();
        $totalEnrollments = Enrollment::count();
        $eligibleCertificates = Enrollment::where('is_eligible_for_certificate', true)->count();
        $totalWatchedSeconds = (int) ModuleProgress::sum('watched_seconds');
        $totalAsyncHoursWatched = round($totalWatchedSeconds / 3600, 1);
        $totalSyncHoursCompleted = (float) Enrollment::sum('sync_hours_completed');
        $totalHoursDelivered = round($totalAsyncHoursWatched + $totalSyncHoursCompleted, 1);

        $recentCourses = Course::withCount(['modules', 'enrollments'])
            ->latest()
            ->limit(5)
            ->get();

        $recentStudents = User::where('role', 'student')
            ->with(['enrollments.course'])
            ->latest()
            ->limit(6)
            ->get();

        $recentActivities = ActivityLog::with(['user', 'course', 'module'])
            ->latest()
            ->limit(10)
            ->get();

        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->latest('updated_at')
            ->limit(8)
            ->get();

        // Chart Data 1: Corsi con più moduli e iscritti
        $topCourses = $courses->sortByDesc('enrollments_count')->take(5)->values();
        $chartCourseLabels = $topCourses->pluck('title')->map(fn($t) => Str::limit($t, 25))->toArray();
        $chartCourseEnrollments = $topCourses->pluck('enrollments_count')->toArray();
        $chartCourseModules = $topCourses->pluck('modules_count')->toArray();

        // Chart Data 2: Distribuzione Ore (FAD vs Zoom)
        $chartHoursDistribution = [
            'labels' => ['Ore FAD Asincrone', 'Ore Zoom Sincrone'],
            'data' => [max(0.1, $totalAsyncHoursWatched), max(0.1, $totalSyncHoursCompleted)],
        ];

        // Chart Data 3: Esito Iscrizioni (Completati / In corso)
        $inProgressEnrollments = max(0, $totalEnrollments - $eligibleCertificates);
        $chartCompletionDistribution = [
            'labels' => ['Attestato Rilasciato (Completati)', 'In Frequenza (In corso)'],
            'data' => [$eligibleCertificates, $inProgressEnrollments],
        ];

        return view('admin.dashboard', compact(
            'settings',
            'courses',
            'recentCourses',
            'recentStudents',
            'totalCourses',
            'totalModules',
            'totalStudents',
            'pendingStudents',
            'totalEnrollments',
            'eligibleCertificates',
            'totalAsyncHoursWatched',
            'totalSyncHoursCompleted',
            'totalHoursDelivered',
            'recentActivities',
            'recentEnrollments',
            'chartCourseLabels',
            'chartCourseEnrollments',
            'chartCourseModules',
            'chartHoursDistribution',
            'chartCompletionDistribution'
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
            'course_type' => ['nullable', 'string', 'max:40'],
            'edition' => ['nullable', 'string', 'max:120'],
            'thematic_area' => ['nullable', 'string', 'max:255'],
            'authorization_details' => ['nullable', 'string', 'max:255'],
            'training_center_name' => ['nullable', 'string', 'max:255'],
            'training_center_city' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'total_hours' => ['nullable', 'numeric', 'min:0.01'],
            'sync_required_hours' => ['nullable', 'numeric', 'min:0'],
            'async_required_hours' => ['required', 'numeric', 'min:0.01'],
            'min_attendance_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'presence_check_interval_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'invite_code' => ['nullable', 'string', 'max:40'],
            'badge_image_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:4096'],
            'badge_image_url' => ['nullable', 'string', 'max:65000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]);

        // Check if Sync Hours (Zoom) are explicitly disabled
        $enableSync = $request->has('enable_sync_hours')
            ? $request->boolean('enable_sync_hours')
            : ((float) ($data['sync_required_hours'] ?? 0) > 0);

        $syncHours = $enableSync ? (float) ($data['sync_required_hours'] ?? 0) : 0.0;
        $asyncHours = (float) $data['async_required_hours'];
        $totalHours = $enableSync
            ? (float) (($data['total_hours'] ?? null) ?: ($syncHours + $asyncHours))
            : $asyncHours;

        $data['course_type'] = $data['course_type'] ?? 'aggiornamento';
        $data['sync_required_hours'] = $syncHours;
        $data['async_required_hours'] = $asyncHours;
        $data['total_hours'] = max(0.05, $totalHours);
        $data['strict_tab_pause'] = $request->boolean('strict_tab_pause');
        $data['allow_student_certificate_download'] = $request->boolean('allow_student_certificate_download');
        $data['allow_self_enrollment'] = $request->boolean('allow_self_enrollment', true);
        $data['is_active'] = true;
        $data['presence_check_interval_minutes'] = (int) ($data['presence_check_interval_minutes'] ?? 0);
        $data['invite_code'] = !empty($data['invite_code']) ? strtoupper(trim($data['invite_code'])) : null;

        unset($data['badge_image_file']);
        if ($request->hasFile('badge_image_file')) {
            $file = $request->file('badge_image_file');
            $path = $file->store('course-badges', 'public');
            $data['badge_image_url'] = '/storage/' . $path;

            \App\Models\MediaFile::create([
                'title' => 'Badge - ' . ($data['title'] ?? 'Corso'),
                'file_path' => $path,
                'disk' => 'public',
                'media_type' => 'badge',
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size_bytes' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $course = Course::create($data);

        ActivityLog::record(
            'course_create',
            "Creato nuovo corso \"{$course->title}\" ({$course->code})",
            auth()->id(),
            $course->id
        );

        return redirect()->route('admin.courses.show', $course)
            ->with('success', "Corso \"{$course->title}\" creato con successo!");
    }

    public function updateCourse(Request $request, Course $course)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', Rule::unique('courses', 'code')->ignore($course->id)],
            'title' => ['required', 'string', 'max:255'],
            'course_type' => ['nullable', 'string', 'max:40'],
            'edition' => ['nullable', 'string', 'max:120'],
            'thematic_area' => ['nullable', 'string', 'max:255'],
            'authorization_details' => ['nullable', 'string', 'max:255'],
            'training_center_name' => ['nullable', 'string', 'max:255'],
            'training_center_city' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'total_hours' => ['nullable', 'numeric', 'min:0.01'],
            'sync_required_hours' => ['nullable', 'numeric', 'min:0'],
            'async_required_hours' => ['required', 'numeric', 'min:0.01'],
            'min_attendance_percent' => ['required', 'integer', 'min:1', 'max:100'],
            'presence_check_interval_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'invite_code' => ['nullable', 'string', 'max:40'],
            'badge_image_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:4096'],
            'badge_image_url' => ['nullable', 'string', 'max:65000'],
        ]);

        $hasZoomModules = $course->hasZoomModules();
        if ($hasZoomModules) {
            $zoomSec = (int) $course->modules()->where('video_source_type', 'zoom')->sum('duration_seconds');
            $syncHours = round($zoomSec / 3600, 2);
            $enableSync = true;
        } else {
            $enableSync = $request->has('enable_sync_hours')
                ? $request->boolean('enable_sync_hours')
                : ((float) ($data['sync_required_hours'] ?? 0) > 0);
            $syncHours = $enableSync ? (float) ($data['sync_required_hours'] ?? 0) : 0.0;
        }

        $asyncHours = (float) $data['async_required_hours'];
        $totalHours = $enableSync
            ? (float) (($data['total_hours'] ?? null) ?: ($syncHours + $asyncHours))
            : $asyncHours;

        $data['code'] = !empty($data['code']) ? strtoupper(trim($data['code'])) : $course->code;
        $data['course_type'] = $data['course_type'] ?? $course->course_type;
        $data['sync_required_hours'] = $syncHours;
        $data['async_required_hours'] = $asyncHours;
        $data['total_hours'] = max(0.05, $totalHours);
        $data['strict_tab_pause'] = $request->boolean('strict_tab_pause');
        $data['allow_student_certificate_download'] = $request->boolean('allow_student_certificate_download');
        $data['allow_self_enrollment'] = $request->boolean('allow_self_enrollment');
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $course->is_active;
        $data['presence_check_interval_minutes'] = (int) ($data['presence_check_interval_minutes'] ?? 0);
        $data['invite_code'] = !empty($data['invite_code']) ? strtoupper(trim($data['invite_code'])) : null;

        unset($data['badge_image_file']);
        if ($request->boolean('remove_badge_image')) {
            $data['badge_image_url'] = null;
        } elseif ($request->hasFile('badge_image_file')) {
            $file = $request->file('badge_image_file');
            $path = $file->store('course-badges', 'public');
            $data['badge_image_url'] = '/storage/' . $path;

            \App\Models\MediaFile::create([
                'title' => 'Badge - ' . ($course->title ?: 'Corso'),
                'file_path' => $path,
                'disk' => 'public',
                'media_type' => 'badge',
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size_bytes' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $course->update($data);

        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        ActivityLog::record(
            'course_update',
            "Aggiornato corso \"{$course->title}\" ({$course->code})",
            auth()->id(),
            $course->id
        );

        return back()->with('success', 'Impostazioni e parametri del corso aggiornati con successo.');
    }

    public function toggleCourseStatus(Course $course)
    {
        $course->update(['is_active' => !$course->is_active]);

        $stateLabel = $course->is_active ? 'riattivato' : 'disattivato';

        ActivityLog::record(
            'course_status_toggle',
            "Corso \"{$course->title}\" ({$course->code}) {$stateLabel}",
            auth()->id(),
            $course->id
        );

        return back()->with('success', "Corso \"{$course->title}\" {$stateLabel} con successo.");
    }

    public function duplicateCourse(Course $course)
    {
        $course->load(['modules.attachments']);

        // Generate guaranteed unique course code
        $baseCode = $course->code;
        $counter = 1;
        do {
            $suffix = $counter === 1 ? '-COPY' : '-COPY-' . $counter;
            $newCode = strtoupper(Str::limit($baseCode, 40 - strlen($suffix), '') . $suffix);
            $exists = Course::where('code', $newCode)->exists();
            $counter++;
        } while ($exists);

        // Generate guaranteed unique invite/access code if defined
        $newInviteCode = null;
        if (!empty($course->invite_code)) {
            $counter = 1;
            do {
                $suffix = $counter === 1 ? '-CP' : '-CP' . $counter;
                $newInviteCode = strtoupper(Str::limit($course->invite_code, 40 - strlen($suffix), '') . $suffix);
                $exists = Course::where('invite_code', $newInviteCode)->orWhere('code', $newInviteCode)->exists();
                $counter++;
            } while ($exists);
        }

        $newCourse = Course::create([
            'code' => $newCode,
            'title' => $course->title . ' (Copia)',
            'course_type' => $course->course_type,
            'thematic_area' => $course->thematic_area,
            'authorization_details' => $course->authorization_details,
            'training_center_name' => $course->training_center_name,
            'training_center_city' => $course->training_center_city,
            'description' => $course->description,
            'badge_image_url' => $course->badge_image_url,
            'total_hours' => $course->total_hours,
            'sync_required_hours' => $course->sync_required_hours,
            'async_required_hours' => $course->async_required_hours,
            'min_attendance_percent' => $course->min_attendance_percent,
            'strict_tab_pause' => $course->strict_tab_pause,
            'presence_check_interval_minutes' => $course->presence_check_interval_minutes,
            'allow_self_enrollment' => $course->allow_self_enrollment,
            'invite_code' => $newInviteCode,
            'is_active' => $course->is_active,
            'starts_at' => $course->starts_at,
            'ends_at' => $course->ends_at,
        ]);

        // Duplicate all modules and attachments
        foreach ($course->modules as $mod) {
            $newMod = CourseModule::create([
                'course_id' => $newCourse->id,
                'sort_order' => $mod->sort_order,
                'title' => $mod->title,
                'instructor_name' => $mod->instructor_name,
                'description' => $mod->description,
                'video_source_type' => $mod->video_source_type,
                'video_url' => $mod->video_url,
                'zoom_meeting_id' => $mod->zoom_meeting_id,
                'zoom_passcode' => $mod->zoom_passcode,
                'zoom_scheduled_at' => $mod->zoom_scheduled_at,
                'zoom_join_url' => $mod->zoom_join_url,
                'duration_seconds' => $mod->duration_seconds,
                'is_published' => $mod->is_published,
            ]);

            foreach ($mod->attachments as $att) {
                ModuleAttachment::create([
                    'course_module_id' => $newMod->id,
                    'title' => $att->title,
                    'file_name' => $att->file_name,
                    'file_path' => $att->file_path,
                    'file_size_bytes' => $att->file_size_bytes,
                ]);
            }
        }

        ActivityLog::record(
            'course_duplicate',
            "Duplicato corso \"{$course->title}\" ({$course->code}) in \"{$newCourse->title}\" ({$newCourse->code}) con {$course->modules->count()} moduli",
            auth()->id(),
            $newCourse->id
        );

        return redirect()->route('admin.courses.show', $newCourse)
            ->with('success', "Corso duplicato con successo con Codice Univoco [{$newCourse->code}]. Sono stati clonati tutti i {$course->modules->count()} moduli didattici.");
    }

    public function destroyCourse(Course $course)
    {
        $title = $course->title;
        $code = $course->code;

        $course->delete();

        ActivityLog::record(
            'course_delete',
            "Eliminato definitivamente il corso \"{$title}\" ({$code})",
            auth()->id()
        );

        return redirect()->route('admin.dashboard')
            ->with('success', "Corso \"{$title}\" eliminato definitivamente.");
    }

    public function storeModule(Request $request, Course $course)
    {
        $data = $request->validate([
            'sort_order' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'instructor_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'video_source_type' => ['required', 'in:url,upload,youtube,vimeo,zoom'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:512000'],
            'zoom_meeting_id' => ['nullable', 'string', 'max:60'],
            'zoom_occurrence_id' => ['nullable', 'string', 'max:60'],
            'zoom_passcode' => ['nullable', 'string', 'max:60'],
            'zoom_scheduled_at' => ['nullable', 'date'],
            'zoom_scheduled_end_at' => ['nullable', 'date'],
            'zoom_join_url' => ['nullable', 'string', 'max:500'],
            'subtitles_url' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        if ($data['video_source_type'] === 'upload' && $request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('course-videos', 'public');
            $videoUrl = asset('storage/' . $path);
        } elseif ($data['video_source_type'] === 'zoom') {
            $videoUrl = !empty($data['zoom_join_url']) ? trim($data['zoom_join_url']) : null;
        } else {
            $videoUrl = !empty($data['video_url']) ? trim($data['video_url']) : 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4';
        }

        $cleanMeetingId = ($data['video_source_type'] === 'zoom' && !empty($data['zoom_meeting_id']))
            ? trim(str_replace(' ', '', $data['zoom_meeting_id']))
            : null;

        $module = CourseModule::create([
            'course_id' => $course->id,
            'sort_order' => (int) $data['sort_order'],
            'title' => $data['title'],
            'instructor_name' => $data['instructor_name'] ?? null,
            'description' => $data['description'] ?? null,
            'video_source_type' => $data['video_source_type'],
            'video_url' => $videoUrl,
            'zoom_meeting_id' => $cleanMeetingId,
            'zoom_occurrence_id' => $data['video_source_type'] === 'zoom' ? ($data['zoom_occurrence_id'] ?? null) : null,
            'zoom_passcode' => $data['video_source_type'] === 'zoom' ? ($data['zoom_passcode'] ?? null) : null,
            'zoom_scheduled_at' => ($data['video_source_type'] === 'zoom' && !empty($data['zoom_scheduled_at'])) ? $data['zoom_scheduled_at'] : null,
            'zoom_scheduled_end_at' => ($data['video_source_type'] === 'zoom' && !empty($data['zoom_scheduled_end_at'])) ? $data['zoom_scheduled_end_at'] : null,
            'zoom_join_url' => $data['video_source_type'] === 'zoom' ? ($data['zoom_join_url'] ?? null) : null,
            'subtitles_url' => $data['subtitles_url'] ?? null,
            'duration_seconds' => (int) round($data['duration_minutes'] * 60),
            'is_published' => true,
        ]);

        $course->autoSyncDurationsFromModules();

        ActivityLog::record(
            'module_create',
            "Aggiunto Modulo #{$module->sort_order} \"{$module->title}\" ({$module->video_source_type}) al corso {$course->code}",
            auth()->id(),
            $course->id,
            $module->id
        );

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Modulo \"{$module->title}\" aggiunto al corso.");
    }

    public function updateModule(Request $request, CourseModule $module)
    {
        $data = $request->validate([
            'sort_order' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'instructor_name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'video_source_type' => ['required', 'in:url,upload,youtube,vimeo,zoom'],
            'video_url' => ['nullable', 'string', 'max:500'],
            'video_file' => ['nullable', 'file', 'mimes:mp4,webm,ogg', 'max:512000'],
            'zoom_meeting_id' => ['nullable', 'string', 'max:60'],
            'zoom_occurrence_id' => ['nullable', 'string', 'max:60'],
            'zoom_passcode' => ['nullable', 'string', 'max:60'],
            'zoom_scheduled_at' => ['nullable', 'date'],
            'zoom_scheduled_end_at' => ['nullable', 'date'],
            'zoom_join_url' => ['nullable', 'string', 'max:500'],
            'subtitles_url' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['required', 'numeric', 'min:1'],
        ]);

        if ($data['video_source_type'] === 'upload' && $request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('course-videos', 'public');
            $videoUrl = asset('storage/' . $path);
        } elseif ($data['video_source_type'] === 'zoom') {
            $videoUrl = !empty($data['zoom_join_url']) ? trim($data['zoom_join_url']) : null;
        } else {
            $videoUrl = !empty($data['video_url']) ? trim($data['video_url']) : ($module->video_source_type !== 'zoom' ? $module->video_url : null);
        }

        $cleanMeetingId = ($data['video_source_type'] === 'zoom')
            ? (!empty($data['zoom_meeting_id']) ? trim(str_replace(' ', '', $data['zoom_meeting_id'])) : $module->zoom_meeting_id)
            : null;

        $module->update([
            'sort_order' => (int) $data['sort_order'],
            'title' => $data['title'],
            'instructor_name' => $data['instructor_name'] ?? null,
            'description' => $data['description'] ?? null,
            'video_source_type' => $data['video_source_type'],
            'video_url' => $videoUrl,
            'zoom_meeting_id' => $cleanMeetingId,
            'zoom_occurrence_id' => $data['video_source_type'] === 'zoom' ? ($data['zoom_occurrence_id'] ?? $module->zoom_occurrence_id) : null,
            'zoom_passcode' => $data['video_source_type'] === 'zoom' ? ($data['zoom_passcode'] ?: $module->zoom_passcode) : null,
            'zoom_scheduled_at' => ($data['video_source_type'] === 'zoom') ? (!empty($data['zoom_scheduled_at']) ? $data['zoom_scheduled_at'] : $module->zoom_scheduled_at) : null,
            'zoom_scheduled_end_at' => ($data['video_source_type'] === 'zoom') ? (!empty($data['zoom_scheduled_end_at']) ? $data['zoom_scheduled_end_at'] : $module->zoom_scheduled_end_at) : null,
            'zoom_join_url' => $data['video_source_type'] === 'zoom' ? ($data['zoom_join_url'] ?: $module->zoom_join_url) : null,
            'subtitles_url' => !empty($data['subtitles_url']) ? trim($data['subtitles_url']) : $module->subtitles_url,
            'duration_seconds' => (int) round($data['duration_minutes'] * 60),
            'is_published' => $request->has('is_published') ? $request->boolean('is_published') : $module->is_published,
        ]);

        $module->course?->autoSyncDurationsFromModules();

        ActivityLog::record(
            'module_update',
            "Modificato Modulo #{$module->sort_order} \"{$module->title}\"",
            auth()->id(),
            $module->course_id,
            $module->id
        );

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Modulo \"{$module->title}\" aggiornato con successo.");
    }

    public function reorderModules(Request $request, Course $course)
    {
        $data = $request->validate([
            'module_ids' => ['required', 'array'],
            'module_ids.*' => ['integer', 'exists:course_modules,id'],
        ]);

        foreach ($data['module_ids'] as $index => $id) {
            CourseModule::where('id', $id)
                ->where('course_id', $course->id)
                ->update(['sort_order' => $index + 1]);
        }

        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        ActivityLog::record(
            'modules_reorder',
            "Riordinati " . count($data['module_ids']) . " moduli didattici per il corso {$course->code}",
            auth()->id(),
            $course->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Ordine dei moduli aggiornato con successo.',
        ]);
    }

    public function syncModuleZoomAttendance(CourseModule $module)
    {
        $result = \App\Services\ZoomService::syncMeetingParticipantsFromApi($module);

        ActivityLog::record(
            'zoom_module_sync',
            "Sincronizzazione presenze Zoom per Modulo #{$module->sort_order} \"{$module->title}\": {$result['message']}",
            auth()->id(),
            $module->course_id,
            $module->id
        );

        if ($result['success']) {
            return back()
                ->with('active_tab', 'modules')
                ->with('success', $result['message']);
        }

        return back()
            ->with('active_tab', 'modules')
            ->withErrors(['zoom_sync' => $result['message']]);
    }

    public function syncCourseZoomMeeting(Request $request, Course $course)
    {
        $data = $request->validate([
            'zoom_meeting_id' => ['required', 'string', 'max:60'],
        ]);

        $result = \App\Services\ZoomService::syncCourseFromZoomMeeting($course, $data['zoom_meeting_id']);

        ActivityLog::record(
            'zoom_course_sync',
            "Sincronizzazione date e lezioni da Zoom per il corso {$course->code}: {$result['message']}",
            auth()->id(),
            $course->id
        );

        if ($result['success']) {
            return back()
                ->with('active_tab', 'modules')
                ->with('success', $result['message']);
        }

        return back()
            ->with('active_tab', 'modules')
            ->withErrors(['zoom_sync' => $result['message']]);
    }

    public function toggleModuleStatus(CourseModule $module)
    {
        $module->update(['is_published' => !$module->is_published]);
        $state = $module->is_published ? 'pubblicato' : 'nascosto (bozza)';

        ActivityLog::record(
            'module_status_toggle',
            "Modulo #{$module->sort_order} \"{$module->title}\" {$state}",
            auth()->id(),
            $module->course_id,
            $module->id
        );

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Modulo \"{$module->title}\" {$state}.");
    }

    public function duplicateModule(CourseModule $module)
    {
        $course = $module->course;
        $maxOrder = $course ? ($course->modules()->max('sort_order') ?? 0) : 0;

        $newModule = CourseModule::create([
            'course_id' => $module->course_id,
            'sort_order' => $maxOrder + 1,
            'title' => $module->title . ' (Copia)',
            'instructor_name' => $module->instructor_name,
            'description' => $module->description,
            'video_source_type' => $module->video_source_type,
            'video_url' => $module->video_url,
            'zoom_meeting_id' => $module->zoom_meeting_id,
            'zoom_passcode' => $module->zoom_passcode,
            'zoom_scheduled_at' => $module->zoom_scheduled_at,
            'zoom_join_url' => $module->zoom_join_url,
            'duration_seconds' => $module->duration_seconds,
            'is_published' => $module->is_published,
        ]);

        foreach ($module->attachments as $att) {
            ModuleAttachment::create([
                'course_module_id' => $newModule->id,
                'title' => $att->title,
                'file_name' => $att->file_name,
                'file_path' => $att->file_path,
                'file_size_bytes' => $att->file_size_bytes,
            ]);
        }

        $course?->autoSyncDurationsFromModules();

        ActivityLog::record(
            'module_duplicate',
            "Duplicato modulo #{$module->sort_order} \"{$module->title}\" in Modulo #{$newModule->sort_order} \"{$newModule->title}\"",
            auth()->id(),
            $course?->id,
            $newModule->id
        );

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Modulo duplicato con successo con titolo \"{$newModule->title}\" (assegnato come Modulo #{$newModule->sort_order}).");
    }

    public function destroyModule(CourseModule $module)
    {
        $course = $module->course;
        $title = $module->title;
        $wasZoom = $module->video_source_type === 'zoom';

        $module->delete();

        if ($course) {
            if ($wasZoom && !$course->modules()->where('video_source_type', 'zoom')->exists()) {
                $course->sync_required_hours = 0.0;
                $course->save();
            }
            $course->autoSyncDurationsFromModules();
        }

        ActivityLog::record(
            'module_delete',
            "Eliminato modulo \"{$title}\" dal corso {$course?->code}",
            auth()->id(),
            $course?->id
        );

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Modulo \"{$title}\" eliminato e ore corso ricalcolate.");
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

            \App\Models\MediaFile::create([
                'title' => 'Allegato: ' . $data['title'],
                'file_path' => $filePath,
                'disk' => 'public',
                'media_type' => 'document',
                'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
                'file_size_bytes' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
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

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Dispensa PDF \"{$attachment->title}\" allegata al modulo.");
    }

    public function destroyAttachment(ModuleAttachment $attachment)
    {
        $title = $attachment->title;
        $courseId = $attachment->module?->course_id;
        $moduleId = $attachment->course_module_id;

        $attachment->delete();

        ActivityLog::record(
            'attachment_delete',
            "Rimossa dispensa PDF \"{$title}\"",
            auth()->id(),
            $courseId,
            $moduleId
        );

        return back()
            ->with('active_tab', 'modules')
            ->with('success', "Dispensa \"{$title}\" rimossa dal modulo.");
    }

    public function usersIndex(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();
        $search = trim((string) $request->query('q'));
        $courseFilter = $request->query('course_id');
        $roleFilter = $request->query('role');

        $query = User::with(['enrollments.course'])
            ->latest();

        if ($roleFilter && in_array($roleFilter, ['student', 'admin', 'tutor'])) {
            $query->where('role', $roleFilter);
        }

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

        return view('admin.users', compact('settings', 'students', 'courses', 'search', 'courseFilter', 'roleFilter'));
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
            'role' => ['nullable', 'in:student,admin,tutor'],
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
            'role' => $data['role'] ?? 'student',
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

    public function updateUser(Request $request, User $user)
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
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['nullable', 'in:student,admin,tutor'],
            'status' => ['nullable', 'in:active,pending,suspended'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $payload = [
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'fiscal_code' => strtoupper(trim($data['fiscal_code'])),
            'birth_date' => ($data['birth_date'] ?? null) ?: $user->birth_date,
            'birth_city' => $data['birth_city'] ?? $user->birth_city,
            'birth_province' => strtoupper($data['birth_province'] ?? ($user->birth_province ?: 'RM')),
            'birth_country' => $data['birth_country'] ?? ($user->birth_country ?: 'Italia'),
            'phone' => $data['phone'] ?? null,
            'national_list_number' => $data['national_list_number'] ?? null,
            'email' => strtolower(trim($data['email'])),
            'status' => $data['status'] ?? $user->status,
        ];

        if (!empty($data['role'])) {
            $payload['role'] = $data['role'];
        }

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        ActivityLog::record(
            'admin_user_update',
            "Aggiornata scheda utente di {$user->full_name} ({$user->email})",
            auth()->id()
        );

        return back()->with('success', "Dati dell'utente {$user->full_name} aggiornati con successo.");
    }

    public function toggleUserStatus(User $user)
    {
        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        $label = $newStatus === 'active' ? 'riattivato' : 'sospeso/disattivato';

        ActivityLog::record(
            'admin_user_status',
            "Account corsista {$user->full_name} {$label}",
            auth()->id()
        );

        return back()->with('success', "Account di {$user->full_name} {$label}.");
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Non puoi eliminare il tuo account amministratore attivo.']);
        }

        $fullName = $user->full_name;
        $email = $user->email;

        $user->delete();

        ActivityLog::record(
            'admin_user_delete',
            "Eliminato definitivamente l'utente {$fullName} ({$email})",
            auth()->id()
        );

        return back()->with('success', "Utente {$fullName} eliminato definitivamente.");
    }

    public function resetEnrollmentProgress(Enrollment $enrollment)
    {
        $studentName = $enrollment->user?->full_name ?? 'Corsista';
        $courseTitle = $enrollment->course?->title ?? 'Corso';

        // Delete module progress records
        ModuleProgress::where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->delete();

        // Delete raw watch telemetry logs for this user/course
        WatchLog::where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->delete();

        // Reset enrollment attendance and revoke issued certificate
        $enrollment->update([
            'status' => 'active',
            'async_seconds_completed' => 0,
            'total_hours_completed' => $enrollment->course?->has_sync_hours ? $enrollment->sync_hours_completed : 0.0,
            'attendance_percent' => $enrollment->course?->has_sync_hours 
                ? min(100.0, round((($enrollment->sync_hours_completed * 3600) / max(1, $enrollment->course->total_hours * 3600)) * 100, 1))
                : 0.0,
            'is_eligible_for_certificate' => false,
            'certificate_number' => null,
            'certificate_issued_at' => null,
        ]);

        ActivityLog::record(
            'admin_enrollment_reset',
            "Azzerato avanzamento del corso \"{$courseTitle}\" per il corsista {$studentName} (richiesta ripetizione corso)",
            auth()->id(),
            $enrollment->course_id
        );

        return back()->with('success', "Avanzamento del corso \"{$courseTitle}\" per {$studentName} azzerato con successo. Il corsista dovrà ripetere le lezioni.");
    }

    public function resetModuleProgress(Enrollment $enrollment, CourseModule $module)
    {
        $studentName = $enrollment->user?->full_name ?? 'Corsista';

        ModuleProgress::where('user_id', $enrollment->user_id)
            ->where('course_module_id', $module->id)
            ->delete();

        WatchLog::where('user_id', $enrollment->user_id)
            ->where('course_module_id', $module->id)
            ->delete();

        $enrollment->recalculateProgress();

        ActivityLog::record(
            'admin_module_reset',
            "Azzerato avanzamento del modulo \"{$module->title}\" nel corso \"{$enrollment->course->title}\" per {$studentName}",
            auth()->id(),
            $enrollment->course_id,
            $module->id
        );

        return back()->with('success', "Modulo \"{$module->title}\" azzerato per {$studentName}.");
    }

    public function destroyEnrollment(Enrollment $enrollment)
    {
        $studentName = $enrollment->user?->full_name ?? 'Corsista';
        $courseTitle = $enrollment->course?->title ?? 'Corso';
        $courseId = $enrollment->course_id;

        ModuleProgress::where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->delete();

        $enrollment->delete();

        ActivityLog::record(
            'admin_enrollment_delete',
            "Rimossa iscrizione di {$studentName} dal corso \"{$courseTitle}\"",
            auth()->id(),
            $courseId
        );

        return back()->with('success', "Iscrizione di {$studentName} al corso \"{$courseTitle}\" rimossa.");
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
                    'sync_hours_completed' => $course->has_sync_hours ? $syncHours : 0,
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

        $course = Course::findOrFail($data['course_id']);

        $enrollment = Enrollment::updateOrCreate(
            ['user_id' => $data['user_id'], 'course_id' => $course->id],
            [
                'status' => 'active',
                'sync_hours_completed' => $course->has_sync_hours ? (float) ($data['sync_hours_completed'] ?? 0) : 0,
                'enrolled_at' => now(),
            ]
        );
        $enrollment->recalculateProgress();

        $student = User::find($data['user_id']);

        if ($student && PlatformSetting::isMailActive('assigned')) {
            $settings = PlatformSetting::getAllSettings();
            try {
                Mail::to($student->email)->send(new CourseAssignedMail($student, $course, $settings));
                ActivityLog::record(
                    'course_assigned_mail_sent',
                    "Inviata email di assegnazione corso \"{$course->title}\" a {$student->email}",
                    $student->id,
                    $course->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Impossibile inviare email assegnazione corso a {$student->email}: " . $e->getMessage());
            }
        }

        ActivityLog::record(
            'admin_enroll',
            "Iscritto {$student->full_name} al corso \"{$course->title}\"",
            auth()->id(),
            $course->id
        );

        return back()->with('success', "Corsista {$student->full_name} iscritto al corso \"{$course->title}\" con successo.");
    }

    public function approveUser(User $user)
    {
        $user->update(['status' => 'active']);
        Enrollment::where('user_id', $user->id)->where('status', 'pending')->update(['status' => 'active']);

        $settings = PlatformSetting::getAllSettings();
        if (PlatformSetting::isMailActive('approved')) {
            try {
                Mail::to($user->email)->send(new AccountApprovedMail($user, $settings));
                ActivityLog::record(
                    'account_approved_mail_sent',
                    "Inviata email di conferma approvazione account a {$user->email}",
                    $user->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("Impossibile inviare email di approvazione account a {$user->email}: " . $e->getMessage());
            }
        }

        ActivityLog::record(
            'user_approve',
            "Approvata registrazione corsista {$user->full_name}",
            auth()->id()
        );

        return back()->with('success', "Account di {$user->full_name} abilitato ed email di notifica inviata.");
    }

    public function updateSyncHours(Request $request, Enrollment $enrollment)
    {
        if (\App\Services\ZoomService::isModuleEnabled() || $enrollment->course()->whereHas('modules', fn($q) => $q->where('video_source_type', 'zoom'))->exists()) {
            return back()->withErrors([
                'sync_hours' => 'Modifica manuale disabilitata per conformità ministeriale: le ore sincrone Zoom vengono calcolate esclusivamente dai log certificati delle sessioni Zoom.',
            ]);
        }

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

    public function exportLogsPdf(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();
        $courseId = $request->query('course_id');
        $userId = $request->query('user_id');

        $selectedCourse = $courseId ? Course::find($courseId) : null;
        $selectedUser = $userId ? User::find($userId) : null;

        $watchQuery = WatchLog::with(['user', 'course', 'module'])->latest('started_at');
        $activityQuery = ActivityLog::with(['user', 'course', 'module'])->latest('created_at');

        if ($courseId) {
            $watchQuery->where('course_id', $courseId);
            $activityQuery->where('course_id', $courseId);
        }
        if ($userId) {
            $watchQuery->where('user_id', $userId);
            $activityQuery->where('user_id', $userId);
        }

        $watchLogs = $watchQuery->limit(500)->get();
        $activityLogs = $activityQuery->limit(500)->get();

        ActivityLog::record(
            'logs_pdf_export',
            "Esportato Registro Ufficiale Audit & Log PDF (Filtri: Corso: " . ($selectedCourse ? $selectedCourse->code : 'Tutti') . ", Corsista: " . ($selectedUser ? $selectedUser->full_name : 'Tutti') . ")",
            auth()->id(),
            $courseId ?: null
        );

        $pdf = Pdf::loadView('pdf.audit-logs', [
            'settings' => $settings,
            'watchLogs' => $watchLogs,
            'activityLogs' => $activityLogs,
            'selectedCourse' => $selectedCourse,
            'selectedUser' => $selectedUser,
            'filters' => [
                'course_id' => $courseId,
                'user_id' => $userId,
            ],
        ])->setPaper('a4', 'landscape');

        $filenameSuffix = '';
        if ($selectedCourse) {
            $filenameSuffix .= '_' . Str::slug($selectedCourse->code);
        }
        if ($selectedUser) {
            $filenameSuffix .= '_' . Str::slug($selectedUser->full_name);
        }
        if (empty($filenameSuffix)) {
            $filenameSuffix = '_Generale';
        }

        return $pdf->download('Registro_Audit_Log' . $filenameSuffix . '_' . date('Ymd_His') . '.pdf');
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

    public function exportCourseDossierPdf(Course $course)
    {
        $settings = PlatformSetting::getAllSettings();
        $course->load([
            'modules' => fn($q) => $q->orderBy('sort_order'),
            'enrollments.user'
        ]);

        foreach ($course->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        $progressMatrix = ModuleProgress::where('course_id', $course->id)
            ->get()
            ->groupBy('user_id');

        $watchLogsByUser = WatchLog::with('module')
            ->where('course_id', $course->id)
            ->orderBy('started_at')
            ->get()
            ->groupBy('user_id');

        $zoomRegistrantsByUser = ZoomRegistrant::with('module')
            ->where('course_id', $course->id)
            ->get()
            ->groupBy('user_id');

        ActivityLog::record(
            'course_dossier_pdf_export',
            "Esportato Fascicolo Probante Ufficiale di Corso PDF per \"{$course->title}\"",
            auth()->id(),
            $course->id
        );

        $pdf = Pdf::loadView('pdf.course-dossier', [
            'settings' => $settings,
            'course' => $course,
            'progressMatrix' => $progressMatrix,
            'watchLogsByUser' => $watchLogsByUser,
            'zoomRegistrantsByUser' => $zoomRegistrantsByUser,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('Fascicolo_Probante_Corso_' . Str::slug($course->code) . '.pdf');
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
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

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

        $zoomRegistrants = ZoomRegistrant::with('module')
            ->where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->get()
            ->keyBy('course_module_id');

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
            'zoomRegistrants' => $zoomRegistrants,
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
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:4096'],
            'logo_url' => ['nullable', 'string', 'max:65000'],
            'favicon_file' => ['nullable', 'file', 'mimes:ico,png,svg,webp', 'max:2048'],
            'favicon_url' => ['nullable', 'string', 'max:65000'],
            'logo_bg' => ['nullable', 'in:white,dark,transparent'],
            'logo_size' => ['nullable', 'in:sm,md,lg'],
            'zoom_account_id' => ['nullable', 'string', 'max:255'],
            'zoom_client_id' => ['nullable', 'string', 'max:255'],
            'zoom_client_secret' => ['nullable', 'string', 'max:255'],
            'zoom_webhook_secret_token' => ['nullable', 'string', 'max:255'],
        ]);

        unset($data['logo_file'], $data['favicon_file']);

        if ($request->boolean('remove_logo')) {
            $data['logo_url'] = '';
        } elseif ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('brand-logos', 'public');
            $data['logo_url'] = '/storage/' . $path;
        } elseif (!$request->filled('logo_url')) {
            unset($data['logo_url']);
        }

        if ($request->boolean('remove_favicon')) {
            $data['favicon_url'] = '';
        } elseif ($request->hasFile('favicon_file')) {
            $path = $request->file('favicon_file')->store('brand-favicons', 'public');
            $data['favicon_url'] = '/storage/' . $path;
        } elseif (!$request->filled('favicon_url')) {
            unset($data['favicon_url']);
        }

        $data['logo_bg'] = $data['logo_bg'] ?? 'white';
        $data['logo_size'] = $data['logo_size'] ?? 'md';
        $data['hide_brand_text_with_logo'] = $request->boolean('hide_brand_text_with_logo') ? '1' : '0';
        $data['primary_color'] = $data['primary_color'] ?? '#4f46e5';
        $data['secondary_color'] = $data['secondary_color'] ?? '#1e1b4b';
        $data['accent_color'] = $data['accent_color'] ?? '#10b981';

        $data['allow_public_registration'] = $request->boolean('allow_public_registration') ? '1' : '0';
        $data['auto_approve_registration'] = $request->boolean('auto_approve_registration') ? '1' : '0';
        $data['zoom_module_enabled'] = $request->boolean('zoom_module_enabled') ? '1' : '0';
        $data['zoom_account_id'] = trim((string) ($data['zoom_account_id'] ?? ''));
        $data['zoom_client_id'] = trim((string) ($data['zoom_client_id'] ?? ''));
        $data['zoom_client_secret'] = trim((string) ($data['zoom_client_secret'] ?? ''));
        $data['zoom_webhook_secret_token'] = trim((string) ($data['zoom_webhook_secret_token'] ?? ''));

        PlatformSetting::setValues($data);

        ActivityLog::record(
            'settings_update',
            'Aggiornati parametri White-Label (Logo, Colori Brand e Dati Ente Erogatore)',
            auth()->id()
        );

        return back()->with('success', 'Impostazioni White-Label, Logo e Colori Brand salvati con successo.');
    }
}
