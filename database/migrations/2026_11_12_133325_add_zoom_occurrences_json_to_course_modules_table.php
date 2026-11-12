<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            if (!Schema::hasColumn('course_modules', 'zoom_occurrences_json')) {
                $table->json('zoom_occurrences_json')->nullable()->after('zoom_occurrence_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_modules', function (Blueprint $table) {
            if (Schema::hasColumn('course_modules', 'zoom_occurrences_json')) {
                $table->dropColumn('zoom_occurrences_json');
            }
        });
    }
};
