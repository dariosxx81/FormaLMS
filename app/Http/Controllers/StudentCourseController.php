<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\ModuleAttachment;
use App\Models\ModuleProgress;
use App\Models\PlatformSetting;
use App\Models\WatchLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentCourseController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        $settings = PlatformSetting::getAllSettings();

        $enrollments = Enrollment::with(['course.modules.attachments'])
            ->where('user_id', $user->id)
            ->latest('enrolled_at')
            ->get();

        foreach ($enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }

        $enrolledCourseIds = $enrollments->pluck('course_id')->toArray();

        $availableCourses = Course::where('is_active', true)
            ->where('allow_self_enrollment', true)
            ->whereNotIn('id', $enrolledCourseIds)
            ->withCount('modules')
            ->get();

        $progressByModule = ModuleProgress::where('user_id', $user->id)
            ->get()
            ->keyBy('course_module_id');

        $recentLogs = ActivityLog::with(['course', 'module'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('student.dashboard', compact(
            'user',
            'settings',
            'enrollments',
            'availableCourses',
            'progressByModule',
            'recentLogs'
        ));
    }

    public function enroll(Request $request, Course $course)
    {
        $user = auth()->user();

        if (!$course->is_active || !$course->allow_self_enrollment) {
            return back()->withErrors(['course' => 'Questo corso non accetta auto-iscrizioni dirette.']);
        }

        if ($course->invite_code && trim((string) $request->input('invite_code')) !== $course->invite_code) {
            return back()->withErrors(['invite_code' => 'Codice invito corso non valido.']);
        }

        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'status' => 'active',
                'enrolled_at' => now(),
                'notes' => 'Auto-iscrizione da catalogo corsista',
            ]
        );
        $enrollment->recalculateProgress();

        ActivityLog::record(
            'course_enroll',
            "Iscrizione al corso \"{$course->title}\" ({$course->code})",
            $user->id,
            $course->id
        );

        return redirect()->route('student.courses.show', $course)
            ->with('success', "Iscrizione al corso \"{$course->title}\" attivata con successo.");
    }

    public function showCourse(Course $course, ?CourseModule $module = null)
    {
        $user = auth()->user();
        $settings = PlatformSetting::getAllSettings();

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment && !$user->isAdmin()) {
            return redirect()->route('student.dashboard')
                ->withErrors(['course' => 'Non risulti iscritto a questo corso.']);
        }

        if (!$enrollment && $user->isAdmin()) {
            $enrollment = Enrollment::firstOrCreate(
                ['user_id' => $user->id, 'course_id' => $course->id],
                ['status' => 'active', 'sync_hours_completed' => $course->sync_required_hours, 'enrolled_at' => now()]
            );
        }

        $course->load(['modules.attachments']);
        $modules = $course->modules;

        if ($modules->isEmpty()) {
            return redirect()->route('student.dashboard')
                ->withErrors(['course' => 'Questo corso non ha ancora moduli pubblicati.']);
        }

        $progressMap = ModuleProgress::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('course_module_id');

        if (!$module) {
            // Pick first uncompleted module or the first module
            $activeModule = $modules->first(function ($m) use ($progressMap) {
                return !($progressMap[$m->id]->is_completed ?? false);
            }) ?? $modules->first();
        } else {
            abort_unless($module->course_id === $course->id, 404);
            $activeModule = $module;
        }

        $currentProgress = ModuleProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_module_id' => $activeModule->id,
            ],
            [
                'watched_seconds' => 0,
                'max_position_seconds' => 0,
                'last_position_seconds' => 0,
                'completion_percent' => 0,
                'is_completed' => false,
                'started_at' => now(),
            ]
        );

        $progressMap[$activeModule->id] = $currentProgress;
        $enrollment->recalculateProgress();

        $sessionToken = Str::random(40);

        ActivityLog::record(
            'module_open',
            "Apertura modulo #{$activeModule->sort_order}: {$activeModule->title}",
            $user->id,
            $course->id,
            $activeModule->id
        );

        return view('student.course-player', compact(
            'user',
            'settings',
            'course',
            'enrollment',
            'modules',
            'activeModule',
            'currentProgress',
            'progressMap',
            'sessionToken'
        ));
    }

    public function heartbeat(Request $request, Course $course, CourseModule $module)
    {
        $user = auth()->user();
        abort_unless($module->course_id === $course->id, 404);

        $data = $request->validate([
            'session_token' => ['required', 'string', 'max:64'],
            'current_time' => ['required', 'numeric', 'min:0'],
            'delta_watched_seconds' => ['required', 'numeric', 'min:0', 'max:60'],
            'event_type' => ['required', 'string', 'max:32'],
            'tab_switches' => ['nullable', 'integer', 'min:0'],
            'presence_confirms' => ['nullable', 'integer', 'min:0'],
        ]);

        $currentTime = (int) round($data['current_time']);
        $deltaSeconds = (int) round($data['delta_watched_seconds']);
        $duration = max(1, (int) $module->duration_seconds);

        $progress = ModuleProgress::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_module_id' => $module->id,
            ],
            [
                'started_at' => now(),
            ]
        );

        $wasCompleted = $progress->is_completed;

        $newWatched = min($duration, $progress->watched_seconds + $deltaSeconds);
        $newMaxPos = min($duration, max($progress->max_position_seconds, $currentTime, $newWatched));
        $percent = min(100, round(($newMaxPos / $duration) * 100, 1));

        // Consider module completed when >= 95% or ended event at end of video
        $isCompleted = $progress->is_completed || $percent >= 95 || ($data['event_type'] === 'ended' && $percent >= 90);

        if ($isCompleted) {
            $percent = 100.0;
            $newWatched = $duration;
            $newMaxPos = $duration;
        }

        $progress->update([
            'watched_seconds' => $newWatched,
            'max_position_seconds' => $newMaxPos,
            'last_position_seconds' => $currentTime,
            'completion_percent' => $percent,
            'is_completed' => $isCompleted,
            'tab_switch_count' => $progress->tab_switch_count + (int) ($data['tab_switches'] ?? 0),
            'presence_confirm_count' => $progress->presence_confirm_count + (int) ($data['presence_confirms'] ?? 0),
            'started_at' => $progress->started_at ?? now(),
            'completed_at' => ($isCompleted && !$progress->completed_at) ? now() : $progress->completed_at,
            'last_watched_at' => now(),
        ]);

        $watchLog = WatchLog::firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'course_module_id' => $module->id,
                'session_token' => $data['session_token'],
            ],
            [
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'start_position_seconds' => max(0, $currentTime - $deltaSeconds),
                'end_position_seconds' => $currentTime,
                'seconds_counted' => 0,
                'started_at' => now(),
                'last_heartbeat_at' => now(),
            ]
        );

        $watchLog->update([
            'end_position_seconds' => $currentTime,
            'seconds_counted' => $watchLog->seconds_counted + $deltaSeconds,
            'last_heartbeat_at' => now(),
        ]);

        if (!$wasCompleted && $isCompleted) {
            ActivityLog::record(
                'module_complete',
                "Completato al 100% il modulo #{$module->sort_order}: {$module->title}",
                $user->id,
                $course->id,
                $module->id,
                ['duration_seconds' => $duration],
                $request
            );
        }

        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => 'active', 'enrolled_at' => now()]
        );
        $enrollment->recalculateProgress();

        return response()->json([
            'ok' => true,
            'module_progress' => [
                'watched_seconds' => $progress->watched_seconds,
                'max_position_seconds' => $progress->max_position_seconds,
                'last_position_seconds' => $progress->last_position_seconds,
                'completion_percent' => $progress->completion_percent,
                'is_completed' => $progress->is_completed,
            ],
            'enrollment' => [
                'async_hours_completed' => $enrollment->async_hours_completed,
                'sync_hours_completed' => $enrollment->sync_hours_completed,
                'total_hours_completed' => $enrollment->total_hours_completed,
                'attendance_percent' => $enrollment->attendance_percent,
                'is_eligible_for_certificate' => $enrollment->is_eligible_for_certificate,
                'certificate_url' => $enrollment->is_eligible_for_certificate
                    ? route('student.enrollments.certificate', $enrollment)
                    : null,
            ],
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    public function downloadAttachment(Request $request, ModuleAttachment $attachment)
    {
        $user = auth()->user();
        $module = $attachment->module()->with('course')->firstOrFail();

        $attachment->increment('download_count');

        ActivityLog::record(
            'attachment_download',
            "Scaricata dispensa didattica \"{$attachment->title}\" (Modulo #{$module->sort_order})",
            $user->id,
            $module->course_id,
            $module->id,
            ['attachment_id' => $attachment->id],
            $request
        );

        if (Storage::disk('public')->exists($attachment->file_path)) {
            return Storage::disk('public')->download(
                $attachment->file_path,
                Str::slug($attachment->title) . '.pdf'
            );
        }

        // Generate on-the-fly Ministerial Study Handout PDF if demo attachment
        $settings = PlatformSetting::getAllSettings();
        $pdf = Pdf::loadView('pdf.study-handout', [
            'settings' => $settings,
            'course' => $module->course,
            'module' => $module,
            'attachment' => $attachment,
            'user' => $user,
        ]);

        return $pdf->download(Str::slug($attachment->title) . '.pdf');
    }

    public function downloadCertificate(Request $request, Enrollment $enrollment)
    {
        $user = auth()->user();
        if ($enrollment->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $enrollment->load(['user', 'course']);
        $enrollment->recalculateProgress();

        if (!$enrollment->is_eligible_for_certificate && !$user->isAdmin()) {
            return back()->withErrors([
                'certificate' => 'L\'attestato è scaricabile solo al raggiungimento di almeno l\'80% delle ore previste dal corso.',
            ]);
        }

        $settings = PlatformSetting::getAllSettings();

        ActivityLog::record(
            'certificate_download',
            "Scaricato Attestato Ufficiale ({$enrollment->certificate_number}) per {$enrollment->user->full_name}",
            $user->id,
            $enrollment->course_id,
            null,
            ['enrollment_id' => $enrollment->id, 'certificate_number' => $enrollment->certificate_number],
            $request
        );

        $pdf = Pdf::loadView('pdf.certificate-allegato4', [
            'settings' => $settings,
            'enrollment' => $enrollment,
            'course' => $enrollment->course,
            'student' => $enrollment->user,
        ])->setPaper('a4', 'portrait');

        $filename = sprintf(
            'Attestato_%s_%s.pdf',
            Str::slug($enrollment->course->code),
            Str::slug($enrollment->user->full_name)
        );

        return $pdf->download($filename);
    }
}
