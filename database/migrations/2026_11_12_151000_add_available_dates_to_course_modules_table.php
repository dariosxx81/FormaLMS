<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('course_modules', 'available_at')) {
                $table->dateTime('available_at')->nullable()->after('duration_seconds');
            }
            if (!Schema::hasColumn('course_modules', 'available_until_at')) {
                $table->dateTime('available_until_at')->nullable()->after('available_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            if (Schema::hasColumn('course_modules', 'available_until_at')) {
                $table->dropColumn('available_until_at');
            }
            if (Schema::hasColumn('course_modules', 'available_at')) {
                $table->dropColumn('available_at');
            }
        });
    }
};
