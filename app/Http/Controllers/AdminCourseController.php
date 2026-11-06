<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class AdminCourseController extends Controller
{
    public function index(Request $request)
    {
        $settings = PlatformSetting::getAllSettings();

        $courses = Course::withCount(['modules', 'enrollments'])
            ->latest()
            ->get();

        $coursesJson = $courses->map(function ($c) {
            return [
                'id' => $c->id,
                'code' => $c->code,
                'title' => $c->title,
                'thematic_area' => $c->thematic_area,
                'course_type' => $c->course_type,
                'access_code' => $c->access_code,
                'badge_image_url' => $c->badge_image_url,
                'is_active' => (bool)$c->is_active,
                'total_hours' => $c->total_hours,
                'sync_required_hours' => $c->sync_required_hours,
                'async_required_hours' => $c->async_required_hours,
                'has_sync_hours' => (bool)$c->has_sync_hours,
                'modules_count' => $c->modules_count,
                'enrollments_count' => $c->enrollments_count,
                'show_url' => route('admin.courses.show', $c),
                'player_url' => route('student.courses.show', $c),
            ];
        });

        return view('admin.courses.index', compact('settings', 'courses', 'coursesJson'));
    }
}
