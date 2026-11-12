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
        'badge_image_url',
        'total_hours',
        'sync_required_hours',
        'async_required_hours',
        'min_attendance_percent',
        'strict_tab_pause',
        'presence_check_interval_minutes',
        'allow_student_certificate_download',
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
            'allow_student_certificate_download' => 'boolean',
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

    public function asyncModules(): HasMany
    {
        return $this->hasMany(CourseModule::class)
            ->where('video_source_type', '!=', 'zoom')
            ->orderBy('sort_order');
    }

    public function zoomModules(): HasMany
    {
        return $this->hasMany(CourseModule::class)
            ->where('video_source_type', 'zoom')
            ->orderBy('zoom_scheduled_at')
            ->orderBy('sort_order');
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

    public function getHasSyncHoursAttribute(): bool
    {
        return (float) $this->sync_required_hours > 0;
    }

    public function getAccessCodeAttribute(): string
    {
        return !empty($this->invite_code) ? $this->invite_code : $this->code;
    }

    public function hasZoomModules(): bool
    {
        return $this->modules()->where('video_source_type', 'zoom')->exists();
    }

    public function getZoomModulesCountAttribute(): int
    {
        return $this->modules()->where('video_source_type', 'zoom')->count();
    }

    public function getZoomDurationMinutesAttribute(): int
    {
        return (int) round($this->modules()->where('video_source_type', 'zoom')->sum('duration_seconds') / 60);
    }

    public function getAsyncDurationMinutesAttribute(): int
    {
        return (int) round($this->modules()->where('video_source_type', '!=', 'zoom')->sum('duration_seconds') / 60);
    }

    /**
     * Sincronizza automaticamente le ore del corso (Zoom sincrono e FAD asincrono)
     * basandosi sulla durata effettiva dei singoli moduli didattici caricati.
     */
    public function autoSyncDurationsFromModules(): void
    {
        $modules = $this->modules()->get();
        if ($modules->isEmpty()) {
            return;
        }

        $syncSeconds = (int) $modules->where('video_source_type', 'zoom')->sum('duration_seconds');
        $asyncSeconds = (int) $modules->where('video_source_type', '!=', 'zoom')->sum('duration_seconds');

        $syncHours = round($syncSeconds / 3600, 2);
        $asyncHours = round($asyncSeconds / 3600, 2);

        $hasZoomModules = $modules->contains(fn($m) => $m->video_source_type === 'zoom');

        if ($hasZoomModules) {
            $this->sync_required_hours = $syncHours;
            $this->async_required_hours = max(0.01, $asyncHours);
            $this->total_hours = max(0.01, round($syncHours + $asyncHours, 2));
        } else {
            // Nessun modulo Zoom presente nel corso.
            // Se le ore sincrone erano state impostate manualmente per CSV webinar, preserviamo l'impostazione;
            // altrimenti aggiorniamo solo le ore asincrone e il totale.
            $this->async_required_hours = max(0.01, $asyncHours);
            $this->total_hours = max(0.01, round((float) $this->sync_required_hours + $asyncHours, 2));
        }

        $this->save();

        foreach ($this->enrollments as $enrollment) {
            $enrollment->recalculateProgress();
        }
    }
}
