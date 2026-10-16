<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'code',
        'title',
        'course_type',
        'thematic_area',
        'authorization_details',
        'training_center_name',
        'training_center_city',
        'description',
        'total_hours',
        'sync_required_hours',
        'async_required_hours',
        'min_attendance_percent',
        'strict_tab_pause',
        'presence_check_interval_minutes',
        'allow_self_enrollment',
        'invite_code',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'total_hours' => 'float',
            'sync_required_hours' => 'float',
            'async_required_hours' => 'float',
            'min_attendance_percent' => 'integer',
            'strict_tab_pause' => 'boolean',
            'presence_check_interval_minutes' => 'integer',
            'allow_self_enrollment' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseModule::class)->orderBy('sort_order');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function moduleProgress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class);
    }

    public function getMinimumRequiredHoursAttribute(): float
    {
        return round(($this->total_hours * $this->min_attendance_percent) / 100, 2);
    }
}
