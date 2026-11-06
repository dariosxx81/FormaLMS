<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            $table->string('zoom_meeting_id', 60)->nullable()->after('video_url');
            $table->string('zoom_passcode', 60)->nullable()->after('zoom_meeting_id');
            $table->dateTime('zoom_scheduled_at')->nullable()->after('zoom_passcode');
            $table->string('zoom_join_url', 500)->nullable()->after('zoom_scheduled_at');
        });

        Schema::create('zoom_registrants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('zoom_meeting_id', 60)->index();
            $table->string('zoom_registrant_id', 120)->nullable()->index();
            $table->text('personal_join_url')->nullable();
            $table->unsignedInteger('attended_seconds')->default(0);
            $table->timestamp('first_joined_at')->nullable();
            $table->timestamp('last_left_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_module_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoom_registrants');

        Schema::table('course_modules', function (Blueprint $table) {
            $table->dropColumn([
                'zoom_meeting_id',
                'zoom_passcode',
                'zoom_scheduled_at',
                'zoom_join_url',
            ]);
        });
    }
};
