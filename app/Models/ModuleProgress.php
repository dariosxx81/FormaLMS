<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleProgress extends Model
{
    protected $table = 'module_progress';

    protected $fillable = [
        'user_id',
        'course_id',
        'course_module_id',
        'watched_seconds',
        'max_position_seconds',
        'last_position_seconds',
        'completion_percent',
        'is_completed',
        'tab_switch_count',
        'presence_confirm_count',
        'started_at',
        'completed_at',
        'last_watched_at',
    ];

    protected function casts(): array
    {
        return [
            'watched_seconds' => 'integer',
            'max_position_seconds' => 'integer',
            'last_position_seconds' => 'integer',
            'completion_percent' => 'float',
            'is_completed' => 'boolean',
            'tab_switch_count' => 'integer',
            'presence_confirm_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_watched_at' => 'datetime',
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

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }
}
