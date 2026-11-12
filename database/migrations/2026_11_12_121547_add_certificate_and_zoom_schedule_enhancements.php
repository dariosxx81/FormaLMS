<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'allow_student_certificate_download')) {
                $table->boolean('allow_student_certificate_download')->default(false)->after('presence_check_interval_minutes');
            }
        });

        Schema::table('course_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('course_modules', 'zoom_scheduled_end_at')) {
                $table->dateTime('zoom_scheduled_end_at')->nullable()->after('zoom_scheduled_at');
            }
            if (!Schema::hasColumn('course_modules', 'zoom_occurrence_id')) {
                $table->string('zoom_occurrence_id', 60)->nullable()->after('zoom_meeting_id');
            }
            if (!Schema::hasColumn('course_modules', 'subtitles_url')) {
                $table->string('subtitles_url', 500)->nullable()->after('video_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            if (Schema::hasColumn('course_modules', 'subtitles_url')) {
                $table->dropColumn('subtitles_url');
            }
            if (Schema::hasColumn('course_modules', 'zoom_occurrence_id')) {
                $table->dropColumn('zoom_occurrence_id');
            }
            if (Schema::hasColumn('course_modules', 'zoom_scheduled_end_at')) {
                $table->dropColumn('zoom_scheduled_end_at');
            }
        });

        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'allow_student_certificate_download')) {
                $table->dropColumn('allow_student_certificate_download');
            }
        });
    }
};
