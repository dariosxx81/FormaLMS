<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title');
            $table->string('course_type', 40)->default('aggiornamento'); // aggiornamento | specializzazione | professionale
            $table->string('thematic_area')->nullable();
            $table->string('authorization_details')->nullable();
            $table->string('training_center_name')->nullable();
            $table->string('training_center_city')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_hours', 6, 2)->default(25.00);
            $table->decimal('sync_required_hours', 6, 2)->default(20.00);
            $table->decimal('async_required_hours', 6, 2)->default(5.00);
            $table->unsignedTinyInteger('min_attendance_percent')->default(80);
            $table->boolean('strict_tab_pause')->default(false);
            $table->unsignedSmallInteger('presence_check_interval_minutes')->default(0); // 0 = disattivato
            $table->boolean('allow_self_enrollment')->default(true);
            $table->string('invite_code', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->string('title');
            $table->string('instructor_name')->nullable();
            $table->text('description')->nullable();
            $table->string('video_source_type', 20)->default('url'); // 'upload' | 'url'
            $table->string('video_url')->nullable();
            $table->unsignedInteger('duration_seconds')->default(3600);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('module_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->unsignedInteger('file_size_kb')->default(120);
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('active'); // 'active' | 'pending' | 'completed'
            $table->decimal('sync_hours_completed', 6, 2)->default(0.00);
            $table->unsignedInteger('async_seconds_completed')->default(0);
            $table->decimal('total_hours_completed', 6, 2)->default(0.00);
            $table->decimal('attendance_percent', 5, 2)->default(0.00);
            $table->boolean('is_eligible_for_certificate')->default(false);
            $table->string('certificate_number', 60)->nullable();
            $table->timestamp('certificate_issued_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('module_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->unsignedInteger('max_position_seconds')->default(0);
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->decimal('completion_percent', 5, 2)->default(0.00);
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('tab_switch_count')->default(0);
            $table->unsignedInteger('presence_confirm_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_module_id']);
        });

        Schema::create('watch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('session_token', 64)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('start_position_seconds')->default(0);
            $table->unsignedInteger('end_position_seconds')->default(0);
            $table->unsignedInteger('seconds_counted')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_module_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 50)->index();
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('watch_logs');
        Schema::dropIfExists('module_progress');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('module_attachments');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('platform_settings');
    }
};
