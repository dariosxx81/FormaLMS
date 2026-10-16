<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchLog extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'course_module_id',
        'session_token',
        'ip_address',
        'user_agent',
        'start_position_seconds',
        'end_position_seconds',
        'seconds_counted',
        'started_at',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'start_position_seconds' => 'integer',
            'end_position_seconds' => 'integer',
            'seconds_counted' => 'integer',
            'started_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
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
