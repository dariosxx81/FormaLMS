<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleAttachment extends Model
{
    protected $fillable = [
        'course_module_id',
        'title',
        'file_path',
        'file_size_kb',
        'download_count',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'course_module_id');
    }
}
