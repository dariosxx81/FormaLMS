<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        $asyncSeconds = (int) ModuleProgress::query()
            ->where('user_id', $this->user_id)
            ->where('course_id', $this->course_id)
            ->sum('watched_seconds');

        $asyncHours = round($asyncSeconds / 3600, 2);
        // Cap async hours to course async_required_hours if greater, or count full watched hours
        $effectiveAsyncHours = min($asyncHours, max($course->async_required_hours, $asyncHours));
        $totalHours = round((float) $this->sync_hours_completed + $effectiveAsyncHours, 2);
        $targetHours = max(0.1, (float) $course->total_hours);
        $percent = min(100, round(($totalHours / $targetHours) * 100, 1));
        $eligible = $percent >= $course->min_attendance_percent;

        $this->async_seconds_completed = $asyncSeconds;
        $this->total_hours_completed = $totalHours;
        $this->attendance_percent = $percent;
        $this->is_eligible_for_certificate = $eligible;

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
            }
        }

        $this->save();

        return $this;
    }
}
