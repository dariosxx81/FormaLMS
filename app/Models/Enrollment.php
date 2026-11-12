<?php

namespace App\Models;

use App\Mail\CertificateEarnedMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'sync_hours_completed',
        'async_seconds_completed',
        'total_hours_completed',
        'attendance_percent',
        'is_eligible_for_certificate',
        'certificate_number',
        'certificate_issued_at',
        'enrolled_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sync_hours_completed' => 'float',
            'async_seconds_completed' => 'integer',
            'total_hours_completed' => 'float',
            'attendance_percent' => 'float',
            'is_eligible_for_certificate' => 'boolean',
            'certificate_issued_at' => 'datetime',
            'enrolled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function getAsyncHoursCompletedAttribute(): float
    {
        return round($this->async_seconds_completed / 3600, 2);
    }

    public function recalculateProgress(): self
    {
        $course = $this->course()->first();
        if (!$course) {
            return $this;
        }

        // Separate Async modules from Zoom Sync modules
        // (Includes both live Zoom modules and Zoom modules converted to post-live video recording for recovery)
        $zoomModuleIds = CourseModule::query()
            ->where('course_id', $this->course_id)
            ->where(function ($q) {
                $q->where('video_source_type', 'zoom')
                  ->orWhere(function ($sq) {
                      $sq->whereNotNull('zoom_meeting_id')
                         ->where('zoom_meeting_id', '!=', '');
                  });
            })
            ->pluck('id')
            ->toArray();

        $asyncSeconds = (int) ModuleProgress::query()
            ->where('user_id', $this->user_id)
            ->where('course_id', $this->course_id)
            ->when(!empty($zoomModuleIds), fn($q) => $q->whereNotIn('course_module_id', $zoomModuleIds))
            ->sum('watched_seconds');

        // If the course has Zoom modules (or Zoom module addon is enabled with Zoom modules),
        // sync_hours_completed is strictly calculated from certified Zoom module logs (no manual override)
        if (!empty($zoomModuleIds)) {
            $zoomWatchedSeconds = (int) ModuleProgress::query()
                ->where('user_id', $this->user_id)
                ->where('course_id', $this->course_id)
                ->whereIn('course_module_id', $zoomModuleIds)
                ->sum('watched_seconds');

            $syncRequiredSeconds = (float) $course->sync_required_hours * 3600;
            $effectiveZoomSeconds = $syncRequiredSeconds > 0
                ? min($zoomWatchedSeconds, $syncRequiredSeconds)
                : $zoomWatchedSeconds;

            $this->sync_hours_completed = round($effectiveZoomSeconds / 3600, 2);
        }

        // Course requirements in seconds
        $asyncRequiredSeconds = (float) $course->async_required_hours * 3600;
        $effectiveAsyncSeconds = $asyncRequiredSeconds > 0 
            ? min($asyncSeconds, $asyncRequiredSeconds)
            : $asyncSeconds;

        // If sync hours (Zoom) are disabled on the course (sync_required_hours == 0), count ONLY platform FAD hours
        $syncSeconds = $course->has_sync_hours 
            ? ((float) $this->sync_hours_completed * 3600) 
            : 0.0;

        $totalCompletedSeconds = $syncSeconds + $effectiveAsyncSeconds;
        $targetTotalSeconds = (float) $course->total_hours * 3600;

        if ($targetTotalSeconds > 0) {
            $percent = min(100.0, round(($totalCompletedSeconds / $targetTotalSeconds) * 100, 1));
        } else {
            $percent = 100.0;
        }

        $totalHours = round($totalCompletedSeconds / 3600, 2);
        $eligible = $percent >= (float) $course->min_attendance_percent;

        $this->async_seconds_completed = $asyncSeconds;
        $this->total_hours_completed = $totalHours;
        $this->attendance_percent = $percent;
        $this->is_eligible_for_certificate = $eligible;

        $justEarnedCertificate = false;
        if ($eligible) {
            if ($this->status === 'active') {
                $this->status = 'completed';
            }
            if (!$this->certificate_number) {
                $this->certificate_number = sprintf(
                    'ATT-%s-%d-%04d',
                    strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $course->code), 0, 8)),
                    now()->year,
                    $this->id ?: rand(100, 999)
                );
                $this->certificate_issued_at = now();
                $justEarnedCertificate = true;
            }
        }

        $this->save();

        if ($justEarnedCertificate && $course->allow_student_certificate_download && PlatformSetting::isMailActive('certificate')) {
            try {
                $user = $this->user ?: User::find($this->user_id);
                if ($user) {
                    $settings = PlatformSetting::getAllSettings();
                    Mail::to($user->email)->send(new CertificateEarnedMail($user, $course, $this, $settings));
                    ActivityLog::record(
                        'certificate_earned_mail_sent',
                        "Inviata email di rilascio attestato ({$this->certificate_number}) per \"{$course->title}\" a {$user->email}",
                        $user->id,
                        $course->id
                    );
                }
            } catch (\Throwable $e) {
                Log::warning("Impossibile inviare email attestato: " . $e->getMessage());
            }
        }

        return $this;
    }
}
