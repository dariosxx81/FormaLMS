<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomRegistrant extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'course_module_id',
        'zoom_meeting_id',
        'zoom_registrant_id',
        'personal_join_url',
        'attended_seconds',
        'first_joined_at',
        'last_left_at',
    ];

    protected function casts(): array
    {
        return [
            'attended_seconds' => 'integer',
            'first_joined_at' => 'datetime',
            'last_left_at' => 'datetime',
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
