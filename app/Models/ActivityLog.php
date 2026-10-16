<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'course_module_id',
        'action_type',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
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

    public static function record(
        string $actionType,
        string $description,
        ?int $userId = null,
        ?int $courseId = null,
        ?int $moduleId = null,
        array $metadata = [],
        ?Request $request = null
    ): self {
        $req = $request ?? request();
        return static::create([
            'user_id' => $userId ?? auth()->id(),
            'course_id' => $courseId,
            'course_module_id' => $moduleId,
            'action_type' => $actionType,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'ip_address' => $req?->ip(),
            'user_agent' => substr((string) $req?->userAgent(), 0, 255),
        ]);
    }
}
