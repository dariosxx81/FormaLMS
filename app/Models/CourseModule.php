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
        'zoom_occurrence_id',
        'zoom_occurrences_json',
        'zoom_passcode',
        'zoom_scheduled_at',
        'zoom_scheduled_end_at',
        'zoom_join_url',
        'subtitles_url',
        'duration_seconds',
        'available_at',
        'available_until_at',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'duration_seconds' => 'integer',
            'is_published' => 'boolean',
            'available_at' => 'datetime',
            'available_until_at' => 'datetime',
            'zoom_scheduled_at' => 'datetime',
            'zoom_scheduled_end_at' => 'datetime',
            'zoom_occurrences_json' => 'array',
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

    public function getEffectiveEndAtAttribute(): ?\Carbon\Carbon
    {
        if ($this->zoom_scheduled_end_at) {
            return $this->zoom_scheduled_end_at;
        }
        if ($this->zoom_scheduled_at) {
            $duration = max(1800, (int) $this->duration_seconds);
            return $this->zoom_scheduled_at->copy()->addSeconds($duration);
        }
        return null;
    }

    public function getOccurrencesListAttribute(): array
    {
        $raw = $this->zoom_occurrences_json;
        if (empty($raw) || !is_array($raw)) {
            return [];
        }

        $now = now();
        $list = [];
        $seq = 1;
        foreach ($raw as $occ) {
            if (!empty($occ['status']) && in_array(strtolower((string) $occ['status']), ['deleted', 'cancelled', 'canceled'])) {
                continue;
            }

            $start = !empty($occ['start_time']) ? \Carbon\Carbon::parse(substr((string) $occ['start_time'], 0, 19), config('app.timezone', 'Europe/Rome')) : null;
            $durationMin = (int) ($occ['duration_minutes'] ?? $occ['duration'] ?? 120);
            $end = $start ? $start->copy()->addMinutes($durationMin) : null;

            $status = 'upcoming';
            if ($start && $end) {
                $early = $start->copy()->subMinutes(15);
                $late = $end->copy()->addMinutes(30);
                if ($now->between($early, $late)) {
                    $status = 'live';
                } elseif ($now->gt($late)) {
                    $status = 'ended';
                }
            }

            $dayMap = [
                'Monday' => 'Lunedì',
                'Tuesday' => 'Martedì',
                'Wednesday' => 'Mercoledì',
                'Thursday' => 'Giovedì',
                'Friday' => 'Venerdì',
                'Saturday' => 'Sabato',
                'Sunday' => 'Domenica',
            ];
            $englishDay = $start ? $start->format('l') : '';
            $dayName = $dayMap[$englishDay] ?? $englishDay;

            $list[] = [
                'index' => $seq++,
                'occurrence_id' => $occ['occurrence_id'] ?? null,
                'start_time' => $start,
                'end_time' => $end,
                'duration_minutes' => $durationMin,
                'status' => $status,
                'formatted_date' => $start ? $start->format('d/m/Y') : 'Data da definire',
                'formatted_time' => ($start && $end) ? ($start->format('H:i') . ' - ' . $end->format('H:i')) : '',
                'day_name' => $dayName,
            ];
        }

        return $list;
    }

    public function getNextUpcomingOccurrenceAttribute(): ?array
    {
        $list = $this->occurrences_list;
        foreach ($list as $occ) {
            if ($occ['status'] === 'live' || $occ['status'] === 'upcoming') {
                return $occ;
            }
        }
        return !empty($list) ? end($list) : null;
    }

    public function getScheduleStatusAttribute(): string
    {
        if (!$this->is_zoom) {
            return 'anytime';
        }

        $occurrences = $this->occurrences_list;
        if (!empty($occurrences)) {
            foreach ($occurrences as $occ) {
                if ($occ['status'] === 'live') {
                    return 'live';
                }
            }
            foreach ($occurrences as $occ) {
                if ($occ['status'] === 'upcoming') {
                    return 'upcoming';
                }
            }
            return 'ended';
        }

        if (!$this->zoom_scheduled_at) {
            return 'anytime';
        }

        $now = now();
        $earlyAccessTime = $this->zoom_scheduled_at->copy()->subMinutes(15);
        $closingTime = $this->effective_end_at ? $this->effective_end_at->copy()->addMinutes(30) : $this->zoom_scheduled_at->copy()->addHours(3);

        if ($now->lt($earlyAccessTime)) {
            return 'upcoming';
        }
        if ($now->between($earlyAccessTime, $closingTime)) {
            return 'live';
        }
        return 'ended';
    }

    public function getIsLockedByScheduleAttribute(): bool
    {
        return $this->schedule_status === 'upcoming';
    }

    public function getIsLiveNowAttribute(): bool
    {
        return $this->schedule_status === 'live';
    }

    public function getHasSubtitlesAttribute(): bool
    {
        return !empty($this->subtitles_url) || $this->is_youtube || $this->is_vimeo;
    }

    public function getIsLockedByDateAttribute(): bool
    {
        if ($this->video_source_type === 'zoom') {
            return false;
        }

        $now = now();
        if ($this->available_at && $now->lt($this->available_at)) {
            return true;
        }

        if ($this->available_until_at && $now->gt($this->available_until_at)) {
            return true;
        }

        return false;
    }

    public function getIsAvailableAttribute(): bool
    {
        return !$this->is_locked_by_date;
    }

    public function getLockReasonAttribute(): ?string
    {
        if ($this->is_locked_by_date) {
            $now = now();
            if ($this->available_at && $now->lt($this->available_at)) {
                return "Disponibile a partire dal " . $this->available_at->format('d/m/Y') . " alle ore " . $this->available_at->format('H:i');
            }
            if ($this->available_until_at && $now->gt($this->available_until_at)) {
                return "Periodo di fruizione scaduto il " . $this->available_until_at->format('d/m/Y') . " alle ore " . $this->available_until_at->format('H:i');
            }
        }
        return null;
    }
}
