<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    protected $fillable = [
        'course_id',
        'sort_order',
        'title',
        'instructor_name',
        'description',
        'video_source_type', // 'upload' | 'url' | 'youtube' | 'vimeo' | 'zoom'
        'video_url',
        'zoom_meeting_id',
        'zoom_passcode',
        'zoom_scheduled_at',
        'zoom_join_url',
        'duration_seconds',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'duration_seconds' => 'integer',
            'is_published' => 'boolean',
            'zoom_scheduled_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ModuleAttachment::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = intdiv($this->duration_seconds, 3600);
        $minutes = intdiv($this->duration_seconds % 3600, 60);
        if ($hours > 0) {
            return sprintf('%dh %02dm', $hours, $minutes);
        }
        return sprintf('%d min', max(1, $minutes));
    }

    public function getYoutubeVideoIdAttribute(): ?string
    {
        if ($this->video_source_type === 'zoom' || !$this->video_url) {
            return null;
        }
        $url = trim($this->video_url);
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getIsYoutubeAttribute(): bool
    {
        if ($this->video_source_type === 'zoom') {
            return false;
        }
        return $this->video_source_type === 'youtube' || ($this->video_source_type !== 'vimeo' && $this->youtube_video_id !== null);
    }

    public function getVimeoVideoIdAttribute(): ?string
    {
        if ($this->video_source_type === 'zoom' || !$this->video_url) {
            return null;
        }
        $url = trim($this->video_url);
        if (preg_match('/^[0-9]{6,12}$/', $url)) {
            return $url;
        }
        // Match standard links: vimeo.com/123456789 or player.vimeo.com/video/123456789
        if (preg_match('/(?:vimeo\.com\/(?:video\/)?|player\.vimeo\.com\/video\/)([0-9]{6,12})/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function getIsVimeoAttribute(): bool
    {
        if ($this->video_source_type === 'zoom') {
            return false;
        }
        return $this->video_source_type === 'vimeo' || ($this->video_source_type !== 'youtube' && $this->vimeo_video_id !== null);
    }

    public function getIsZoomAttribute(): bool
    {
        return $this->video_source_type === 'zoom';
    }
}
